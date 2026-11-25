<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOutlookMailRequest;
use App\Models\OutlookMailSetting;
use App\Services\AuditLogger;
use App\Services\OutlookOAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class OutlookMailController extends Controller
{
    public function edit()
    {
        return view('admin.mail.edit', ['settings' => OutlookMailSetting::current()]);
    }

    public function update(UpdateOutlookMailRequest $request)
    {
        DB::transaction(function () use ($request) {
            $settings = OutlookMailSetting::lockForUpdate()->firstOrCreate(['id' => 1]);
            $original = $settings->fingerprint();
            $oldClientId = $settings->client_id;
            $settings->fill($request->safe()->except('client_secret'));
            if ($request->filled('client_secret')) {
                $settings->client_secret = $request->validated('client_secret');
            } elseif ($settings->client_id !== $oldClientId) {
                $settings->client_secret = null;
            }
            if ($settings->fingerprint() !== $original) {
                $settings->clearTokens();
            }
            $settings->save();
            AuditLogger::record('mail_settings.updated', $settings);
        });

        return back()->with('success', 'Configurações salvas. Conecte o Outlook caso a conta ainda não esteja autorizada.');
    }

    public function connect(Request $request)
    {
        $settings = OutlookMailSetting::current();
        if (! $settings->client_id || ! $settings->client_secret) {
            return back()->with('error', 'Salve o ID e o segredo do aplicativo Microsoft antes de conectar.');
        }
        $state = Str::random(64);
        $verifier = Str::random(80);
        $request->session()->put('outlook_oauth', [
            'state' => $state, 'verifier' => $verifier, 'expires' => time() + 600,
            'fingerprint' => $settings->fingerprint(),
        ]);

        return redirect()->away(OutlookOAuth::BASE.'/authorize?'.http_build_query([
            'client_id' => $settings->client_id, 'response_type' => 'code', 'response_mode' => 'query',
            'redirect_uri' => route('mail-settings.callback'), 'scope' => OutlookOAuth::SCOPE,
            'state' => $state, 'code_challenge_method' => 'S256',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'login_hint' => $settings->from_address, 'prompt' => 'select_account',
        ]));
    }

    public function callback(Request $request, OutlookOAuth $oauth)
    {
        $pending = $request->session()->pull('outlook_oauth');
        $settings = OutlookMailSetting::current();
        abort_unless(is_array($pending) && ($pending['expires'] ?? 0) >= time()
            && is_string($request->query('state'))
            && hash_equals($pending['state'], $request->query('state'))
            && hash_equals($pending['fingerprint'], $settings->fingerprint()), 419);
        try {
            if ($request->has('error') || ! is_string($request->query('code'))) {
                throw new \RuntimeException;
            }
            $tokens = $oauth->exchange($settings, $request->query('code'), $pending['verifier']);
            DB::transaction(function () use ($oauth, $settings, $tokens) {
                $current = OutlookMailSetting::lockForUpdate()->findOrFail(1);
                if (! hash_equals($settings->fingerprint(), $current->fingerprint())) {
                    throw new \RuntimeException;
                }
                $oauth->storeTokens($current, $tokens);
                AuditLogger::record('mail_settings.connected', $current);
            });
        } catch (Throwable) {
            return redirect()->route('mail-settings.edit')->with('error', 'Não foi possível conectar. Verifique o aplicativo Microsoft e autorize a mesma conta informada como remetente.');
        }

        return redirect()->route('mail-settings.edit')->with('success', 'Conta Outlook autorizada. Use o envio de teste para verificar o SMTP.');
    }

    public function disconnect()
    {
        DB::transaction(function () {
            $settings = OutlookMailSetting::lockForUpdate()->find(1);
            if ($settings) {
                $settings->clearTokens();
                $settings->save();
                AuditLogger::record('mail_settings.disconnected', $settings);
            }
        });

        return back()->with('success', 'Conta desconectada deste site. Novos envios aguardam uma nova conexão.');
    }

    public function test()
    {
        try {
            $status = Artisan::call('notifications:test-smtp');
        } catch (Throwable) {
            $status = 1;
        }

        return back()->with($status === 0 ? 'success' : 'error', $status === 0
            ? 'Os dois e-mails [TESTE] foram aceitos pelo SMTP. Confira a caixa de entrada e o lixo eletrônico do destinatário.'
            : 'O envio de teste não foi confirmado para os dois fluxos. Confira o aplicativo, a conexão Outlook e o remetente.');
    }
}

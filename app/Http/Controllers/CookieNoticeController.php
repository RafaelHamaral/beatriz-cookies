<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CookieNoticeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $path = $request->input('return_path', '/');

        // Only redirect to a local path, never to a supplied external URL.
        if (! is_string($path) || ! preg_match('~^/(?!/)[^\\\\\x00-\x20?#]*$~D', $path)) {
            $path = '/';
        }

        return redirect($path)->withCookie(cookie(
            'beatriz_cookie_notice', 'v1', 60 * 24 * 180,
            '/', null, $request->isSecure(), true, false, 'lax',
        ));
    }
}

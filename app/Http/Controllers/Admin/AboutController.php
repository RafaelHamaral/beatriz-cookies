<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAboutRequest;
use App\Models\AboutSection;
use App\Services\AuditLogger;
use App\Services\ProductImageStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Exceptions\InvalidProductImage;

class AboutController extends Controller
{
    public function edit(): \Illuminate\View\View
    {
        return view('admin.about.edit', ['about' => AboutSection::current()]);
    }

    public function update(UpdateAboutRequest $request, ProductImageStorage $images): \Illuminate\Http\RedirectResponse
    {
        $uploaded = [];
        try {
            foreach ($request->file('images', []) as $file) {
                $uploaded[(string) \Illuminate\Support\Str::uuid()] = $images->store($file);
            }
            $primary = $request->hasFile('image') ? $images->store($request->file('image')) : null;
            if ($primary) {
                $uploaded['primary'] = $primary;
            }
            $oldPaths = DB::transaction(function () use ($request, $uploaded) {
                $about = AboutSection::query()->lockForUpdate()->find(1) ?? new AboutSection;
                $oldGallery = $about->gallery();
                $gallery = $oldGallery;
                foreach ($request->input('remove_images', []) as $key) {
                    unset($gallery[$key]);
                }
                if ($request->boolean('remove_image')) {
                    unset($gallery['primary']);
                }
                $gallery = array_replace($gallery, $uploaded);
                if (count($gallery) > 8) {
                    throw ValidationException::withMessages(['images' => 'A seção Sobre permite até 8 imagens. Remova alguma antes de adicionar outras.']);
                }
                $about->id = 1;
                $about->fill($request->safe()->only(['title', 'body', 'image_alt', 'is_active']));
                $about->image_path = $gallery['primary']['path'] ?? null;
                $about->image_mime = $gallery['primary']['mime'] ?? null;
                unset($gallery['primary']);
                $about->images = $gallery;
                $about->save();
                AuditLogger::record('about.updated', $about);

                return array_diff(array_column($oldGallery, 'path'), array_column($about->gallery(), 'path'));
            });
        } catch (\Throwable $exception) {
            foreach ($uploaded as $image) {
                $images->delete($image['path']);
            }
            if ($exception instanceof InvalidProductImage) {
                throw ValidationException::withMessages(['images' => $exception->getMessage()]);
            }
            throw $exception;
        }
        foreach ($oldPaths as $path) {
            $images->delete($path);
        }

        return redirect()->route('about.edit')->with('success', 'Seção Sobre atualizada.');
    }
}

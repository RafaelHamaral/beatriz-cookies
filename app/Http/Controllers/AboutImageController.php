<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AboutSection;
use Illuminate\Support\Facades\Storage;

class AboutImageController extends Controller
{
    public function __invoke(): \Illuminate\Http\Response
    {
        $about = AboutSection::current();
        $image = $about->gallery()[request()->query('image', 'primary')] ?? null;
        abort_unless($image, 404);
        abort_unless($about->is_active || auth()->user()?->managesCatalog(), 404);
        abort_unless($image['path'] && str_starts_with($image['path'], 'products/') && ! str_contains($image['path'], '..'), 404);
        abort_unless(in_array($image['mime'], ['image/jpeg', 'image/png', 'image/webp'], true), 404);
        abort_unless(Storage::disk('catalog')->exists($image['path']), 404);

        return response(Storage::disk('catalog')->get($image['path']), 200, [
            'Content-Type' => $image['mime'],
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
            'Cache-Control' => 'no-store',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductImage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function __invoke(ProductImage $image): Response
    {
        $product = $image->product()->with('category')->first();
        $allowed = $product?->isVisibleOnStore() === true || auth()->user()?->managesCatalog() === true;

        abort_unless($allowed, 404);
        abort_unless(in_array($image->mime, ['image/jpeg', 'image/png', 'image/webp'], true), 404);
        abort_if(str_contains($image->path, '..') || ! str_starts_with($image->path, 'products/'), 404);
        abort_unless(Storage::disk('catalog')->exists($image->path), 404);

        return response(Storage::disk('catalog')->get($image->path), 200, [
            'Content-Type' => $image->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}

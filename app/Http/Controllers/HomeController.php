<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'about' => \App\Models\AboutSection::current(),
            'products' => Product::query()
                ->visibleOnStore()
                ->with(['coverImage', 'category'])
                ->latest('id')
                ->limit(6)
                ->get(),
            'reviews' => Review::query()
                ->where('status', 'approved')
                ->latest('moderated_at')
                ->limit(6)
                ->get(),
        ]);
    }
}

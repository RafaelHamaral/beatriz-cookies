<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['products' => fn ($query) => $query->visibleOnStore()->with('coverImage')->orderBy('name')])
            ->get()
            ->filter(fn (Category $category) => $category->products->isNotEmpty())
            ->values();

        return view('catalog.index', ['categories' => $categories]);
    }

    public function category(Category $category): View
    {
        abort_unless($category->is_active, 404);

        $products = $category->products()->visibleOnStore()->with('coverImage')->orderBy('name')->get();

        return view('catalog.category', [
            'category' => $category,
            'products' => $products,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ProductPageController extends Controller
{
    public function __invoke(Product $product): View
    {
        $product->load('category', 'images', 'components.product');
        abort_unless($product->isVisibleOnStore(), 404);

        return view('catalog.product', ['product' => $product]);
    }
}

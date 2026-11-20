<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivationRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AuditLogger;
use App\Services\ProductImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('admin.products.index', [
            'products' => Product::query()->with('category')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.create', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request, ProductImageStorage $storage): RedirectResponse
    {
        $written = [];

        try {
            $product = DB::transaction(function () use ($request, $storage, &$written) {
                $product = Product::query()->create([
                    'category_id' => (int) $request->input('category_id'),
                    'name' => $request->string('name')->toString(),
                    'slug' => Product::makeSlug($request->string('name')->toString()),
                    'description' => $request->string('description')->toString(),
                    'ingredients' => $request->string('ingredients')->toString(),
                    'allergens' => $request->string('allergens')->toString() ?: null,
                    'price_cents' => $request->priceCents(),
                    'is_available' => $request->boolean('is_available'),
                    'is_active' => $request->boolean('is_active'),
                ]);

                $newIds = $this->storeUploads($product, $request->file('images', []), $storage, $written);
                $this->assignCover($product, (string) $request->input('cover', 'new:0'), $newIds);

                return $product;
            });
        } catch (Throwable $exception) {
            foreach ($written as $path) {
                $storage->delete($path);
            }

            throw $exception;
        }

        AuditLogger::record('product.created', $product, [
            'name' => $product->name,
            'price_cents' => $product->price_cents,
            'is_active' => $product->is_active,
        ]);

        return redirect()->route('products.index')->with('success', 'Produto criado.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('view', $product);

        return view('admin.products.edit', [
            'product' => $product->load('images'),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, ProductImageStorage $storage): RedirectResponse
    {
        $written = [];
        $removed = [];

        try {
            DB::transaction(function () use ($request, $product, $storage, &$written, &$removed) {
                $product->fill([
                    'category_id' => (int) $request->input('category_id'),
                    'name' => $request->string('name')->toString(),
                    'description' => $request->string('description')->toString(),
                    'ingredients' => $request->string('ingredients')->toString(),
                    'allergens' => $request->string('allergens')->toString() ?: null,
                    'price_cents' => $request->priceCents(),
                    'is_available' => $request->boolean('is_available'),
                    'is_active' => $request->boolean('is_active'),
                ])->save();

                $removeIds = collect($request->input('remove', []))->map(fn ($id) => (int) $id)->all();
                $dropping = $product->images()->whereIn('id', $removeIds)->get();
                foreach ($dropping as $image) {
                    $removed[] = $image->path;
                    $image->delete();
                }

                $newIds = $this->storeUploads($product, $request->file('images', []) ?? [], $storage, $written);
                $this->assignCover($product, $request->string('cover')->toString(), $newIds);
            });
        } catch (Throwable $exception) {
            foreach ($written as $path) {
                $storage->delete($path);
            }

            throw $exception;
        }

        foreach ($removed as $path) {
            $storage->delete($path);
        }

        AuditLogger::record('product.updated', $product, [
            'name' => $product->name,
            'price_cents' => $product->price_cents,
            'is_active' => $product->is_active,
            'is_available' => $product->is_available,
        ]);

        return redirect()->route('products.edit', $product)->with('success', 'Produto atualizado.');
    }

    public function activation(ActivationRequest $request, Product $product): RedirectResponse
    {
        $product->is_active = $request->boolean('is_active');
        $product->save();

        AuditLogger::record('product.activation_changed', $product, [
            'is_active' => $product->is_active,
        ]);

        return redirect()->route('products.index')->with('success', 'Produto atualizado.');
    }

    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $files
     * @param  list<string>  $written
     * @return list<int>
     */
    private function storeUploads(Product $product, mixed $files, ProductImageStorage $storage, array &$written): array
    {
        $uploads = is_array($files) ? $files : [];
        $start = (int) $product->images()->max('sort_order');
        $start = $product->images()->exists() ? $start + 1 : 0;
        $ids = [];

        foreach (array_values($uploads) as $offset => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $stored = $storage->store($file);
            $written[] = $stored['path'];
            $image = $product->images()->create([
                'public_id' => (string) Str::uuid(),
                'path' => $stored['path'],
                'mime' => $stored['mime'],
                'width' => $stored['width'],
                'height' => $stored['height'],
                'is_cover' => false,
                'sort_order' => $start + $offset,
            ]);
            $ids[] = $image->id;
        }

        return $ids;
    }

    /**
     * @param  list<int>  $newIds
     */
    private function assignCover(Product $product, string $cover, array $newIds): void
    {
        $coverId = null;

        if (preg_match('/^existing:(\d+)$/', $cover, $matches)) {
            $coverId = (int) $matches[1];
        } elseif (preg_match('/^new:(\d+)$/', $cover, $matches) && isset($newIds[(int) $matches[1]])) {
            $coverId = $newIds[(int) $matches[1]];
        }

        $belongsToProduct = ProductImage::query()
            ->where('product_id', $product->id)
            ->whereKey($coverId)
            ->exists();

        if ($coverId === null || ! $belongsToProduct) {
            $coverId = (int) ProductImage::query()
                ->where('product_id', $product->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('id');
        }

        ProductImage::query()->where('product_id', $product->id)->update(['is_cover' => false]);
        ProductImage::query()->whereKey($coverId)->update(['is_cover' => true]);
    }
}

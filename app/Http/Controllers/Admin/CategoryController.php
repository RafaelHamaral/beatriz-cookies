<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivationRequest;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        return view('admin.categories.index', [
            'categories' => Category::query()->withCount('products')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::query()->create([
            'name' => $request->string('name')->toString(),
            'slug' => Category::makeSlug($request->string('name')->toString()),
            'description' => $request->string('description')->toString() ?: null,
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogger::record('category.created', $category, [
            'name' => $category->name,
            'is_active' => $category->is_active,
        ]);

        return redirect()->route('categories.index')->with('success', 'Categoria criada.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('view', $category);

        return view('admin.categories.edit', ['category' => $category]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->fill([
            'name' => $request->string('name')->toString(),
            'description' => $request->string('description')->toString() ?: null,
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ])->save();

        AuditLogger::record('category.updated', $category, [
            'name' => $category->name,
            'is_active' => $category->is_active,
        ]);

        return redirect()->route('categories.index')->with('success', 'Categoria atualizada.');
    }

    public function activation(ActivationRequest $request, Category $category): RedirectResponse
    {
        $category->is_active = $request->boolean('is_active');
        $category->save();

        AuditLogger::record('category.activation_changed', $category, [
            'is_active' => $category->is_active,
        ]);

        return redirect()->route('categories.index')->with('success', 'Categoria atualizada.');
    }
}

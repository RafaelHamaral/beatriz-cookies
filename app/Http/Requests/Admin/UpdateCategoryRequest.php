<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Category;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category && $this->user()?->can('update', $category) === true;
    }
}

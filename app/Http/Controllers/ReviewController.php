<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function create(): View
    {
        return view('reviews.create');
    }

    public function store(StoreReviewRequest $request): RedirectResponse
    {
        Review::query()->create($request->validated() + ['status' => 'pending']);

        return redirect()->route('reviews.create')
            ->with('success', 'Avaliação recebida. Ela aparecerá no site após aprovação da loja.');
    }
}

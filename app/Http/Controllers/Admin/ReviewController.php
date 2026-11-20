<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateReviewRequest;
use App\Models\Review;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Review::class);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Review::STATUSES))],
        ]);
        $reviews = Review::query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function update(ModerateReviewRequest $request, Review $review)
    {
        DB::transaction(function () use ($request, $review): void {
            $locked = Review::query()->whereKey($review->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->version !== (int) $request->validated('version')) {
                throw ValidationException::withMessages(['status' => 'A avaliação foi alterada por outro operador. Recarregue a página.']);
            }

            $locked->status = $request->validated('status');
            $locked->moderated_at = $locked->status === 'pending' ? null : now();
            $locked->moderated_by = $locked->status === 'pending' ? null : $request->user()->id;
            $locked->version++;
            $locked->save();
            AuditLogger::record('review.moderated', $locked, ['status' => $locked->status]);
        }, 3);

        return redirect()->route('reviews.index')->with('success', 'Avaliação atualizada.');
    }
}

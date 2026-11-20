<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardMetrics $metrics): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $fromDate = $validated['from'] ?? now()->startOfMonth()->toDateString();
        $toDate = $validated['to'] ?? now()->toDateString();
        $from = CarbonImmutable::createFromFormat('Y-m-d', $fromDate)->startOfDay();
        $to = CarbonImmutable::createFromFormat('Y-m-d', $toDate)->endOfDay();

        return view('admin.dashboard', [
            'from' => $fromDate,
            'to' => $toDate,
            'metrics' => $metrics->forPeriod($from, $to),
        ]);
    }
}

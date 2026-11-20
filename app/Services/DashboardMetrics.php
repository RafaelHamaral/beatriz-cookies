<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonInterface;

class DashboardMetrics
{
    /**
     * @return array{revenue_cents: int, units_sold: int, pending_orders: int, ranking: mixed, chart: array<string, array{revenue_cents: int, units: int}>}
     */
    public function forPeriod(CarbonInterface $from, CarbonInterface $to): array
    {
        $paidOrders = Order::query()
            ->where('payment_status', 'paid')
            ->where('fulfillment_status', '!=', 'cancelled')
            ->whereBetween('paid_at', [$from, $to])
            ->withSum('items', 'quantity')
            ->orderBy('paid_at')
            ->get();

        $orderIds = $paidOrders->pluck('id');
        $ranking = OrderItem::query()
            ->selectRaw('product_name, SUM(quantity) as units')
            ->whereIn('order_id', $orderIds)
            ->groupBy('product_name')
            ->orderByDesc('units')
            ->orderBy('product_name')
            ->limit(10)
            ->get();

        $chart = [];
        foreach ($paidOrders as $order) {
            $date = $order->paid_at->toDateString();
            $chart[$date] ??= ['revenue_cents' => 0, 'units' => 0];
            $chart[$date]['revenue_cents'] += $order->total_cents;
            $chart[$date]['units'] += (int) $order->items_sum_quantity;
        }

        return [
            'revenue_cents' => (int) $paidOrders->sum('total_cents'),
            'units_sold' => (int) $paidOrders->sum('items_sum_quantity'),
            'pending_orders' => Order::query()
                ->where('fulfillment_status', 'pending')
                ->whereBetween('created_at', [$from, $to])
                ->count(),
            'ranking' => $ranking,
            'chart' => $chart,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Review;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareRestrictedNotifications
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $canOperate = $request->user()?->managesCatalog() === true;
        $pendingOrders = $canOperate ? Order::query()->where('fulfillment_status', 'pending')->count() : 0;
        $unreadMessages = $canOperate ? ContactMessage::query()->whereNull('read_at')->count() : 0;
        $pendingReviews = $canOperate ? Review::query()->where('status', 'pending')->count() : 0;

        View::share('pendingOrdersCount', $pendingOrders);
        View::share('unreadMessagesCount', $unreadMessages);
        View::share('pendingReviewsCount', $pendingReviews);

        return $next($request);
    }
}

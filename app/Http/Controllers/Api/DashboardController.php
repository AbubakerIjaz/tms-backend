<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesListingFilters;
use App\Models\Client;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ShopController
{
    use AppliesListingFilters;

    public function index(Request $request): JsonResponse
    {
        $shopId = $this->shopId($request);
        $hasDateRange = $request->query('from') || $request->query('to');

        $clientsQuery = Client::forShop($shopId);
        $this->applyDateRangeFilter($clientsQuery, $request, 'created_at');
        $totalClients = $clientsQuery->count();

        $pendingQuery = Order::forShop($shopId)->whereIn('status', ['pending', 'in_progress']);
        $this->applyDateRangeFilter($pendingQuery, $request, 'order_date');
        $pendingOrders = $pendingQuery->count();

        $readyQuery = Order::forShop($shopId)->where('status', 'ready');
        $this->applyDateRangeFilter($readyQuery, $request, 'order_date');
        $readyOrders = $readyQuery->count();

        $incomeQuery = Transaction::forShop($shopId)->where('type', 'income');
        $this->applyDateRangeFilter($incomeQuery, $request, 'transaction_date');
        $income = (float) $incomeQuery->sum('amount');

        $expenseQuery = Transaction::forShop($shopId)->where('type', 'expense');
        $this->applyDateRangeFilter($expenseQuery, $request, 'transaction_date');
        $expense = (float) $expenseQuery->sum('amount');

        $recentOrdersQuery = Order::forShop($shopId)
            ->with(['client:id,name,phone', 'design:id,name'])
            ->latest('order_date');
        $this->applyDateRangeFilter($recentOrdersQuery, $request, 'order_date');
        $recentOrders = $recentOrdersQuery->limit(5)->get();

        $upcomingDueQuery = Order::forShop($shopId)
            ->with(['client:id,name,phone'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date');

        if ($hasDateRange) {
            $this->applyDateRangeFilter($upcomingDueQuery, $request, 'due_date');
        } else {
            $upcomingDueQuery
                ->where('due_date', '>=', now()->toDateString())
                ->where('due_date', '<=', now()->addDays(7)->toDateString());
        }

        $upcomingDue = $upcomingDueQuery->orderBy('due_date')->limit(5)->get();

        return response()->json([
            'stats' => [
                'total_clients' => $totalClients,
                'pending_orders' => $pendingOrders,
                'ready_orders' => $readyOrders,
                'month_income' => $income,
                'month_expense' => $expense,
                'month_profit' => $income - $expense,
            ],
            'recent_orders' => $recentOrders,
            'upcoming_due' => $upcomingDue,
        ]);
    }
}

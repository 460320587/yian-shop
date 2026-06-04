<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\Invoice\Models\Invoice;
use App\Domains\Order\Models\Order;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends BaseController
{
    public function index(): JsonResponse
    {
        $today = now()->startOfDay();

        $todayOrders = Order::whereDate('created_at', $today)->count();
        $todaySales = Order::whereDate('created_at', $today)->sum('total_amount');
        $totalCustomers = Customer::count();
        $totalProducts = Product::count();
        $pendingAfterSales = AfterSale::where('status', 11)->count();
        $pendingInvoices = Invoice::where('status', 11)->count();

        $recentOrders = Order::with('customer')->latest()->limit(10)->get()->map(fn ($o) => [
            'id' => $o->id,
            'order_no' => $o->order_no,
            'customer_name' => $o->customer?->name ?? $o->customer?->phone ?? '未知',
            'total_amount' => $o->total_amount,
            'status' => $o->status,
            'customer_status' => $o->customer_status,
            'created_at' => $o->created_at?->toDateTimeString(),
        ]);

        $salesTrend = collect(range(6, 0))->map(function (int $daysAgo) {
            $date = now()->subDays($daysAgo)->startOfDay();
            $nextDate = $date->copy()->endOfDay();
            return [
                'date' => $date->toDateString(),
                'amount' => (int) Order::whereBetween('created_at', [$date, $nextDate])->sum('total_amount'),
                'count' => Order::whereBetween('created_at', [$date, $nextDate])->count(),
            ];
        })->values();

        return $this->success([
            'today_orders' => $todayOrders,
            'today_sales' => (int) $todaySales,
            'total_customers' => $totalCustomers,
            'total_products' => $totalProducts,
            'pending_after_sales' => $pendingAfterSales,
            'pending_invoices' => $pendingInvoices,
            'recent_orders' => $recentOrders,
            'sales_trend' => $salesTrend,
        ]);
    }
}

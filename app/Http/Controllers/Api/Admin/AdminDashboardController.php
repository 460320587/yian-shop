<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\Invoice\Models\Invoice;
use App\Domains\Order\Enums\OrderStatus;
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
        $yesterday = now()->subDay()->startOfDay();
        $yesterdayEnd = now()->subDay()->endOfDay();

        $todayOrders = Order::whereDate('created_at', $today)->count();
        $todaySales = Order::whereDate('created_at', $today)->sum('total_amount');
        $yesterdayOrders = Order::whereBetween('created_at', [$yesterday, $yesterdayEnd])->count();
        $yesterdaySales = Order::whereBetween('created_at', [$yesterday, $yesterdayEnd])->sum('total_amount');

        $totalCustomers = Customer::count();
        $totalProducts = Product::count();
        $pendingAfterSales = AfterSale::where('status', 11)->count();
        $pendingInvoices = Invoice::where('status', 11)->count();

        $orderStatusCounts = [
            'pending_payment' => Order::where('status', OrderStatus::PendingPayment->value)->count(),
            'paid' => Order::where('status', OrderStatus::Paid->value)->count(),
            'in_production' => Order::where('status', OrderStatus::InProduction->value)->count(),
            'pending_delivery' => Order::where('status', OrderStatus::PendingDelivery->value)->count(),
            'shipped' => Order::where('status', OrderStatus::Shipped->value)->count(),
            'pending_receive' => Order::where('status', OrderStatus::PendingReceive->value)->count(),
            'completed' => Order::where('status', OrderStatus::Completed->value)->count(),
        ];

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
            'yesterday_orders' => $yesterdayOrders,
            'yesterday_sales' => (int) $yesterdaySales,
            'total_customers' => $totalCustomers,
            'total_products' => $totalProducts,
            'pending_after_sales' => $pendingAfterSales,
            'pending_invoices' => $pendingInvoices,
            'order_status_counts' => $orderStatusCounts,
            'recent_orders' => $recentOrders,
            'sales_trend' => $salesTrend,
        ]);
    }
}

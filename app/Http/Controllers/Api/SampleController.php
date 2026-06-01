<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Product\Models\Product;
use App\Domains\Sample\Models\SampleOrder;
use App\Domains\User\Models\Customer;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SampleController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = SampleOrder::with('product')
            ->where('customer_id', auth('sanctum')->id())
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        return $this->paginated($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
            'address_snapshot' => 'nullable|array',
            'remark' => 'nullable|string|max:500',
        ]);

        $customer = auth('sanctum')->user();
        $product = Product::findOrFail($validated['product_id']);

        if ($product->status !== 1) {
            return $this->error(ErrorCode::PRODUCT_OUT_OF_STOCK, '商品已下架', null, 422);
        }

        $quantity = $validated['quantity'];
        $unitPrice = $product->price_min;
        $discount = $this->calculateDiscount($customer, $unitPrice, $quantity);
        $total = $unitPrice->multiply($quantity)->subtract($discount);

        $order = SampleOrder::create([
            'customer_id' => $customer->id,
            'order_no' => $this->generateOrderNo(),
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'status' => 100,
            'address_snapshot' => $validated['address_snapshot'] ?? null,
            'remark' => $validated['remark'] ?? null,
        ]);

        return $this->success($order, '样品订单创建成功', 201);
    }

    public function show(int $id): JsonResponse
    {
        $order = SampleOrder::with('product')
            ->where('customer_id', auth('sanctum')->id())
            ->find($id);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在', null, 404);
        }

        return $this->success($order);
    }

    private function generateOrderNo(): string
    {
        return 'S' . now()->format('Ymd') . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function calculateDiscount(Customer $customer, $unitPrice, int $quantity): mixed
    {
        $level = $customer->vip_level;
        $discountRate = match (true) {
            $level >= 1 && $level <= 3 => 0.10,
            $level === 4 => 0.20,
            $level >= 5 => 0.50,
            default => 0.0,
        };

        if ($discountRate <= 0) {
            return new \App\Domains\Common\ValueObjects\Money(0);
        }

        $subtotal = $unitPrice->multiply($quantity);
        return $subtotal->multiply($discountRate);
    }
}

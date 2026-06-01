<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Product\Models\Product;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;

class CartController extends BaseController
{
    public function index(): JsonResponse
    {
        $cart = $this->getOrCreateCart();
        $items = $cart->items()->with('product:id,name,cover_image')->get();

        $selectedItems = $items->where('selected', 1);

        return $this->success([
            'items' => $items->map(fn (CartItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'thumbnail' => $item->product?->cover_image,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price / 100,
                'subtotal' => $item->subtotal / 100,
                'selected' => (bool) $item->selected,
                'spec_info' => $item->spec_info,
            ])->all(),
            'summary' => [
                'total_count' => $items->count(),
                'selected_count' => $selectedItems->count(),
                'selected_subtotal' => $selectedItems->sum('subtotal') / 100,
            ],
        ]);
    }

    public function store(AddCartItemRequest $request): JsonResponse
    {
        $product = Product::where('status', 1)->find($request->product_id);
        if (! $product) {
            return $this->error(ErrorCode::PRODUCT_NOT_FOUND);
        }

        $cart = $this->getOrCreateCart();
        $unitPrice = $product->price_min->amount;
        $subtotal = $unitPrice * $request->quantity;

        $item = $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'selected' => 1,
        ]);

        $this->updateCartSummary($cart);

        return $this->success([
            'id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price / 100,
            'subtotal' => $item->subtotal / 100,
            'selected' => true,
        ], '已加入购物车', 201);
    }

    public function update(UpdateCartItemRequest $request, int $id): JsonResponse
    {
        $cart = $this->getOrCreateCart();
        $item = $cart->items()->find($id);

        if (! $item) {
            return $this->error(ErrorCode::FORBIDDEN, '无权操作该购物车项', null, 403);
        }

        if ($request->has('quantity')) {
            $item->quantity = $request->input('quantity');
            $item->subtotal = $item->unit_price * $item->quantity;
        }

        if ($request->has('selected')) {
            $item->selected = $request->boolean('selected') ? 1 : 0;
        }

        $item->save();
        $this->updateCartSummary($cart);

        return $this->success([
            'id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price / 100,
            'subtotal' => $item->subtotal / 100,
            'selected' => (bool) $item->selected,
        ]);
    }

    public function destroyItem(int $id): JsonResponse
    {
        $cart = $this->getOrCreateCart();
        $item = $cart->items()->find($id);

        if (! $item) {
            return $this->error(ErrorCode::FORBIDDEN, '无权操作该购物车项', null, 403);
        }

        $item->forceDelete();
        $this->updateCartSummary($cart);

        return $this->success([], '删除成功');
    }

    public function clear(): JsonResponse
    {
        $cart = $this->getOrCreateCart();
        $cart->items()->forceDelete();
        $this->updateCartSummary($cart);

        return $this->success([], '购物车已清空');
    }

    private function getOrCreateCart(): Cart
    {
        $customerId = auth('sanctum')->id();
        return Cart::firstOrCreate(
            ['customer_id' => $customerId],
            ['total_count' => 0, 'selected_subtotal' => 0]
        );
    }

    private function updateCartSummary(Cart $cart): void
    {
        $items = $cart->items()->get();
        $cart->total_count = $items->count();
        $cart->selected_subtotal = $items->where('selected', 1)->sum('subtotal');
        $cart->save();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Product\Models\ProductReview;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends BaseController
{
    public function indexByProduct(Request $request, int $productId): JsonResponse
    {
        $query = ProductReview::with('customer')
            ->where('product_id', $productId)
            ->where('is_show', true)
            ->orderBy('created_at', 'desc');

        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->paginate($perPage);

        return $this->paginated($paginator);
    }

    public function store(StoreReviewRequest $request, int $orderId): JsonResponse
    {
        $customerId = auth('sanctum')->id();
        $order = Order::where('customer_id', $customerId)->find($orderId);

        if (! $order) {
            return $this->error(ErrorCode::FORBIDDEN, '无权访问该订单', null, 403);
        }

        if ((int) $order->status !== OrderStatus::Completed->value) {
            return $this->error(ErrorCode::ORDER_STATUS_INVALID, '订单未完成，无法评价', null, 422);
        }

        $productId = (int) $request->input('product_id');
        $hasItem = $order->items()->where('product_id', $productId)->exists();

        if (! $hasItem) {
            return $this->error(ErrorCode::FORBIDDEN, '该订单不包含此商品', null, 403);
        }

        $existing = ProductReview::where('customer_id', $customerId)
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            return $this->error(ErrorCode::BAD_REQUEST, '该商品已评价', null, 400);
        }

        $review = ProductReview::create([
            'customer_id' => $customerId,
            'product_id' => $productId,
            'order_id' => $orderId,
            'rating' => (int) $request->input('rating'),
            'content' => $request->input('content'),
            'images' => $request->input('images'),
            'is_show' => true,
        ]);

        return $this->success($this->formatReview($review), '评价成功', 201);
    }

    public function myReviews(Request $request): JsonResponse
    {
        $query = ProductReview::with('product')
            ->where('customer_id', auth('sanctum')->id())
            ->orderBy('created_at', 'desc');

        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->paginate($perPage);

        return $this->paginated($paginator);
    }

    private function formatReview(ProductReview $review): array
    {
        return [
            'id' => $review->id,
            'customer_id' => $review->customer_id,
            'product_id' => $review->product_id,
            'order_id' => $review->order_id,
            'rating' => $review->rating,
            'content' => $review->content,
            'images' => $review->images,
            'reply' => $review->reply,
            'reply_at' => $review->reply_at,
            'is_show' => $review->is_show,
            'created_at' => $review->created_at,
            'customer' => $review->customer ? [
                'id' => $review->customer->id,
                'nickname' => $review->customer->nickname,
            ] : null,
        ];
    }
}

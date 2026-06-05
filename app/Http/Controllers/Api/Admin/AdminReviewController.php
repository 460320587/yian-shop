<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Product\Models\ProductReview;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = ProductReview::with(['customer', 'product']);

        if ($request->has('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        if ($request->has('is_show')) {
            $query->where('is_show', (bool) $request->input('is_show'));
        }

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where('content', 'like', "%{$keyword}%");
        }

        return $this->paginated($query->orderBy('created_at', 'desc')->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $review = ProductReview::with(['customer', 'product', 'order'])->find($id);

        if (! $review) {
            return $this->error(ErrorCode::NOT_FOUND, '评价不存在');
        }

        return $this->success([
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
            'product' => $review->product ? [
                'id' => $review->product->id,
                'name' => $review->product->name,
            ] : null,
        ]);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $review = ProductReview::find($id);

        if (! $review) {
            return $this->error(ErrorCode::NOT_FOUND, '评价不存在');
        }

        $data = $request->validate([
            'reply' => ['required', 'string', 'max:500'],
        ]);

        $review->update([
            'reply' => $data['reply'],
            'reply_at' => now(),
        ]);

        return $this->success([
            'id' => $review->id,
            'reply' => $review->reply,
            'reply_at' => $review->reply_at,
        ], '回复成功');
    }

    public function toggleShow(int $id): JsonResponse
    {
        $review = ProductReview::find($id);

        if (! $review) {
            return $this->error(ErrorCode::NOT_FOUND, '评价不存在');
        }

        $review->update(['is_show' => ! $review->is_show]);

        return $this->success(['is_show' => $review->fresh()->is_show]);
    }

    public function destroy(int $id): JsonResponse
    {
        $review = ProductReview::find($id);

        if (! $review) {
            return $this->error(ErrorCode::NOT_FOUND, '评价不存在');
        }

        $review->delete();

        return $this->success([], '删除成功');
    }
}

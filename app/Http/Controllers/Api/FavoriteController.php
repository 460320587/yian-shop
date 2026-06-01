<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Product\Models\CustomerFavorite;
use App\Domains\Product\Models\Product;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = CustomerFavorite::with('product.category')
            ->where('customer_id', auth('sanctum')->id())
            ->where('status', 1)
            ->orderBy('created_at', 'desc');

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->paginate($perPage);

        return $this->paginated($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'remark' => 'nullable|string|max:200',
        ]);

        $customerId = auth('sanctum')->id();

        $favorite = CustomerFavorite::updateOrCreate(
            [
                'customer_id' => $customerId,
                'product_id' => $validated['product_id'],
            ],
            [
                'remark' => $validated['remark'] ?? null,
                'status' => 1,
            ]
        );

        return $this->success($favorite, $favorite->wasRecentlyCreated ? '收藏成功' : '收藏已更新');
    }

    public function destroy(int $id): JsonResponse
    {
        $favorite = CustomerFavorite::where('customer_id', auth('sanctum')->id())->find($id);

        if (! $favorite) {
            return $this->error(ErrorCode::NOT_FOUND, '收藏不存在', null, 404);
        }

        $favorite->delete();

        return $this->success([], '取消收藏成功');
    }
}

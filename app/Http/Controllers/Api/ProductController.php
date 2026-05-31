<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Product\Models\Product;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::where('status', 1)
            ->with('category:id,name');

        // 分类筛选
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        // 关键词搜索
        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->input('keyword') . '%');
        }

        // 价格范围筛选
        if ($request->filled('min_price')) {
            $query->where('price_min', '>=', (int) ($request->input('min_price') * 100));
        }
        if ($request->filled('max_price')) {
            $query->where('price_max', '<=', (int) ($request->input('max_price') * 100));
        }

        // 排序
        $sort = $request->input('sort', 'default');
        match ($sort) {
            'price_asc' => $query->orderBy('price_min', 'asc'),
            'price_desc' => $query->orderBy('price_min', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('sort', 'asc')->orderBy('id', 'desc'),
        };

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->paginate($perPage);

        return $this->paginated($paginator);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::where('status', 1)
            ->with('category:id,name')
            ->find($id);

        if (! $product) {
            return $this->error(ErrorCode::PRODUCT_NOT_FOUND);
        }

        return $this->success([
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'cover_image' => $product->cover_image,
            'price_min' => $product->price_min,
            'price_max' => $product->price_max,
            'status' => $product->status,
            'sort' => $product->sort,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ] : null,
        ]);
    }
}

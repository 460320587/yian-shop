<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Product\Models\Product;
use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category');

        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword): void {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        $products = $query->latest()->paginate($request->input('per_page', 10));

        return $this->success([
            'data' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'thumbnail' => $p->thumbnail,
                'price_min' => $p->price_min,
                'price_max' => $p->price_max,
                'status' => $p->status,
                'sales_count' => $p->sales_count,
                'is_hot' => $p->is_hot,
                'is_new' => $p->is_new,
                'category' => ['id' => $p->category_id, 'name' => $p->category?->name],
            ]),
            'total' => $products->total(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with('category')->findOrFail($id);

        return $this->success([
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'description' => $product->description,
            'thumbnail' => $product->thumbnail,
            'cover_image' => $product->cover_image,
            'price_min' => $product->price_min,
            'price_max' => $product->price_max,
            'status' => $product->status,
            'sales_count' => $product->sales_count,
            'is_hot' => $product->is_hot,
            'is_new' => $product->is_new,
            'sort' => $product->sort,
            'category' => ['id' => $product->category_id, 'name' => $product->category?->name],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => 'required|integer|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:products',
            'price_min' => 'required|integer|min:0',
            'price_max' => 'required|integer|min:0',
            'status' => 'nullable|integer|in:0,1,2',
        ]);

        $product = Product::create($data + ['sales_count' => 0]);

        return $this->success([
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'price_min' => $product->price_min,
            'price_max' => $product->price_max,
            'status' => $product->status,
        ], '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:100|unique:products,code,' . $product->id,
            'price_min' => 'nullable|integer|min:0',
            'price_max' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|in:0,1,2',
            'description' => 'nullable|string',
        ]);

        $product->update(array_filter($data, fn ($v) => $v !== null));

        return $this->success([], '更新成功');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update(['status' => $product->status === 1 ? 0 : 1]);

        return $this->success(['status' => $product->fresh()->status]);
    }
}

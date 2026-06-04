<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Product\Models\Product;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Product\CalculatePriceRequest;
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

    public function params(int $id): JsonResponse
    {
        $product = Product::where('status', 1)->find($id);

        if (! $product) {
            return $this->error(ErrorCode::PRODUCT_NOT_FOUND);
        }

        return $this->success([
            'product_id' => $product->id,
            'pricing_params' => $product->pricing_params ?? [],
        ]);
    }

    public function price(CalculatePriceRequest $request, int $id): JsonResponse
    {
        $product = Product::where('status', 1)->find($id);

        if (! $product) {
            return $this->error(ErrorCode::PRODUCT_NOT_FOUND);
        }

        $params = $product->pricing_params ?? [];
        $quantity = (int) $request->input('quantity');
        $paperId = (int) $request->input('paper_id');
        $colorId = (int) $request->input('color_id');
        $processIds = $request->input('process_ids', []);

        $priceTiers = $params['price_tiers'] ?? [];
        $paperOptions = $params['paper_options'] ?? [];
        $colorOptions = $params['color_options'] ?? [];
        $processOptions = $params['process_options'] ?? [];

        // 找到对应的 price tier
        $tierPrice = null;
        foreach (array_reverse($priceTiers) as $tier) {
            if ($quantity >= $tier['min_qty']) {
                $tierPrice = (int) $tier['price'];
                break;
            }
        }
        if ($tierPrice === null) {
            $tierPrice = (int) ($params['base_price'] ?? 0);
        }

        // 找到 paper factor
        $paperFound = false;
        $paperFactor = 1.0;
        foreach ($paperOptions as $option) {
            if ($option['id'] === $paperId) {
                $paperFactor = (float) $option['price_factor'];
                $paperFound = true;
                break;
            }
        }
        if (! $paperFound && ! empty($paperOptions)) {
            return $this->error(ErrorCode::VALIDATION_ERROR, '无效的纸张选项');
        }

        // 找到 color factor
        $colorFound = false;
        $colorFactor = 1.0;
        foreach ($colorOptions as $option) {
            if ($option['id'] === $colorId) {
                $colorFactor = (float) $option['price_factor'];
                $colorFound = true;
                break;
            }
        }
        if (! $colorFound && ! empty($colorOptions)) {
            return $this->error(ErrorCode::VALIDATION_ERROR, '无效的颜色选项');
        }

        // 计算单价（分）
        $unitPrice = (int) round($tierPrice * $paperFactor * $colorFactor);
        $baseAmount = $unitPrice * $quantity;

        // 计算工艺费用
        $processAmount = 0;
        foreach ($processIds as $pid) {
            foreach ($processOptions as $option) {
                if ($option['id'] === $pid) {
                    $processAmount += (int) ($option['price'] ?? 0);
                    break;
                }
            }
        }

        $totalAmount = $baseAmount + $processAmount;

        return $this->success([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice / 100,
            'breakdown' => [
                'base_amount' => $baseAmount / 100,
                'process_amount' => $processAmount / 100,
                'total_amount' => $totalAmount / 100,
            ],
        ]);
    }
}

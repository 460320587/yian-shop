<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Product\Models\ProductCategory;
use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    public function tree(Request $request): JsonResponse
    {
        $depth = (int) $request->input('depth', 2);
        $depth = max(1, min($depth, 3));

        $categories = ProductCategory::where('status', 1)
            ->where('parent_id', 0)
            ->orderBy('sort')
            ->get();

        $tree = $categories->map(fn (ProductCategory $category) => $this->buildNode($category, $depth, 1));

        return $this->success($tree->all());
    }

    private function buildNode(ProductCategory $category, int $maxDepth, int $currentDepth): array
    {
        $node = [
            'id' => $category->id,
            'name' => $category->name,
            'icon' => $category->icon,
            'sort' => $category->sort,
        ];

        if ($currentDepth < $maxDepth) {
            $children = ProductCategory::where('status', 1)
                ->where('parent_id', $category->id)
                ->orderBy('sort')
                ->get();

            $node['children'] = $children->map(
                fn (ProductCategory $child) => $this->buildNode($child, $maxDepth, $currentDepth + 1)
            )->all();
        }

        return $node;
    }
}

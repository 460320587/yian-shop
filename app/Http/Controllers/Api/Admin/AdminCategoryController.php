<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Product\Models\ProductCategory;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminCategoryController extends BaseController
{
    public function index(): JsonResponse
    {
        $categories = ProductCategory::orderBy('sort')->orderBy('id')->get();

        return $this->success($categories->map(fn (ProductCategory $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'parent_id' => $c->parent_id,
            'icon' => $c->icon,
            'sort' => $c->sort,
            'status' => $c->status,
            'level' => $c->level,
            'path' => $c->path,
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'min:0'],
            'icon' => ['nullable', 'string', 'max:200'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);

        $parentId = (int) ($data['parent_id'] ?? 0);
        $level = 1;
        $path = '';

        if ($parentId > 0) {
            $parent = ProductCategory::find($parentId);
            if (! $parent) {
                return $this->error(ErrorCode::NOT_FOUND, '父分类不存在');
            }
            $level = $parent->level + 1;
            $path = $parent->path === '' ? (string) $parent->id : $parent->path . ',' . $parent->id;
        }

        $category = ProductCategory::create([
            'name' => $data['name'],
            'parent_id' => $parentId,
            'icon' => $data['icon'] ?? null,
            'sort' => $data['sort'] ?? 0,
            'status' => 1,
            'level' => $level,
            'path' => $path,
        ]);

        return $this->success([
            'id' => $category->id,
            'name' => $category->name,
            'parent_id' => $category->parent_id,
            'icon' => $category->icon,
            'sort' => $category->sort,
            'status' => $category->status,
            'level' => $category->level,
            'path' => $category->path,
        ], '创建成功', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = ProductCategory::find($id);
        if (! $category) {
            return $this->error(ErrorCode::NOT_FOUND, '分类不存在');
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:200'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);

        $updateData = array_filter($data, fn ($v) => $v !== null);
        $category->update($updateData);

        return $this->success([], '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $category = ProductCategory::find($id);
        if (! $category) {
            return $this->error(ErrorCode::NOT_FOUND, '分类不存在');
        }

        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => ['该分类下存在商品，无法删除'],
            ]);
        }

        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => ['该分类下存在子分类，无法删除'],
            ]);
        }

        $category->delete();

        return $this->success([], '删除成功');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $category = ProductCategory::find($id);
        if (! $category) {
            return $this->error(ErrorCode::NOT_FOUND, '分类不存在');
        }

        $category->update(['status' => $category->status === 1 ? 0 : 1]);

        return $this->success(['status' => $category->fresh()->status]);
    }
}

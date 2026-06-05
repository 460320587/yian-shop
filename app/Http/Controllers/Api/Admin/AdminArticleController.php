<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Content\Models\Article;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminArticleController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Article::query();

        if ($request->has('type')) {
            $query->where('type', (int) $request->input('type'));
        }

        if ($request->has('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('keyword')) {
            $query->where('title', 'like', '%' . $request->input('keyword') . '%');
        }

        return $this->paginated($query->orderBy('sort')->orderBy('id', 'desc')->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $article = Article::find($id);
        if (! $article) {
            return $this->error(ErrorCode::NOT_FOUND, '文章不存在');
        }

        return $this->success([
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'type' => $article->type,
            'content' => $article->content,
            'summary' => $article->summary,
            'cover' => $article->cover,
            'author' => $article->author,
            'view_count' => $article->view_count,
            'sort' => $article->sort,
            'status' => $article->status,
            'published_at' => $article->published_at,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200', 'unique:articles'],
            'type' => ['required', 'integer', 'in:1,2,3,4'],
            'content' => ['required', 'string'],
            'summary' => ['nullable', 'string', 'max:500'],
            'cover' => ['nullable', 'string', 'max:500'],
            'author' => ['nullable', 'string', 'max:50'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'integer', 'in:0,1,2'],
        ]);

        $article = Article::create($data + ['view_count' => 0]);

        return $this->success([
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
        ], '创建成功', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $article = Article::find($id);
        if (! $article) {
            return $this->error(ErrorCode::NOT_FOUND, '文章不存在');
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'unique:articles,slug,' . $article->id],
            'type' => ['nullable', 'integer', 'in:1,2,3,4'],
            'content' => ['nullable', 'string'],
            'summary' => ['nullable', 'string', 'max:500'],
            'cover' => ['nullable', 'string', 'max:500'],
            'author' => ['nullable', 'string', 'max:50'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'integer', 'in:0,1,2'],
        ]);

        $article->update(array_filter($data, fn ($v) => $v !== null));

        return $this->success([], '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $article = Article::find($id);
        if (! $article) {
            return $this->error(ErrorCode::NOT_FOUND, '文章不存在');
        }

        $article->delete();

        return $this->success([], '删除成功');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $article = Article::find($id);
        if (! $article) {
            return $this->error(ErrorCode::NOT_FOUND, '文章不存在');
        }

        $article->update(['status' => $article->status === 1 ? 0 : 1]);

        return $this->success(['status' => $article->fresh()->status]);
    }
}

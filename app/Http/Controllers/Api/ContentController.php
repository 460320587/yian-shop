<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Content\Models\Article;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends BaseController
{
    public function articles(Request $request): JsonResponse
    {
        $query = Article::where('status', 1);

        if ($request->has('type')) {
            $query->where('type', (int) $request->input('type'));
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->orderBy('sort')->orderBy('id', 'desc')->paginate($perPage);

        return $this->paginated($paginator);
    }

    public function show(string $slug): JsonResponse
    {
        $article = Article::where('slug', $slug)->where('status', 1)->first();

        if (! $article) {
            return $this->error(ErrorCode::NOT_FOUND, '文章不存在');
        }

        $article->increment('view_count');

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
            'published_at' => $article->published_at,
            'created_at' => $article->created_at,
        ]);
    }
}

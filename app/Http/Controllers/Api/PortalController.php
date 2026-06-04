<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Portal\Models\Announcement;
use App\Domains\Portal\Models\Banner;
use App\Domains\Product\Models\Product;
use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalController extends BaseController
{
    public function banners(Request $request): JsonResponse
    {
        $position = $request->input('position', 'home');
        $limit = (int) $request->input('limit', 5);

        $banners = Banner::ofPosition($position)
            ->visible()
            ->orderBy('sort')
            ->limit($limit)
            ->get();

        return $this->success($banners->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'image' => $b->image,
            'image_mobile' => $b->image_mobile,
            'link_type' => $b->link_type,
            'link_target' => $b->link_target,
            'sort' => $b->sort,
        ]));
    }

    public function announcements(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $limit = (int) $request->input('limit', 5);

        $query = Announcement::visible();
        if ($type) {
            $query->ofType($type);
        }

        $announcements = $query->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $this->success($announcements->map(fn ($a) => [
            'id' => $a->id,
            'title' => $a->title,
            'content' => $a->content,
            'type' => $a->type,
            'is_popup' => $a->is_popup,
            'created_at' => $a->created_at?->toDateTimeString(),
        ]));
    }

    public function hotProducts(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);

        $products = Product::active()
            ->where('is_hot', 1)
            ->orderByDesc('sales_count')
            ->limit($limit)
            ->get();

        return $this->success($this->mapProducts($products));
    }

    public function newArrivals(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);

        $products = Product::active()
            ->where('is_new', 1)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $this->success($this->mapProducts($products));
    }

    public function home(): JsonResponse
    {
        $banners = Banner::ofPosition('home')
            ->visible()
            ->orderBy('sort')
            ->limit(5)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'image' => $b->image,
                'image_mobile' => $b->image_mobile,
                'link_type' => $b->link_type,
                'link_target' => $b->link_target,
                'sort' => $b->sort,
            ]);

        $announcements = Announcement::visible()
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'type' => $a->type,
                'is_popup' => $a->is_popup,
            ]);

        $hotProducts = Product::active()
            ->where('is_hot', 1)
            ->orderByDesc('sales_count')
            ->limit(6)
            ->get();

        $newArrivals = Product::active()
            ->where('is_new', 1)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return $this->success([
            'banners' => $banners,
            'announcements' => $announcements,
            'hot_products' => $this->mapProducts($hotProducts),
            'new_arrivals' => $this->mapProducts($newArrivals),
        ]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, Product> $products
     * @return array<int, array<string, mixed>>
     */
    private function mapProducts($products): array
    {
        return $products->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'thumbnail' => $p->thumbnail,
            'min_price' => $p->price_min?->toYuan(),
            'max_price' => $p->price_max?->toYuan(),
            'sales_count' => $p->sales_count,
            'is_hot' => $p->is_hot,
            'is_new' => $p->is_new,
            'category' => [
                'id' => $p->category_id,
                'name' => $p->category?->name,
            ],
        ])->all();
    }
}

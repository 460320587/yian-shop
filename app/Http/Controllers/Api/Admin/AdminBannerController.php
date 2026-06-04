<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Portal\Models\Announcement;
use App\Domains\Portal\Models\Banner;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBannerController extends BaseController
{
    // ========== Banner ==========

    public function bannerIndex(): JsonResponse
    {
        $banners = Banner::orderBy('sort')->paginate();
        return $this->paginated($banners);
    }

    public function bannerStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:200',
            'image' => 'required|string|max:500',
            'image_mobile' => 'nullable|string|max:500',
            'link_type' => 'required|string|in:product,category,url',
            'link_target' => 'required|string|max:500',
            'position' => 'required|string|max:50',
            'sort' => 'nullable|integer|min:0',
            'display_start' => 'nullable|date',
            'display_end' => 'nullable|date',
            'status' => 'required|integer|in:0,1',
        ]);

        $banner = Banner::create($data);
        return $this->success($banner, 'Banner 已创建', 201);
    }

    public function bannerUpdate(Request $request, int $id): JsonResponse
    {
        $banner = Banner::find($id);
        if (! $banner) {
            return $this->error(ErrorCode::NOT_FOUND, 'Banner 不存在');
        }

        $data = $request->validate([
            'title' => 'nullable|string|max:200',
            'image' => 'sometimes|string|max:500',
            'image_mobile' => 'nullable|string|max:500',
            'link_type' => 'sometimes|string|in:product,category,url',
            'link_target' => 'sometimes|string|max:500',
            'position' => 'sometimes|string|max:50',
            'sort' => 'nullable|integer|min:0',
            'display_start' => 'nullable|date',
            'display_end' => 'nullable|date',
            'status' => 'sometimes|integer|in:0,1',
        ]);

        $banner->update($data);
        return $this->success($banner, 'Banner 已更新');
    }

    public function bannerDestroy(int $id): JsonResponse
    {
        $banner = Banner::find($id);
        if (! $banner) {
            return $this->error(ErrorCode::NOT_FOUND, 'Banner 不存在');
        }

        $banner->delete();
        return $this->success([], 'Banner 已删除');
    }

    // ========== Announcement ==========

    public function announcementIndex(): JsonResponse
    {
        $announcements = Announcement::orderBy('created_at', 'desc')->paginate();
        return $this->paginated($announcements);
    }

    public function announcementStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'type' => 'required|string|in:general,legality,promotion',
            'is_popup' => 'nullable|integer|in:0,1',
            'display_start' => 'nullable|date',
            'display_end' => 'nullable|date',
            'status' => 'required|integer|in:0,1',
        ]);

        $announcement = Announcement::create($data);
        return $this->success($announcement, '公告已创建', 201);
    }

    public function announcementUpdate(Request $request, int $id): JsonResponse
    {
        $announcement = Announcement::find($id);
        if (! $announcement) {
            return $this->error(ErrorCode::NOT_FOUND, '公告不存在');
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:200',
            'content' => 'sometimes|string',
            'type' => 'sometimes|string|in:general,legality,promotion',
            'is_popup' => 'nullable|integer|in:0,1',
            'display_start' => 'nullable|date',
            'display_end' => 'nullable|date',
            'status' => 'sometimes|integer|in:0,1',
        ]);

        $announcement->update($data);
        return $this->success($announcement, '公告已更新');
    }

    public function announcementDestroy(int $id): JsonResponse
    {
        $announcement = Announcement::find($id);
        if (! $announcement) {
            return $this->error(ErrorCode::NOT_FOUND, '公告不存在');
        }

        $announcement->delete();
        return $this->success([], '公告已删除');
    }
}

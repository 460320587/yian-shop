<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Notification\Models\CustomerNotification;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = CustomerNotification::where('customer_id', auth('sanctum')->id());

        if ($request->has('is_read')) {
            $query->where('is_read', (int) $request->input('is_read'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->paginate($perPage);

        return $this->paginated($paginator);
    }

    public function markRead(int $id): JsonResponse
    {
        $notification = CustomerNotification::where('customer_id', auth('sanctum')->id())->find($id);

        if (! $notification) {
            return $this->error(ErrorCode::FORBIDDEN, '无权操作该通知', null, 403);
        }

        $notification->update(['is_read' => 1]);

        return $this->success([], '标记已读');
    }

    public function markAllRead(): JsonResponse
    {
        CustomerNotification::where('customer_id', auth('sanctum')->id())
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return $this->success([], '全部已读');
    }

    public function unreadCount(): JsonResponse
    {
        $count = CustomerNotification::where('customer_id', auth('sanctum')->id())
            ->where('is_read', 0)
            ->count();

        return $this->success(['unread_count' => $count]);
    }

    public function destroy(int $id): JsonResponse
    {
        $notification = CustomerNotification::where('customer_id', auth('sanctum')->id())->find($id);

        if (! $notification) {
            return $this->error(ErrorCode::FORBIDDEN, '无权操作该通知', null, 403);
        }

        $notification->forceDelete();

        return $this->success([], '删除成功');
    }
}

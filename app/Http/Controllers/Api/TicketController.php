<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Order\Models\Order;
use App\Domains\Ticket\Models\Ticket;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::where('customer_id', auth('sanctum')->id());

        if ($request->has('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', (int) $request->input('type'));
        }

        return $this->paginated($query->orderBy('created_at', 'desc')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => 'nullable|integer|exists:orders,id',
            'type' => 'required|integer|in:1,2,3,4,5',
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'images' => 'nullable|array',
            'expected_resolution' => 'nullable|string|max:255',
        ]);

        $customerId = auth('sanctum')->id();

        if (! empty($data['order_id'])) {
            $order = Order::where('id', $data['order_id'])->where('customer_id', $customerId)->first();
            if (! $order) {
                return $this->error(ErrorCode::FORBIDDEN, '无权操作该订单');
            }
        }

        $ticket = Ticket::create([
            'customer_id' => $customerId,
            'order_id' => $data['order_id'] ?? null,
            'ticket_no' => 'TK' . now()->format('Ymd') . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'type' => $data['type'],
            'status' => 1,
            'priority' => 3,
            'title' => $data['title'],
            'content' => $data['content'],
            'images' => $data['images'] ?? [],
            'expected_resolution' => $data['expected_resolution'] ?? null,
        ]);

        return $this->success($ticket->load('order'), '工单已提交', 201);
    }

    public function show(int $id): JsonResponse
    {
        $ticket = Ticket::where('customer_id', auth('sanctum')->id())
            ->with('order')
            ->find($id);

        if (! $ticket) {
            return $this->error(ErrorCode::NOT_FOUND, '工单不存在');
        }

        return $this->success($ticket);
    }

    public function cancel(int $id): JsonResponse
    {
        $ticket = Ticket::where('customer_id', auth('sanctum')->id())->find($id);
        if (! $ticket) {
            return $this->error(ErrorCode::NOT_FOUND, '工单不存在');
        }

        if ($ticket->status !== 1) {
            return $this->error(ErrorCode::ORDER_STATUS_INVALID, '当前状态不可撤诉', null, 400);
        }

        $ticket->update(['status' => 0]);

        return $this->success([], '工单已撤诉');
    }
}

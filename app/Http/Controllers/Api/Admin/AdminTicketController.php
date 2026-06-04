<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Ticket\Models\Ticket;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTicketController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::with('customer');

        if ($request->has('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', (int) $request->input('type'));
        }

        if ($request->has('priority')) {
            $query->where('priority', (int) $request->input('priority'));
        }

        return $this->paginated($query->orderBy('created_at', 'desc')->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $ticket = Ticket::with('customer', 'order')->find($id);
        if (! $ticket) {
            return $this->error(ErrorCode::NOT_FOUND, '工单不存在');
        }

        return $this->success($ticket);
    }

    public function process(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::find($id);
        if (! $ticket) {
            return $this->error(ErrorCode::NOT_FOUND, '工单不存在');
        }

        $data = $request->validate([
            'status' => 'required|integer|in:2,3,4,5',
            'remark' => 'nullable|string',
            'priority' => 'nullable|integer|in:1,2,3,4',
        ]);

        $update = [
            'status' => $data['status'],
            'remark' => $data['remark'] ?? $ticket->remark,
        ];

        if (! empty($data['priority'])) {
            $update['priority'] = $data['priority'];
        }

        if ($data['status'] === 2 && $ticket->status === 1) {
            $update['processed_by'] = auth('admin')->id();
            $update['processed_at'] = now();
        }

        if ($data['status'] === 4) {
            $update['completed_at'] = now();
        }

        $ticket->update($update);

        return $this->success($ticket, '工单已更新');
    }
}

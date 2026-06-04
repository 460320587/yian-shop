<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Audit\Models\AuditLog;
use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query();

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }
        if ($adminId = $request->input('admin_id')) {
            $query->where('admin_id', $adminId);
        }
        if ($modelType = $request->input('model_type')) {
            $query->where('model_type', $modelType);
        }
        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword): void {
                $q->where('admin_name', 'like', "%{$keyword}%")
                  ->orWhere('remark', 'like', "%{$keyword}%");
            });
        }

        $logs = $query->latest()->paginate($request->input('per_page', 10));

        return $this->success([
            'data' => $logs->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'admin_id' => $log->admin_id,
                'admin_name' => $log->admin_name,
                'action' => $log->action,
                'model_type' => $log->model_type,
                'model_id' => $log->model_id,
                'ip' => $log->ip,
                'result' => $log->result,
                'created_at' => $log->created_at?->toDateTimeString(),
            ]),
            'total' => $logs->total(),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $log = AuditLog::findOrFail($id);

        return $this->success([
            'id' => $log->id,
            'admin_id' => $log->admin_id,
            'admin_name' => $log->admin_name,
            'action' => $log->action,
            'model_type' => $log->model_type,
            'model_id' => $log->model_id,
            'before_data' => $log->before_data,
            'after_data' => $log->after_data,
            'ip' => $log->ip,
            'user_agent' => $log->user_agent,
            'result' => $log->result,
            'remark' => $log->remark,
            'created_at' => $log->created_at?->toDateTimeString(),
        ]);
    }
}

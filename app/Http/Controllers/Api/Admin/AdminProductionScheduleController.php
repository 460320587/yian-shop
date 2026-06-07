<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Order\Actions\CreateProductionScheduleAction;
use App\Domains\Order\Actions\UpdateProductionScheduleAction;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\ProductionSchedule;
use App\Domains\Order\Queries\ProductionScheduleQuery;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductionScheduleController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = (new ProductionScheduleQuery($request->all()))
            ->with(['order'])
            ->perPage((int) $request->input('per_page', 15));

        return $this->paginated($query->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $schedule = ProductionSchedule::with('order')->find($id);

        if (! $schedule) {
            return $this->error(ErrorCode::NOT_FOUND, '排期不存在');
        }

        return $this->success($schedule);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'schedule_date' => ['required', 'date'],
            'process_name' => ['required', 'string', 'max:64'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = Order::find($data['order_id']);
        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在');
        }

        $schedule = (new CreateProductionScheduleAction(
            $order,
            $data['schedule_date'],
            $data['process_name'],
            $data['priority'] ?? 3,
            $data['estimated_hours'] ?? null,
        ))->handle();

        return $this->success($schedule, '排期创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $schedule = ProductionSchedule::find($id);

        if (! $schedule) {
            return $this->error(ErrorCode::NOT_FOUND, '排期不存在');
        }

        $data = $request->validate([
            'schedule_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'process_name' => ['nullable', 'string', 'max:64'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'actual_hours' => ['nullable', 'numeric', 'min:0'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['nullable', 'integer', 'in:0,1,2,3,4'],
            'delay_reason' => ['nullable', 'string', 'max:255'],
        ]);

        (new UpdateProductionScheduleAction($schedule, $data))->handle();

        return $this->success($schedule->fresh(), '排期更新成功');
    }

    public function updateProgress(Request $request, int $id): JsonResponse
    {
        $schedule = ProductionSchedule::find($id);

        if (! $schedule) {
            return $this->error(ErrorCode::NOT_FOUND, '排期不存在');
        }

        $data = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'actual_hours' => ['nullable', 'numeric', 'min:0'],
        ]);

        (new UpdateProductionScheduleAction($schedule, $data))->handle();

        return $this->success($schedule->fresh(), '进度更新成功');
    }
}

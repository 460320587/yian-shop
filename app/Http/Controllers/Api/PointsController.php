<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Points\Models\CustomerPointsLog;
use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointsController extends BaseController
{
    public function index(): JsonResponse
    {
        $customer = auth('sanctum')->user();

        return $this->success([
            'points' => $customer->points,
            'grow_value' => $customer->grow_value,
        ]);
    }

    public function records(Request $request): JsonResponse
    {
        $query = CustomerPointsLog::where('customer_id', auth('sanctum')->id());

        if ($request->has('type')) {
            $query->where('type', (int) $request->input('type'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->paginate($perPage);

        return $this->paginated($paginator);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Logistics\Models\OrderDelivery;
use App\Domains\Order\Models\Order;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;

class LogisticsController extends BaseController
{
    public function tracks(int $orderId): JsonResponse
    {
        $order = Order::where('customer_id', auth('sanctum')->id())->find($orderId);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在', null, 404);
        }

        $delivery = OrderDelivery::with('tracks')
            ->where('order_id', $order->id)
            ->first();

        if (! $delivery) {
            return $this->error(ErrorCode::NOT_FOUND, '暂无物流信息', null, 404);
        }

        return $this->success([
            'carrier_name' => $delivery->carrier_name,
            'tracking_no' => $delivery->tracking_no,
            'status' => $delivery->status,
            'shipped_at' => $delivery->shipped_at,
            'delivered_at' => $delivery->delivered_at,
            'tracks' => $delivery->tracks->sortByDesc('track_time')->values()->map(function ($track) {
                return [
                    'time' => $track->track_time,
                    'location' => $track->location,
                    'description' => $track->description,
                ];
            }),
        ]);
    }

    public function map(int $orderId): JsonResponse
    {
        $order = Order::where('customer_id', auth('sanctum')->id())->find($orderId);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在', null, 404);
        }

        $delivery = OrderDelivery::with('tracks')
            ->where('order_id', $order->id)
            ->first();

        if (! $delivery) {
            return $this->error(ErrorCode::NOT_FOUND, '暂无物流信息', null, 404);
        }

        $tracks = $delivery->tracks->sortBy('track_time')->values();

        $points = $tracks->map(function ($track) {
            return [
                'time' => $track->track_time,
                'location' => $track->location,
                'latitude' => $track->latitude,
                'longitude' => $track->longitude,
                'description' => $track->description,
            ];
        });

        return $this->success([
            'carrier_name' => $delivery->carrier_name,
            'tracking_no' => $delivery->tracking_no,
            'status' => $delivery->status,
            'shipped_at' => $delivery->shipped_at,
            'delivered_at' => $delivery->delivered_at,
            'current' => $points->last(),
            'path' => $points,
        ]);
    }

    public function recommend(int $orderId): JsonResponse
    {
        $order = Order::where('customer_id', auth('sanctum')->id())->find($orderId);

        if (! $order) {
            return $this->error(ErrorCode::NOT_FOUND, '订单不存在', null, 404);
        }

        // 模拟物流推荐方案
        $recommendations = [
            [
                'carrier_name' => '顺丰速运',
                'estimated_days' => 2,
                'price' => 18.00,
                'recommend_reason' => '时效最快',
            ],
            [
                'carrier_name' => '中通快递',
                'estimated_days' => 3,
                'price' => 12.00,
                'recommend_reason' => '性价比高',
            ],
            [
                'carrier_name' => '圆通速递',
                'estimated_days' => 3,
                'price' => 10.00,
                'recommend_reason' => '价格最低',
            ],
        ];

        return $this->success($recommendations);
    }
}

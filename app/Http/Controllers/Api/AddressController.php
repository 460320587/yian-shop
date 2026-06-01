<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\User\Models\CustomerAddress;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;

class AddressController extends BaseController
{
    public function index(): JsonResponse
    {
        $addresses = CustomerAddress::where('customer_id', auth('sanctum')->id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->paginated($addresses);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $customerId = auth('sanctum')->id();
        $isDefault = $request->boolean('is_default', false);

        if ($isDefault) {
            CustomerAddress::where('customer_id', $customerId)
                ->update(['is_default' => false]);
        }

        $address = CustomerAddress::create([
            'customer_id' => $customerId,
            'contact_name' => $request->input('contact_name'),
            'contact_phone' => $request->input('contact_phone'),
            'province_name' => $request->input('province_name'),
            'city_name' => $request->input('city_name'),
            'county_name' => $request->input('county_name'),
            'detail_address' => $request->input('detail_address'),
            'zip_code' => $request->input('zip_code'),
            'is_default' => $isDefault || ! CustomerAddress::where('customer_id', $customerId)->exists(),
            'tag' => $request->input('tag'),
        ]);

        return $this->success($this->formatAddress($address), '添加成功', 201);
    }

    public function update(UpdateAddressRequest $request, int $id): JsonResponse
    {
        $address = CustomerAddress::where('customer_id', auth('sanctum')->id())->find($id);

        if (! $address) {
            return $this->error(ErrorCode::FORBIDDEN, '无权操作该地址', null, 403);
        }

        $address->update($request->validated());

        return $this->success($this->formatAddress($address), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $address = CustomerAddress::where('customer_id', auth('sanctum')->id())->find($id);

        if (! $address) {
            return $this->error(ErrorCode::FORBIDDEN, '无权操作该地址', null, 403);
        }

        $wasDefault = $address->is_default;
        $customerId = $address->customer_id;
        $address->forceDelete();

        if ($wasDefault) {
            $next = CustomerAddress::where('customer_id', $customerId)
                ->orderBy('created_at', 'asc')
                ->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return $this->success([], '删除成功');
    }

    public function setDefault(int $id): JsonResponse
    {
        $customerId = auth('sanctum')->id();
        $address = CustomerAddress::where('customer_id', $customerId)->find($id);

        if (! $address) {
            return $this->error(ErrorCode::FORBIDDEN, '无权操作该地址', null, 403);
        }

        CustomerAddress::where('customer_id', $customerId)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        return $this->success($this->formatAddress($address), '设置成功');
    }

    private function formatAddress(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'contact_name' => $address->contact_name,
            'contact_phone' => $address->contact_phone,
            'province_name' => $address->province_name,
            'city_name' => $address->city_name,
            'county_name' => $address->county_name,
            'detail_address' => $address->detail_address,
            'full_address' => $address->province_name . $address->city_name . $address->county_name . $address->detail_address,
            'zip_code' => $address->zip_code,
            'is_default' => (bool) $address->is_default,
            'tag' => $address->tag,
            'created_at' => $address->created_at,
        ];
    }
}

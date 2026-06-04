<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Invoice\Models\Invoice;
use App\Domains\Invoice\Models\InvoiceTitle;
use App\Domains\Order\Models\Order;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends BaseController
{
    // ========== 发票抬头 ==========

    public function titleIndex(): JsonResponse
    {
        $titles = InvoiceTitle::where('customer_id', auth('sanctum')->id())
            ->orderByDesc('is_default')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($titles);
    }

    public function titleStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title_type' => 'required|integer|in:1,2',
            'invoice_category' => 'required|integer|in:1,2',
            'company_name' => 'required|string|max:200',
            'tax_number' => 'nullable|string|max:20',
            'register_address' => 'nullable|string|max:255',
            'register_phone' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:30',
            'is_default' => 'nullable|integer|in:0,1',
        ]);

        $customerId = auth('sanctum')->id();

        if (! empty($data['is_default'])) {
            InvoiceTitle::where('customer_id', $customerId)->update(['is_default' => 0]);
        }

        $title = InvoiceTitle::create(array_merge($data, ['customer_id' => $customerId]));

        return $this->success($title, '发票抬头已添加', 201);
    }

    public function titleUpdate(Request $request, int $id): JsonResponse
    {
        $title = InvoiceTitle::where('customer_id', auth('sanctum')->id())->find($id);
        if (! $title) {
            return $this->error(ErrorCode::NOT_FOUND, '发票抬头不存在');
        }

        $data = $request->validate([
            'title_type' => 'sometimes|integer|in:1,2',
            'invoice_category' => 'sometimes|integer|in:1,2',
            'company_name' => 'sometimes|string|max:200',
            'tax_number' => 'nullable|string|max:20',
            'register_address' => 'nullable|string|max:255',
            'register_phone' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:30',
            'is_default' => 'nullable|integer|in:0,1',
        ]);

        $customerId = auth('sanctum')->id();
        if (! empty($data['is_default'])) {
            InvoiceTitle::where('customer_id', $customerId)->where('id', '!=', $id)->update(['is_default' => 0]);
        }

        $title->update($data);

        return $this->success($title, '发票抬头已更新');
    }

    public function titleDestroy(int $id): JsonResponse
    {
        $title = InvoiceTitle::where('customer_id', auth('sanctum')->id())->find($id);
        if (! $title) {
            return $this->error(ErrorCode::NOT_FOUND, '发票抬头不存在');
        }

        $title->delete();

        return $this->success([], '发票抬头已删除');
    }

    // ========== 发票申请 ==========

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::where('customer_id', auth('sanctum')->id());

        if ($request->has('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        return $this->paginated($query->orderBy('created_at', 'desc')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'title_id' => 'required|integer|exists:invoice_titles,id',
            'type' => 'required|integer|in:1,2',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
            'remark' => 'nullable|string|max:500',
        ]);

        $customerId = auth('sanctum')->id();

        $order = Order::where('id', $data['order_id'])->where('customer_id', $customerId)->first();
        if (! $order) {
            return $this->error(ErrorCode::FORBIDDEN, '无权操作该订单');
        }

        $existing = Invoice::where('order_id', $data['order_id'])
            ->where('customer_id', $customerId)
            ->whereIn('status', [1, 2, 4, 5])
            ->exists();
        if ($existing) {
            return $this->error(ErrorCode::BAD_REQUEST, '该订单已存在有效发票申请');
        }

        $title = InvoiceTitle::where('customer_id', $customerId)->find($data['title_id']);
        if (! $title) {
            return $this->error(ErrorCode::NOT_FOUND, '发票抬头不存在');
        }

        $invoice = DB::transaction(function () use ($data, $customerId, $order, $title) {
            $invoice = Invoice::create([
                'order_id' => $data['order_id'],
                'customer_id' => $customerId,
                'type' => $data['type'],
                'status' => 1,
                'business_type' => 1,
                'title' => $title->company_name,
                'tax_number' => $title->tax_number,
                'amount' => $order->total_amount ?? Money::fromYuan(0),
                'email' => $data['email'] ?? $title->register_phone,
                'address' => $data['address'] ?? null,
                'bank_name' => $title->bank_name,
                'bank_account' => $title->bank_account,
                'remark' => $data['remark'] ?? null,
            ]);

            return $invoice;
        });

        return $this->success($invoice->load('order'), '发票申请已提交', 201);
    }

    public function show(int $id): JsonResponse
    {
        $invoice = Invoice::where('customer_id', auth('sanctum')->id())
            ->with('order')
            ->find($id);

        if (! $invoice) {
            return $this->error(ErrorCode::NOT_FOUND, '发票不存在');
        }

        return $this->success($invoice);
    }

    public function cancel(int $id): JsonResponse
    {
        $invoice = Invoice::where('customer_id', auth('sanctum')->id())->find($id);
        if (! $invoice) {
            return $this->error(ErrorCode::NOT_FOUND, '发票不存在');
        }

        if ($invoice->status !== 1) {
            return $this->error(ErrorCode::ORDER_STATUS_INVALID, '当前状态不可取消', null, 400);
        }

        $invoice->update(['status' => 0]);

        return $this->success([], '发票申请已取消');
    }
}

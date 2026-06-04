<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Invoice\Models\Invoice;
use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInvoiceController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with('customer', 'order');

        if ($request->has('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        return $this->paginated($query->orderBy('created_at', 'desc')->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $invoice = Invoice::with('customer', 'order')->find($id);
        if (! $invoice) {
            return $this->error(ErrorCode::NOT_FOUND, '发票不存在');
        }

        return $this->success($invoice);
    }

    public function audit(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::find($id);
        if (! $invoice) {
            return $this->error(ErrorCode::NOT_FOUND, '发票不存在');
        }

        $data = $request->validate([
            'status' => 'required|integer|in:2,3',
            'remark' => 'nullable|string|max:500',
        ]);

        $invoice->update([
            'status' => $data['status'],
            'remark' => $data['remark'] ?? null,
        ]);

        $label = $data['status'] === 2 ? '审核通过' : '审核拒绝';
        return $this->success($invoice, "发票已{$label}");
    }

    public function issue(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::find($id);
        if (! $invoice) {
            return $this->error(ErrorCode::NOT_FOUND, '发票不存在');
        }

        $data = $request->validate([
            'invoice_no' => 'required|string|max:32',
            'express_no' => 'nullable|string|max:50',
        ]);

        $invoice->update([
            'status' => 4,
            'invoice_no' => $data['invoice_no'],
            'express_no' => $data['express_no'] ?? null,
            'issued_at' => now(),
        ]);

        return $this->success($invoice, '发票已开具');
    }
}

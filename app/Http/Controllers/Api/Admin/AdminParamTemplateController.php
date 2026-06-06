<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\Product\Models\ParamTemplate;
use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminParamTemplateController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = ParamTemplate::with('category:id,name');

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        $templates = $query->orderBy('sort_order', 'asc')->get();

        return $this->success($templates->map(fn (ParamTemplate $t) => [
            'id' => $t->id,
            'category_id' => $t->category_id,
            'category_name' => $t->category?->name,
            'param_type' => $t->param_type,
            'param_name' => $t->param_name,
            'options' => $t->options,
            'rules' => $t->rules,
            'version' => $t->version,
            'sort_order' => $t->sort_order,
            'status' => $t->status,
        ]));
    }

    public function show(int $id): JsonResponse
    {
        $template = ParamTemplate::with('category:id,name')->findOrFail($id);

        return $this->success([
            'id' => $template->id,
            'category_id' => $template->category_id,
            'category_name' => $template->category?->name,
            'param_type' => $template->param_type,
            'param_name' => $template->param_name,
            'options' => $template->options,
            'rules' => $template->rules,
            'version' => $template->version,
            'sort_order' => $template->sort_order,
            'status' => $template->status,
        ]);
    }
}

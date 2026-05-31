<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BaseRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->post('/api/v1/test-base-request', function (TestRequest $request) {
            return response()->json(['name' => $request->input('name')]);
        });
    }

    public function test_failed_validation_returns_json_error_response(): void
    {
        $response = $this->postJson('/api/v1/test-base-request', []);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 422,
                'message' => '请求参数校验失败',
            ])
            ->assertJsonPath('data.name', ['validation.required']);
    }

    public function test_successful_validation_passes_through(): void
    {
        $response = $this->postJson('/api/v1/test-base-request', ['name' => 'John']);

        $response->assertStatus(200)
            ->assertJson(['name' => 'John']);
    }
}

class TestRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => 'required|string'];
    }
}

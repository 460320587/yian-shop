<?php

declare(strict_types=1);

namespace Tests\Feature\Exceptions;

use App\Exceptions\BusinessException;
use App\Support\ErrorCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExceptionHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->group(function () {
            Route::get('/api/v1/test-business-exception', function () {
                throw new BusinessException(ErrorCode::ORDER_NOT_FOUND);
            });

            Route::post('/api/v1/test-validation-exception', function () {
                throw ValidationException::withMessages(['email' => ['邮箱字段必填']]);
            });

            Route::get('/api/v1/test-auth-exception', function () {
                throw new AuthenticationException();
            });

            Route::get('/api/v1/test-forbidden-exception', function () {
                throw new AuthorizationException();
            });

            Route::get('/api/v1/test-not-found-exception', function () {
                throw (new ModelNotFoundException)->setModel('App\Models\User', [1]);
            });
        });
    }

    public function test_business_exception_returns_json(): void
    {
        $response = $this->getJson('/api/v1/test-business-exception');

        $response->assertStatus(200)
            ->assertJson([
                'code' => ErrorCode::ORDER_NOT_FOUND->value,
                'message' => '订单不存在',
            ]);
    }

    public function test_validation_exception_returns_422(): void
    {
        $response = $this->postJson('/api/v1/test-validation-exception');

        $response->assertStatus(422)
            ->assertJson([
                'code' => ErrorCode::VALIDATION_ERROR->value,
                'message' => '请求参数校验失败',
            ])
            ->assertJsonPath('data.email', ['邮箱字段必填']);
    }

    public function test_authentication_exception_returns_401(): void
    {
        $response = $this->getJson('/api/v1/test-auth-exception');

        $response->assertStatus(401)
            ->assertJson([
                'code' => ErrorCode::UNAUTHORIZED->value,
                'message' => '未登录或登录已过期',
            ]);
    }

    public function test_authorization_exception_returns_403(): void
    {
        $response = $this->getJson('/api/v1/test-forbidden-exception');

        $response->assertStatus(403)
            ->assertJson([
                'code' => ErrorCode::FORBIDDEN->value,
                'message' => '无权访问该资源',
            ]);
    }

    public function test_model_not_found_returns_404(): void
    {
        $response = $this->getJson('/api/v1/test-not-found-exception');

        $response->assertStatus(404)
            ->assertJson([
                'code' => ErrorCode::NOT_FOUND->value,
                'message' => '请求的资源不存在',
            ]);
    }
}

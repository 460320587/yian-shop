<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Jobs;

use Tests\TestCase;
use App\Infrastructure\Jobs\BaseJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BaseJobTest extends TestCase
{
    public function test_base_job_is_abstract(): void
    {
        $reflection = new \ReflectionClass(BaseJob::class);
        $this->assertTrue($reflection->isAbstract(), 'BaseJob 必须是抽象类');
    }

    public function test_base_job_implements_should_queue(): void
    {
        $reflection = new \ReflectionClass(BaseJob::class);
        $this->assertTrue($reflection->implementsInterface(ShouldQueue::class), 'BaseJob 必须实现 ShouldQueue');
    }

    public function test_base_job_uses_required_traits(): void
    {
        $traits = class_uses(BaseJob::class);
        $this->assertContains(Dispatchable::class, $traits, '必须使用 Dispatchable trait');
        $this->assertContains(InteractsWithQueue::class, $traits, '必须使用 InteractsWithQueue trait');
        $this->assertContains(Queueable::class, $traits, '必须使用 Queueable trait');
        $this->assertContains(SerializesModels::class, $traits, '必须使用 SerializesModels trait');
    }

    public function test_base_job_defines_abstract_handle_method(): void
    {
        $reflection = new \ReflectionClass(BaseJob::class);
        $this->assertTrue($reflection->hasMethod('handle'), '必须定义 handle() 方法');

        $method = $reflection->getMethod('handle');
        $this->assertTrue($method->isAbstract(), 'handle() 必须是抽象方法');
    }

    public function test_base_job_has_tries_property(): void
    {
        $reflection = new \ReflectionClass(BaseJob::class);
        $this->assertTrue($reflection->hasProperty('tries'), '必须定义 $tries 属性');
    }

    public function test_base_job_has_max_exceptions_property(): void
    {
        $reflection = new \ReflectionClass(BaseJob::class);
        $this->assertTrue($reflection->hasProperty('maxExceptions'), '必须定义 $maxExceptions 属性');
    }

    public function test_base_job_has_fail_on_timeout_property(): void
    {
        $reflection = new \ReflectionClass(BaseJob::class);
        $this->assertTrue($reflection->hasProperty('failOnTimeout'), '必须定义 $failOnTimeout 属性');
    }

    public function test_base_job_has_timeout_property(): void
    {
        $reflection = new \ReflectionClass(BaseJob::class);
        $this->assertTrue($reflection->hasProperty('timeout'), '必须定义 $timeout 属性');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Commands;

use Tests\TestCase;
use App\Infrastructure\Commands\BaseCommand;

class BaseCommandTest extends TestCase
{
    public function test_base_command_is_abstract(): void
    {
        $reflection = new \ReflectionClass(BaseCommand::class);
        $this->assertTrue($reflection->isAbstract(), 'BaseCommand 必须是抽象类');
    }

    public function test_base_command_extends_laravel_command(): void
    {
        $reflection = new \ReflectionClass(BaseCommand::class);
        $this->assertTrue($reflection->isSubclassOf(\Illuminate\Console\Command::class), '必须继承 Illuminate\\Console\\Command');
    }

    public function test_base_command_defines_abstract_handle_method(): void
    {
        $reflection = new \ReflectionClass(BaseCommand::class);
        $this->assertTrue($reflection->hasMethod('handle'), '必须定义 handle() 方法');

        $method = $reflection->getMethod('handle');
        $this->assertTrue($method->isAbstract(), 'handle() 必须是抽象方法');
    }

    public function test_base_command_has_log_start_method(): void
    {
        $reflection = new \ReflectionClass(BaseCommand::class);
        $this->assertTrue($reflection->hasMethod('logStart'), '必须提供 logStart() 方法');
    }

    public function test_base_command_has_log_end_method(): void
    {
        $reflection = new \ReflectionClass(BaseCommand::class);
        $this->assertTrue($reflection->hasMethod('logEnd'), '必须提供 logEnd() 方法');
    }

    public function test_base_command_has_log_error_method(): void
    {
        $reflection = new \ReflectionClass(BaseCommand::class);
        $this->assertTrue($reflection->hasMethod('logError'), '必须提供 logError() 方法');
    }
}

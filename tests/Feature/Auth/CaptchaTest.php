<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\TestCase;

class CaptchaTest extends TestCase
{
    public function test_guest_can_get_captcha(): void
    {
        $response = $this->getJson('/api/v1/auth/captcha');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    'captcha_key',
                    'captcha_code',
                ],
            ]);
    }
}

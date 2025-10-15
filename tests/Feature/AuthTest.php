<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_routes_require_bearer_token(): void
    {
        $this->json('GET', '/api/translations')->assertUnauthorized();

        $plain = str_repeat('c', 64);
        ApiToken::create(['name' => 'test', 'token_hash' => hash('sha256', $plain)]);

        $this->json('GET', '/api/translations', [], ['Authorization' => 'Bearer '.$plain])->assertOk();
    }
}

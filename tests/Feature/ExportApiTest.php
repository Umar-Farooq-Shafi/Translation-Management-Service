<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authHeader(): array
    {
        $plain = str_repeat('b', 64);
        ApiToken::create([
            'name' => 'test',
            'token_hash' => hash('sha256', $plain),
        ]);

        return ['Authorization' => 'Bearer ' . $plain];
    }

    public function test_export_returns_map_and_etag(): void
    {
        $headers = $this->authHeader();

        Translation::create([
            'key' => 'home.title',
            'locale' => 'en',
            'content' => 'Welcome',
            'tags' => ['web'],
        ]);

        $resp = $this->json('GET', '/api/export?locale=en&tags[0]=web', [], $headers)
            ->assertOk()
            ->assertHeader('ETag');

        $etag = trim($resp->headers->get('ETag'), '"');

        $this->json('GET', '/api/export?locale=en&tags[0]=web', [], array_merge($headers, [
            'If-None-Match' => '"' . $etag . '"'
        ]))->assertStatus(304);
    }

}

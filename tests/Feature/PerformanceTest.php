<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function authHeader(): array
    {
        $plain = str_repeat('d', 64);
        ApiToken::create(['name' => 'perf', 'token_hash' => hash('sha256', $plain)]);
        return ['Authorization' => 'Bearer ' . $plain];
    }

    public function test_export_under_500ms_with_5k_records(): void
    {
        $headers = $this->authHeader();

        $web = Tag::create(['name' => 'web']);

        Translation::factory()->count(5000)->create(['locale' => 'en'])->each(function ($t) use ($web) {
            $t->tags()->sync([$web->id]);
        });

        $start = microtime(true);
        $this->json('GET', '/api/export?locale=en&tags[0]=web', [], $headers)->assertOk();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $elapsed, 'Export exceeded 500ms: '.$elapsed.'ms');
    }

    public function test_endpoints_under_200ms_basic(): void
    {
        $headers = $this->authHeader();

        $start = microtime(true);
        $this->json('GET', '/api/translations', [], $headers)->assertOk();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $elapsed, 'List exceeded 200ms: '.$elapsed.'ms');
    }
}

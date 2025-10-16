<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authHeader(): array
    {
        $plain = str_repeat('a', 64);
        ApiToken::create([
            'name' => 'test',
            'token_hash' => hash('sha256', $plain),
        ]);

        return ['Authorization' => 'Bearer ' . $plain];
    }

    public function test_can_create_and_list_translations(): void
    {
        $headers = $this->authHeader();

        $this->json('POST', '/api/translations', [
            'key' => 'greeting.hello',
            'locale' => 'en',
            'content' => 'Hello',
            'tags' => ['web', 'common'],
        ], $headers)->assertCreated();

        $this->json('GET', '/api/translations?locale=en', [], $headers)
            ->assertOk()
            ->assertJsonPath('data.0.key', 'greeting.hello');
    }

    public function test_can_filter_by_tag_and_query(): void
    {
        $headers = $this->authHeader();

        Translation::create([
            'key' => 'home.title',
            'locale' => 'en',
            'content' => 'Welcome Home',
            'tags' => ['web'],
        ]);

        Translation::create([
            'key' => 'home.title',
            'locale' => 'fr',
            'content' => 'Bienvenue',
            'tags' => ['mobile'],
        ]);

        $this->json('GET', '/api/translations?tags[0]=web&locale=en&q=Welcome', [], $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}

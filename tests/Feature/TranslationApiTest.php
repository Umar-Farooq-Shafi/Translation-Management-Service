<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authHeader(): array
    {
        $plain = str_repeat('a', 64);
        ApiToken::create(['name' => 'test', 'token_hash' => hash('sha256', $plain)]);
        return ['Authorization' => 'Bearer ' . $plain];
    }

    public function test_can_create_and_list_translations(): void
    {
        $headers = $this->authHeader();

        $this->json('POST', '/api/translations', [
            'key' => 'greeting.hello',
            'locale' => 'en',
            'value' => 'Hello',
            'tags' => ['web', 'common'],
        ], $headers)->assertCreated();

        $this->json('GET', '/api/translations?locale=en', [], $headers)
            ->assertOk()
            ->assertJsonPath('data.0.key', 'greeting.hello');
    }

    public function test_can_filter_by_tag_and_query(): void
    {
        $headers = $this->authHeader();

        $t1 = Translation::create(['key' => 'home.title', 'locale' => 'en', 'value' => 'Welcome Home']);
        $t2 = Translation::create(['key' => 'home.title', 'locale' => 'fr', 'value' => 'Bienvenue']);
        $web = Tag::create(['name' => 'web']);
        $mobile = Tag::create(['name' => 'mobile']);
        $t1->tags()->sync([$web->id]);
        $t2->tags()->sync([$mobile->id]);

        $this->json('GET', '/api/translations?tags[0]=web&locale=en&q=Welcome', [], $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition(): array
    {
        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt'];
        $baseKey = fake()->unique()->slug(3);

        return [
            'key' => "app.$baseKey",
            'locale' => fake()->randomElement($locales),
            'value' => fake()->sentence(8),
        ];
    }
}

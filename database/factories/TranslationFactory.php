<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition(): array
    {
        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt'];
        $tagsPool = ['web','mobile','desktop','email','admin'];

        $key = $this->faker->words(3, true);
        $key = Str::slug($key, '.');

        return [
            'key' => $key,
            'locale' => $this->faker->randomElement($locales),
            'content' => $this->faker->sentence(6),
            'tags' => $this->faker->randomElements($tagsPool, $this->faker->numberBetween(1,2)),
        ];
    }
}

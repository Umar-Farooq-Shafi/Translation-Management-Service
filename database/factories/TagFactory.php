<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['mobile', 'desktop', 'web', 'marketing', 'admin', 'common']),
        ];
    }
}

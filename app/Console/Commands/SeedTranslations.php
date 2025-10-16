<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\Translation;
use Faker\Factory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedTranslations extends Command
{
    protected $signature = 'translations:seed {count=100000}';
    protected $description = 'Seed translations and tags efficiently for scalability testing';

    public function handle()
    {
        $count = (int) $this->argument('count');
        $this->info("Seeding {$count} translations...");

        $chunk = 1000;
        $faker = Factory::create();

        $locales = ['en','fr','es','de','it','pt'];
        $tagsPool = ['web','mobile','desktop','email','admin'];

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i += $chunk) {
            $batch = [];
            $limit = min($chunk, $count - $i);

            for ($j = 0; $j < $limit; $j++) {
                $key = Str::slug($faker->words(3, true), '.');

                $batch[] = [
                    'key' => $key,
                    'locale' => $faker->randomElement($locales),
                    'content' => $faker->sentence(8),
                    'tags' => json_encode($faker->randomElements($tagsPool, $faker->numberBetween(1,2))),
                    'context' => $faker->randomElement(['ui','errors','emails', null]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $bar->advance();
            }

            DB::table('translations')->insert($batch);
            unset($batch);
        }

        $bar->finish();
        $this->info("\nDone.");
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedTranslations extends Command
{
    protected $signature = 'translations:seed {count=100000}';
    protected $description = 'Seed translations and tags efficiently for scalability testing';

    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $this->info("Seeding {$count} translations...");

        $tags = ['mobile', 'desktop', 'web', 'marketing', 'admin', 'common'];
        foreach ($tags as $t) {
            Tag::firstOrCreate(['name' => $t]);
        }
        $tagIds = Tag::pluck('id')->all();

        $chunk = 5000;
        $inserted = 0;

        DB::disableQueryLog();

        while ($inserted < $count) {
            $batch = min($chunk, $count - $inserted);

            $translations = Translation::factory()->count($batch)->make();
            // Insert in chunks using upsert to respect unique (key, locale)
            $toUpsert = $translations->map(function ($tr) {
                return [
                    'key' => $tr->key,
                    'locale' => $tr->locale,
                    'value' => $tr->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            // Upsert
            Translation::query()->upsert($toUpsert, ['key', 'locale'], ['value', 'updated_at']);

            // Attach random tags (2 per translation average)
            $ids = Translation::query()
                ->latest('id')
                ->limit($batch)
                ->pluck('id');

            $pivot = [];
            foreach ($ids as $id) {
                $pick = collect($tagIds)->random(rand(1, 3))->all();
                foreach ($pick as $tid) {
                    $pivot[] = ['translation_id' => $id, 'tag_id' => $tid, 'created_at' => now(), 'updated_at' => now()];
                }
            }
            if (!empty($pivot)) {
                DB::table('translation_tag')->insert($pivot);
            }

            $inserted += $batch;
            $this->info("Inserted: {$inserted}/{$count}");
        }

        $this->info('Done.');
        return 0;
    }
}

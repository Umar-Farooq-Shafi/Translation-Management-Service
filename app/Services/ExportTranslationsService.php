<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ExportTranslationsService
{
    /**
     * Returns [payload (array: key=>value), etag (string)]
     */
    public function export(string $locale, array $tags = []): array
    {
        // Compute a cache key including a moving version derived from last update
        $versionKey = $this->versionKey($locale, $tags);

        return Cache::remember($versionKey, now()->addSeconds(60), function () use ($locale, $tags) {
            $query = Translation::query()
                ->select(['key', 'value', 'updated_at'])
                ->where('locale', $locale);

            if (!empty($tags)) {
                $query->whereHas('tags', function (Builder $q) use ($tags) {
                    $q->whereIn('name', $tags);
                });
            }

            $rows = $query->orderBy('key')->get();

            $payload = [];
            $latestMicro = 0;
            foreach ($rows as $row) {
                $payload[$row->key] = $row->value;
                $latestMicro = max($latestMicro, (int) ($row->updated_at?->format('Uu') ?? 0));
            }

            // ETag built from locale, tags, count, and latest updated timestamp
            $etag = hash('sha256', implode('|', [
                $locale,
                implode(',', $tags),
                count($payload),
                (string) $latestMicro,
            ]));

            return [$payload, $etag];
        });
    }

    private function versionKey(string $locale, array $tags): string
    {
        $tagsKey = implode(',', $tags);
        // Short TTL key that updates when collection changes (size/time embedded in value)
        $latest = Translation::query()
            ->where('locale', $locale)
            ->when(!empty($tags), fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->whereIn('name', $tags)))
            ->max('updated_at');

        $count = Translation::query()
            ->where('locale', $locale)
            ->when(!empty($tags), fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->whereIn('name', $tags)))
            ->count();

        $sig = sprintf('%s|%s|%s|%d', $locale, $tagsKey, (string) $latest, $count);

        return 'export:' . hash('sha256', $sig);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;

class TranslationsService
{
    /**
     * Returns [payload (array: key=>value), etag (string)]
     */
    public function export(string $locale): array
    {
        return Cache::remember("translations:export:{$locale}", 3600, function () use ($locale) {
            return response()->stream(function () use ($locale) {
                echo '[';
                $first = true;

                Translation::where('locale', $locale)
                    ->orderBy('id')
                    ->cursor()
                    ->each(function ($t) use (&$first) {
                        if (!$first) echo ',';
                        echo json_encode([
                            'key' => $t->key,
                            'content' => $t->content,
                            'tags' => $t->tags,
                            'context' => $t->context,
                        ]);
                        $first = false;
                        flush();
                    });

                echo ']';
            }, 200, ['Content-Type' => 'application/json']);
        });
    }

}

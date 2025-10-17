<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationsService
{
    /**
     * Returns [payload (array: key=>value), etag (string)]
     */
    public function export(string $locale)
    {
        $cacheKey = "translations:export:{$locale}";
        $ttl = 3600;

        if (Cache::has($cacheKey)) {
            return response()->stream(function () use ($cacheKey) {
                echo Cache::get($cacheKey);
            }, 200, ['Content-Type' => 'application/json']);
        }

        $json = '';
        $stream = function () use ($locale, $cacheKey, $ttl, &$json) {
            echo '[';
            $first = true;

            Translation::where('locale', $locale)
                ->orderBy('id')
                ->cursor()
                ->each(function ($t) use (&$first, &$json) {
                    $item = json_encode([
                        'key' => $t->key,
                        'content' => $t->content,
                        'tags' => $t->tags,
                        'locale' => $t->locale,
                    ]);
                    if (!$first) {
                        echo ',';
                        $json .= ',';
                    }
                    echo $item;
                    $json .= $item;
                    $first = false;
                    flush();
                });

            echo ']';
            $json .= ']';

            Cache::put($cacheKey, $json, $ttl);
        };

        return new StreamedResponse($stream, 200, ['Content-Type' => 'application/json']);
    }

}

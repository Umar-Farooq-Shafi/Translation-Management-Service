<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ExportTranslationsService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __construct(private readonly ExportTranslationsService $service)
    {
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'max:10'],
            'tags'   => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:64'],
        ]);

        $tags = $validated['tags'] ?? [];

        [$payload, $etag] = $this->service->export($validated['locale'], $tags);

        // ETag support for CDN/browser caching and freshness
        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch && trim($ifNoneMatch, '"') === $etag) {
            return response()->noContent(304)->withHeaders([
                'ETag' => '"' . $etag . '"',
                'Cache-Control' => 'public, max-age=60, s-maxage=300, stale-while-revalidate=60',
            ]);
        }

        return response()->json($payload, 200, [
            'ETag' => '"' . $etag . '"',
            'Cache-Control' => 'public, max-age=60, s-maxage=300, stale-while-revalidate=60',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

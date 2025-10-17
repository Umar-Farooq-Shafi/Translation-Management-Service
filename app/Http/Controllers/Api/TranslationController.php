<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TranslationResource;
use App\Models\Translation;
use App\Services\TranslationsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationController extends Controller
{

    public function __construct(
        protected TranslationsService $translationsService,
    )
    {
    }

    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'tag' => ['sometimes', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
            'key' => ['sometimes', 'string'],
            'q' => ['sometimes', 'string'],
            'locale' => ['sometimes', 'string', 'max:10'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['perPage'] ?? 20;

        $query = Translation::query();

        if (!empty($validated['locale'])) {
            $query->where('locale', $validated['locale']);
        }

        if (!empty($validated['key'])) {
            $query->where('key', 'like', '%' . addcslashes($validated['key'], '%_') . '%');
        }

        if (!empty($validated['q'])) {
            $search = $validated['q'];

            try {
                $query->whereRaw("MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)", [$search]);
            } catch (\Exception $e) {
                $query->where('content', 'like', '%' . addcslashes($search, '%_') . '%');
            }
        }

        $tagsFilter = $validated['tags'] ?? (isset($validated['tag']) ? [$validated['tag']] : []);
        if (!empty($tagsFilter)) {
            $query->where(function ($q) use ($tagsFilter) {
                foreach ($tagsFilter as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        $query->orderBy('key')->orderBy('locale');

        return TranslationResource::collection($query->paginate($perPage));
    }

    /**
     * @param Translation $translation
     * @return TranslationResource
     */
    public function show(Translation $translation): TranslationResource
    {
        return new TranslationResource($translation);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:10'],
            'content' => ['required', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:64'],
        ]);

        $translation = Translation::updateOrCreate(
            ['key' => $validated['key'], 'locale' => $validated['locale']],
            $validated
        );

        Cache::forget("export_{$translation->locale}");

        return (new TranslationResource($translation))
            ->additional(['message' => 'Saved'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @param Request $request
     * @param Translation $translation
     * @return TranslationResource
     */
    public function update(Request $request, Translation $translation): TranslationResource
    {
        $validated = $request->validate([
            'key' => ['sometimes', 'string', 'max:255', Rule::unique('translations')->ignore($translation->id)->where(fn($q) => $q->where('locale', $translation->locale))],
            'locale' => ['sometimes', 'string', 'max:10'],
            'content' => ['sometimes', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:64'],
        ]);

        $translation->update($validated);
        Cache::forget("export_{$translation->locale}");

        return new TranslationResource($translation);
    }

    /**
     * @param Translation $translation
     * @return Response
     */
    public function destroy(Translation $translation): Response
    {
        Cache::forget("export_{$translation->locale}");
        $translation->delete();

        return response()->noContent();
    }

    /**
     * @param Request $request
     * @return StreamedResponse
     */
    public function export(Request $request): StreamedResponse
    {
        $locale = $request->input('locale', 'en');

        return $this->translationsService->export($locale);
    }

}

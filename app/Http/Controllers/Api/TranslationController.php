<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TranslationResource;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationController extends Controller
{

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

        $query = Translation::query()->with('tags');

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
                // Fallback to LIKE if full-text search not supported
                $query->where('content', 'like', '%' . addcslashes($search, '%_') . '%');
            }
        }

        $tagsFilter = $validated['tags'] ?? (isset($validated['tag']) ? [$validated['tag']] : []);
        if (!empty($tagsFilter)) {
            $query->whereHas('tags', function (Builder $q) use ($tagsFilter) {
                $q->whereIn('name', $tagsFilter);
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
        $translation->load('tags');
        return new TranslationResource($translation);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:10'],
            'content' => ['required', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:64'],
            'context' => 'nullable|string|max:255',
        ]);

        $translation = null;

        DB::transaction(function () use (&$translation, $data) {
            $translation = Translation::query()->updateOrCreate(
                ['key' => $data['key'], 'locale' => $data['locale']],
                ['value' => $data['value']]
            );

            if (!empty($data['tags'])) {
                $tagIds = collect($data['tags'])->map(function (string $name) {
                    return Tag::firstOrCreate(['name' => $name])->id;
                })->all();
                $translation->tags()->sync($tagIds);
            }
        });

        $translation->load('tags');

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
        $data = $request->validate([
            'key' => ['sometimes', 'string', 'max:255', Rule::unique('translations')->ignore($translation->id)->where(fn($q) => $q->where('locale', $translation->locale))],
            'locale' => ['sometimes', 'string', 'max:10'],
            'content' => ['sometimes', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:64'],
            'context' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($translation, $data) {
            if (isset($data['key'])) {
                $translation->key = $data['key'];
            }

            if (isset($data['locale'])) {
                $translation->locale = $data['locale'];
            }

            if (array_key_exists('value', $data)) {
                $translation->value = $data['value'];
            }

            $translation->save();

            if (isset($data['tags'])) {
                $tagIds = collect($data['tags'])->map(fn(string $name) => Tag::firstOrCreate(['name' => $name])->id)->all();
                $translation->tags()->sync($tagIds);
            }
        });

        $translation->load('tags');

        return new TranslationResource($translation);
    }

    /**
     * @param Translation $translation
     * @return Response
     */
    public function destroy(Translation $translation): Response
    {
        $translation->tags()->detach();
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

        $response = new StreamedResponse(function() use ($locale) {
            echo '[';
            $first = true;

            Translation::where('locale', $locale)
                ->orderBy('id')
                ->cursor()
                ->each(function($t) use (&$first) {
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
        });

        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}

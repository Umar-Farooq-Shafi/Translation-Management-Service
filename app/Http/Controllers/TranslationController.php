<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\TranslationResource;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TranslationController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'tag'    => ['sometimes', 'string'],
            'tags'   => ['sometimes', 'array'],
            'tags.*' => ['string'],
            'key'    => ['sometimes', 'string'],
            'q'      => ['sometimes', 'string'],
            'locale' => ['sometimes', 'string', 'max:10'],
            'page'   => ['sometimes', 'integer', 'min:1'],
            'perPage'=> ['sometimes', 'integer', 'min:1', 'max:100'],
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
            // For MySQL, optionally add FULLTEXT; fallback to LIKE for portability
            $query->where('value', 'like', '%' . addcslashes($validated['q'], '%_') . '%');
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

    public function show(Translation $translation)
    {
        $translation->load('tags');
        return new TranslationResource($translation);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key'    => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:10'],
            'value'  => ['required', 'string'],
            'tags'   => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:64'],
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

    public function update(Request $request, Translation $translation)
    {
        $data = $request->validate([
            'key'    => ['sometimes', 'string', 'max:255', Rule::unique('translations')->ignore($translation->id)->where(fn($q) => $q->where('locale', $translation->locale))],
            'locale' => ['sometimes', 'string', 'max:10'],
            'value'  => ['sometimes', 'string'],
            'tags'   => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:64'],
        ]);

        DB::transaction(function () use ($translation, $data) {
            if (isset($data['key']))    { $translation->key = $data['key']; }
            if (isset($data['locale'])) { $translation->locale = $data['locale']; }
            if (array_key_exists('value', $data)) { $translation->value = $data['value']; }
            $translation->save();

            if (isset($data['tags'])) {
                $tagIds = collect($data['tags'])->map(fn (string $name) => Tag::firstOrCreate(['name' => $name])->id)->all();
                $translation->tags()->sync($tagIds);
            }
        });

        $translation->load('tags');

        return new TranslationResource($translation);
    }

    public function destroy(Translation $translation)
    {
        $translation->tags()->detach();
        $translation->delete();

        return response()->noContent();
    }
}

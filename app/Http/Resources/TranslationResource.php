<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TranslationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'key'    => $this->key,
            'locale' => $this->locale,
            'value'  => $this->value,
            'tags'   => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->all()),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

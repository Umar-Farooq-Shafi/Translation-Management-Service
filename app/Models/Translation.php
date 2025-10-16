<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'locale',
        'content',
        'tags',
        'context',
    ];

    protected $casts = [
        'tags' => 'array',
    ];


    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'translation_tag')->withTimestamps();
    }
}

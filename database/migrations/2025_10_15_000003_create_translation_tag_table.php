<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('translation_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('translation_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->primary(['translation_id', 'tag_id']);
            $table->foreign('translation_id')->references('id')->on('translations')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();

            $table->index(['tag_id', 'translation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_tag');
    }
};

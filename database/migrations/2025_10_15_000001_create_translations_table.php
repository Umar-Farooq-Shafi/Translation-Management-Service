<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255);
            $table->string('locale', 10)->index();
            $table->text('value');
            $table->timestamps();

            $table->unique(['key', 'locale']);
            // Optional FULLTEXT index with MySQL: uncomment if using InnoDB + MySQL 5.6+
            // $table->fullText('value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};

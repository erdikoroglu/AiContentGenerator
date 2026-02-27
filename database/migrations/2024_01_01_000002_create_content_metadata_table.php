<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('content_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('content_id')->unique();
            $table->string('focus_keyword');
            $table->json('related_keywords')->nullable();
            $table->enum('search_intent', ['informational', 'navigational', 'transactional', 'commercial'])->nullable();
            $table->enum('content_type', ['how-to', 'concept', 'news'])->nullable();
            $table->string('locale', 10)->nullable();
            $table->integer('word_count')->nullable();
            $table->decimal('keyword_density', 5, 2)->nullable();
            $table->string('ai_provider', 50)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('focus_keyword');
            $table->index('locale');
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_metadata');
    }
};

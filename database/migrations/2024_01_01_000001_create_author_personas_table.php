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
        Schema::create('author_personas', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
            $table->string('author_company')->nullable();
            $table->string('author_job_title')->nullable();
            $table->json('author_expertise_areas');
            $table->text('author_short_bio')->nullable();
            $table->string('author_url')->nullable();
            $table->timestamps();

            $table->index('author_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('author_personas');
    }
};

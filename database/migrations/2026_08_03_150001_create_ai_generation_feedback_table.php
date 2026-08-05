<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generation_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_id')->constrained('ai_generations')->cascadeOnDelete();
            $table->string('action');
            $table->string('field')->nullable();
            $table->timestamps();

            $table->index('generation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generation_feedback');
    }
};

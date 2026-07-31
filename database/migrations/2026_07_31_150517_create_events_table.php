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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('location');
            $table->string('city');
            $table->string('format')->default('in_person');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('draft');
            $table->string('banner_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organizer_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('city');
            $table->index('starts_at');
            $table->index(['organizer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->string('provider_used')->nullable()->after('model');
            $table->string('model_used')->nullable()->after('provider_used');
            $table->json('result')->nullable()->after('error_code');
        });
    }

    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropColumn(['provider_used', 'model_used', 'result']);
        });
    }
};

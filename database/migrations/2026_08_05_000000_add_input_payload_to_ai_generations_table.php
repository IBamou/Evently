<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add durable execution input storage for AI generations.
     *
     * The queued job must not depend on volatile cache state: the validated
     * request inputs are persisted here so a delayed worker (backlog, cache
     * flush, deployment) can still execute the generation reliably.
     */
    public function up(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->json('input_payload')->nullable()->after('input_hash');
        });
    }

    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropColumn('input_payload');
        });
    }
};

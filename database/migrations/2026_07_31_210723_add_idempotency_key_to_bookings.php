<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persist the client idempotency key so duplicate submits with the same
     * key can be detected authoritatively (REQ-BK-011).
     *
     * The unique index is scoped per user + event; the key encodes the exact
     * ticket selection, so a changed selection produces a different key.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('idempotency_key', 100)->nullable()->after('reference');
            $table->unique(['user_id', 'event_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'event_id', 'idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};

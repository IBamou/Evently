<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('ticket_name');
            $table->decimal('unit_price', 10, 2)->unsigned();
            $table->unsignedSmallInteger('quantity');
            $table->decimal('line_total', 10, 2)->unsigned();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('ticket_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};

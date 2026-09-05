<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per DATABASE_SCHEMA.md §3. Built now (Step 2) so slot computation
     * can exclude already-booked times (TESTING.md §3) — booking
     * *creation* (the WhatsApp flow, double-booking prevention under
     * concurrency, cancellation) is still Step 4 per ROADMAP.md and the
     * required sequence in CLAUDE_HANDOFF.md. This migration only
     * establishes the schema.
     *
     * The overlap-prevention constraint itself (DATABASE_SCHEMA.md §3
     * "Constraints") is deliberately NOT implemented here — it belongs
     * to Step 4, where it can be built and tested against real
     * concurrent-write scenarios rather than bolted on ahead of time.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            // pending | confirmed | cancelled | completed | no_show
            $table->string('status')->default('pending');
            // whatsapp | manual
            $table->string('source');

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index(['provider_id', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

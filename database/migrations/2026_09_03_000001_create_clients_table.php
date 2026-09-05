<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per DATABASE_SCHEMA.md §2. Built now (Phase 1 Step 2) rather than
     * Step 4 because `bookings.client_id` is a required FK and slot
     * computation must be able to exclude already-booked times
     * (TESTING.md §3) — but no client-onboarding/consent business logic
     * is implemented here, only the schema. That logic lands in Step 3
     * (WhatsApp inbound pipeline creates/finds the Client) and Step 4.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();

            // E.164, per DATABASE_SCHEMA.md §1.
            $table->string('phone_number');
            $table->string('display_name')->nullable();

            // 'opted_in' | 'unknown' — see SOURCE_OF_TRUTH.md §2.6.
            $table->string('consent_status')->default('unknown');

            $table->timestamp('first_contacted_at')->nullable();
            $table->timestamps();

            // A phone number identifies a client scoped to the
            // provider it messaged, not globally — see SECURITY.md §6.
            $table->unique(['provider_id', 'phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

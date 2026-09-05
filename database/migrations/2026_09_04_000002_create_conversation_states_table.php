<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per DATABASE_SCHEMA.md §4 — note only `updated_at` is listed
     * (no `created_at`), same treatment as `slot_exceptions`. This is
     * the skeleton only: the table exists and can hold a client's
     * current step, but no actual states/transitions are defined or
     * enforced until Step 4 builds the booking flow.
     */
    public function up(): void
    {
        Schema::create('conversation_states', function (Blueprint $table): void {
            $table->id();

            // One active state per client.
            $table->foreignId('client_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();

            $table->string('state_key');
            $table->json('context_json')->nullable();

            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_states');
    }
};

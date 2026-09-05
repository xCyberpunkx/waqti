<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per DATABASE_SCHEMA.md §3 — note the schema doc lists only
     * `created_at` for this table (no `updated_at`), which is honored
     * here rather than defaulting to Laravel's usual `timestamps()`.
     */
    public function up(): void
    {
        Schema::create('slot_exceptions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->boolean('is_closed')->default(false);

            // Extra/override hours for this date when not closed. Slot
            // length is still sourced from that weekday's
            // AvailabilityRule — see ComputeAvailableSlots.
            $table->time('override_starts_at')->nullable();
            $table->time('override_ends_at')->nullable();
            $table->string('reason')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['provider_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slot_exceptions');
    }
};

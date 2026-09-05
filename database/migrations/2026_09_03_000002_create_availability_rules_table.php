<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per DATABASE_SCHEMA.md §3. Phase 1 scope decision (not an
     * architecture-level ADR, just an implementation constraint worth
     * recording here since the schema doc doesn't specify it): at most
     * one rule per (provider, weekday). A provider with a split shift
     * (e.g. closed 12–14) expresses that via a SlotException on
     * specific dates for now — recurring split shifts are future scope
     * if a real provider needs one.
     */
    public function up(): void
    {
        Schema::create('availability_rules', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();

            // 0 = Sunday .. 6 = Saturday, matching Carbon's dayOfWeek.
            $table->unsignedTinyInteger('weekday');

            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('slot_length_minutes');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['provider_id', 'weekday']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_rules');
    }
};

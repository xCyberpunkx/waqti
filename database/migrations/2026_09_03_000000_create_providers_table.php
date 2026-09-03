<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 1 assumes a single provider row, but this is modeled as a
     * table (not a config file) since a second provider on shared
     * infrastructure is plausible later — see SOURCE_OF_TRUTH.md §2.9
     * and DATABASE_SCHEMA.md §2.
     */
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table): void {
            $table->id();

            // Dashboard owner. Phase 1: exactly one provider per user.
            // A `users` FK (Fortify-backed) rather than duplicating
            // credential storage, per DATABASE_SCHEMA.md §2 note.
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('business_category')->nullable();
            $table->string('timezone')->default('Africa/Algiers');

            // Meta WhatsApp Cloud API identifiers — not secrets, but
            // meaningless without the access token below.
            $table->string('whatsapp_phone_number_id')->nullable();
            $table->string('whatsapp_business_account_id')->nullable();

            // Encrypted at rest via the model's `encrypted` cast — see
            // SECURITY.md §3. Never select/log this column raw.
            $table->text('whatsapp_access_token')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};

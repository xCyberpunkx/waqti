<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per DATABASE_SCHEMA.md §4, plus `conversation_state` (a snapshot
     * of the client's ConversationState at the moment this message
     * arrived) — that column is in DOMAIN_MODEL.md §4's target
     * attributes but was missing from DATABASE_SCHEMA.md; added here
     * and reconciled into that doc. See DECISIONS.md.
     */
    public function up(): void
    {
        Schema::create('inbound_messages', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            // Meta's message ID (wamid...) — globally unique across the
            // WhatsApp platform, not just per-provider. This is the
            // idempotency key: SOURCE_OF_TRUTH.md §2.3, SECURITY.md §5.
            $table->string('whatsapp_message_id')->unique();

            $table->text('body')->nullable();
            $table->json('payload_json');
            $table->string('conversation_state')->nullable();

            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_messages');
    }
};

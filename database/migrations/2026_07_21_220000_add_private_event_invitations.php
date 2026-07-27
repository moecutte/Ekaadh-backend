<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_private')->default(false)->after('is_featured');
            $table->index(['status', 'is_private']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 20)->default('purchase')->after('payment_reference');
            $table->index('source');
        });

        Schema::create('event_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->string('guest_name')->nullable();
            $table->string('guest_phone', 30);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('token', 64)->unique();
            $table->enum('status', ['active', 'revoked'])->default('active');
            $table->enum('sms_status', ['pending', 'sent', 'failed', 'skipped'])->default('pending');
            $table->enum('whatsapp_status', ['pending', 'sent', 'failed', 'skipped'])->default('pending');
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'guest_phone']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('invitation_id')
                ->nullable()
                ->after('order_item_id')
                ->constrained('event_invitations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invitation_id');
        });

        Schema::dropIfExists('event_invitations');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_private']);
            $table->dropColumn('is_private');
        });
    }
};

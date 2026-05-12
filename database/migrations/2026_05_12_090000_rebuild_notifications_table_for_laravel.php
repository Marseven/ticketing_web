<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The original notifications table was created with a custom schema
     * (user_id, channel, template_key, subject, body, status, ref_*) that
     * no application code ever wrote to. Meanwhile every $user->notify(...)
     * call uses Laravel's standard Database channel which expects the
     * canonical schema (uuid, type, notifiable_*, data, read_at).
     *
     * Result: every notification (OrderConfirmation, PaymentSuccessful,
     * TicketsReady, PayoutCreated/Successful/Failed, etc.) failed with
     * "Column not found: 'type'". This migration drops the dead custom
     * table and rebuilds it as the standard Laravel notifications table.
     */
    public function up(): void
    {
        Schema::dropIfExists('notifications');

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('channel', ['email', 'sms', 'push']);
            $table->string('template_key');
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->datetime('sent_at')->nullable();
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['channel']);
            $table->index(['status']);
            $table->index(['ref_type', 'ref_id']);
        });
    }
};

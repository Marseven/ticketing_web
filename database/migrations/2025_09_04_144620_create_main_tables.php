<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Organizer User (staff) - déjà créée dans une migration précédente
        // Schema::create('organizer_user', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('organizer_id');
        //     $table->unsignedBigInteger('user_id');
        //     $table->string('role_label')->default('manager');
        //     $table->timestamps();
        //     
        //     $table->index(['organizer_id']);
        //     $table->index(['user_id']);
        //     $table->index(['role_label']);
        // });

        // 2. Event Categories
        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
            
            $table->index(['slug']);
            $table->index(['parent_id']);
        });

        // 3. Venues
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organizer_id');
            $table->string('name');
            $table->string('city');
            $table->text('address');
            $table->decimal('geo_lat', 10, 8)->nullable();
            $table->decimal('geo_lng', 11, 8)->nullable();
            $table->timestamps();
            
            $table->index(['organizer_id']);
            $table->index(['city']);
        });

        // 4. Events
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organizer_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('venue_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->string('image_url')->nullable();
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $table->datetime('published_at')->nullable();
            $table->timestamps();
            
            $table->index(['organizer_id']);
            $table->index(['category_id']);
            $table->index(['venue_id']);
            $table->index(['slug']);
            $table->index(['status']);
            $table->index(['published_at']);
        });

        // 5. Event Schedules
        Schema::create('event_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->datetime('starts_at');
            $table->datetime('ends_at');
            $table->datetime('door_time')->nullable();
            $table->enum('status', ['active', 'cancelled', 'completed'])->default('active');
            $table->timestamps();
            
            $table->index(['event_id']);
            $table->index(['starts_at']);
            $table->index(['status']);
        });

        // 6. Ticket Types
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->string('name'); // VIP, Standard, etc.
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'sold_out'])->default('active');
            $table->timestamps();
            
            $table->index(['event_id']);
            $table->index(['status']);
        });

        // 7. Ticket Prices
        Schema::create('ticket_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_type_id');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->string('currency', 3)->default('XOF');
            $table->decimal('unit_price', 12, 2);
            $table->datetime('sale_starts_at')->nullable();
            $table->datetime('sale_ends_at')->nullable();
            $table->integer('max_per_order')->nullable();
            $table->timestamps();
            
            $table->index(['ticket_type_id']);
            $table->index(['schedule_id']);
            $table->index(['sale_starts_at']);
            $table->index(['sale_ends_at']);
        });

        // 8. Ticket Inventories
        Schema::create('ticket_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_type_id');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->integer('quantity_total');
            $table->integer('quantity_sold')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->timestamps();
            
            $table->index(['ticket_type_id']);
            $table->index(['schedule_id']);
        });

        // 9. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organizer_id');
            $table->unsignedBigInteger('buyer_id'); // user client
            $table->string('currency', 3)->default('XOF');
            $table->decimal('subtotal_amount', 12, 2);
            $table->decimal('fees_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['pending', 'paid', 'cancelled', 'refunded'])->default('pending');
            $table->string('reference')->unique();
            $table->datetime('placed_at');
            $table->timestamps();
            
            $table->index(['organizer_id']);
            $table->index(['buyer_id']);
            $table->index(['status']);
            $table->index(['reference']);
            $table->index(['placed_at']);
        });

        // 10. Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('ticket_type_id');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->integer('qty');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
            
            $table->index(['order_id']);
            $table->index(['event_id']);
            $table->index(['ticket_type_id']);
            $table->index(['schedule_id']);
        });

        // 11. Tickets
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('ticket_type_id');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->unsignedBigInteger('buyer_id');
            $table->string('code')->unique(); // QR code unique
            $table->enum('status', ['issued', 'used', 'refunded', 'void'])->default('issued');
            $table->datetime('issued_at');
            $table->datetime('used_at')->nullable();
            $table->timestamps();
            
            $table->index(['order_id']);
            $table->index(['event_id']);
            $table->index(['ticket_type_id']);
            $table->index(['schedule_id']);
            $table->index(['buyer_id']);
            $table->index(['status']);
            $table->index(['code']);
        });

        // 12. Checkins (journal de scan)
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('scanned_by'); // user organisateur
            $table->string('device_id')->nullable();
            $table->datetime('scanned_at');
            $table->enum('result', ['valid', 'duplicate', 'invalid'])->default('valid');
            $table->string('location_hint')->nullable();
            $table->timestamps();
            
            $table->index(['ticket_id']);
            $table->index(['scanned_by']);
            $table->index(['scanned_at']);
            $table->index(['result']);
        });

        // 13. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->enum('provider', ['airtel', 'moov', 'card', 'bank']);
            $table->string('provider_txn_ref')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['initiated', 'success', 'failed', 'cancelled'])->default('initiated');
            $table->datetime('paid_at')->nullable();
            $table->text('payload')->nullable(); // log court
            $table->timestamps();
            
            $table->index(['order_id']);
            $table->index(['provider']);
            $table->index(['provider_txn_ref']);
            $table->index(['status']);
            $table->index(['paid_at']);
        });

        // 14. Payment Transactions (webhooks)
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            $table->string('event_type'); // init, callback, webhook
            $table->longText('raw'); // données brutes
            $table->datetime('received_at');
            $table->timestamps();
            
            $table->index(['payment_id']);
            $table->index(['event_type']);
            $table->index(['received_at']);
        });

        // 15. Refunds
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->datetime('refunded_at')->nullable();
            $table->timestamps();
            
            $table->index(['order_id']);
            $table->index(['payment_id']);
            $table->index(['status']);
        });

        // 16. Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('channel', ['email', 'sms', 'push']);
            $table->string('template_key');
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->datetime('sent_at')->nullable();
            $table->string('ref_type')->nullable(); // order, ticket, etc.
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamps();
            
            $table->index(['user_id']);
            $table->index(['channel']);
            $table->index(['status']);
            $table->index(['ref_type', 'ref_id']);
        });

        // 17. Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->enum('scope_type', ['system', 'organizer', 'event', 'user']);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->timestamps();
            
            $table->index(['scope_type', 'scope_id']);
            $table->index(['key', 'scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('checkins');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('ticket_inventories');
        Schema::dropIfExists('ticket_prices');
        Schema::dropIfExists('ticket_types');
        Schema::dropIfExists('event_schedules');
        Schema::dropIfExists('events');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('event_categories');
        Schema::dropIfExists('organizer_user');
    }
};
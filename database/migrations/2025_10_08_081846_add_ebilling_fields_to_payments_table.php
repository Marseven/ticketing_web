<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('billing_id')->nullable()->after('provider_txn_ref');
            $table->string('merchant_id')->nullable()->after('billing_id');
            $table->string('customer_id')->nullable()->after('merchant_id');
            $table->string('transaction_id')->nullable()->after('customer_id');
            $table->string('payer_id')->nullable()->after('transaction_id');
            $table->string('payer_code')->nullable()->after('payer_id');
            $table->string('payment_system')->nullable()->after('payer_code');
            $table->string('sub_payment_system')->nullable()->after('payment_system');
            $table->string('payment_system_token')->nullable()->after('sub_payment_system');
            $table->string('payer_name')->nullable()->after('payment_system_token');
            $table->string('payer_email')->nullable()->after('payer_name');
            $table->string('short_description')->nullable()->after('payer_email');
            $table->timestamp('ebilling_created_at')->nullable()->after('short_description');
            $table->string('ebilling_state')->nullable()->after('ebilling_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'billing_id',
                'merchant_id',
                'customer_id',
                'transaction_id',
                'payer_id',
                'payer_code',
                'payment_system',
                'sub_payment_system',
                'payment_system_token',
                'payer_name',
                'payer_email',
                'short_description',
                'ebilling_created_at',
                'ebilling_state',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * - commission_percentage : override par événement (null => on utilise
     *   organizers.default_commission_percentage).
     * - approval_status : l'admin valide chaque événement manuellement avant
     *   qu'il puisse vendre (le % de commission pouvant varier par organisateur).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('commission_percentage', 5, 2)->nullable()->after('use_variable_pricing');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('commission_percentage');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            $table->datetime('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');

            $table->index(['approval_status']);
        });

        // Grandfathering : les événements déjà publiés (ou terminés) sont
        // considérés comme approuvés pour ne pas bloquer les ventes en cours.
        DB::table('events')
            ->whereIn('status', ['published', 'completed'])
            ->update(['approval_status' => 'approved', 'approved_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['approval_status']);
            $table->dropColumn([
                'commission_percentage',
                'approval_status',
                'approved_by',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};

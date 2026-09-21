<?php

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le numéro qui a payé, en colonne.
 *
 * Il n'existait que dans le JSON `payments.payload`, donc impossible à
 * interroger. La récupération d'un billet se fait désormais sur le nom et le
 * téléphone : ce numéro-là doit être cherchable, au même titre que celui du
 * compte (KYC) ou celui saisi à la commande.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payer_phone', 32)->nullable()->after('payer_email');
            $table->index('payer_phone');
        });

        // Reprise de l'existant : les paiements déjà passés gardent leur numéro
        // dans le payload, autant le remonter pour que les billets déjà vendus
        // restent récupérables.
        DB::table('payments')
            ->whereNotNull('payload')
            ->orderBy('id')
            ->chunkById(200, function ($payments) {
                foreach ($payments as $payment) {
                    $payload = json_decode((string) $payment->payload, true);
                    $phone = is_array($payload) ? ($payload['phone'] ?? null) : null;
                    $digits = PhoneNumber::digits($phone);

                    if ($digits === '') {
                        continue;
                    }

                    DB::table('payments')->where('id', $payment->id)->update(['payer_phone' => $digits]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payer_phone']);
            $table->dropColumn('payer_phone');
        });
    }
};

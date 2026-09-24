<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\OrganizerBalance;
use App\Models\Payout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Contrôle de la source du rappel de versement SHAP.
 *
 * Ce point d'entrée fait SORTIR de l'argent : un rappel « échec » recrédite le
 * solde de l'organisateur. Il était public, sans jeton ni signature ni limite
 * de débit. Un organisateur lit sa propre `external_reference` dans son espace
 * (le modèle Payout ne masque aucun champ), puis appelle ce rappel autant de
 * fois qu'il veut : chaque appel recrédite le montant.
 *
 * Il est désormais refusé par défaut. L'URL de rappel n'étant jamais transmise
 * à SHAP, rien de légitime n'en dépend : la réconciliation réelle passe par
 * `payout:check-status` toutes les cinq minutes.
 */
class ShapPayoutWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/webhooks/shap-payout';

    private function pendingPayout(float $amount = 50000, float $balance = 0): Payout
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        OrganizerBalance::create([
            'organizer_id' => $organizer->id, 'gateway' => 'airtelmoney',
            'balance' => $balance, 'pending_balance' => 0,
        ]);

        return Payout::create([
            'organizer_id' => $organizer->id,
            'reference' => 'REF-' . strtoupper(uniqid()),
            'external_reference' => 'PAYOUT_' . time() . '_ABCD1234',
            'gateway' => 'airtelmoney', 'payment_system_name' => 'airtelmoney',
            'payee_msisdn' => '{{PHONE_008}}', 'amount' => $amount,
            'payout_type' => 'manual', 'status' => 'processing', 'is_automatic' => false,
        ]);
    }

    private function forged(Payout $payout): array
    {
        return ['external_reference' => $payout->external_reference, 'status' => 'failed'];
    }

    public function test_a_callback_without_a_secret_is_refused(): void
    {
        Notification::fake();
        config(['services.shap.webhook_secret' => 'le-bon-secret']);
        $payout = $this->pendingPayout();

        $this->postJson(self::URL, $this->forged($payout))->assertStatus(403);

        $this->assertSame('processing', $payout->fresh()->status);
        $this->assertSame(0.0, (float) $payout->organizer->balances()->first()->balance,
            'aucun recrédit sur un appel non authentifié');
    }

    public function test_a_callback_with_a_wrong_secret_is_refused(): void
    {
        Notification::fake();
        config(['services.shap.webhook_secret' => 'le-bon-secret']);
        $payout = $this->pendingPayout();

        $this->postJson(self::URL, $this->forged($payout), ['X-Webhook-Secret' => 'presque'])
            ->assertStatus(403);

        $this->assertSame('processing', $payout->fresh()->status);
    }

    public function test_nothing_is_accepted_while_no_secret_is_configured(): void
    {
        // Refus par défaut : ce rappel sort de l'argent, et rien de légitime
        // n'en dépend tant que l'URL n'est pas transmise à SHAP.
        Notification::fake();
        config(['services.shap.webhook_secret' => null]);
        $payout = $this->pendingPayout();

        $this->postJson(self::URL, $this->forged($payout), ['X-Webhook-Secret' => 'peu importe'])
            ->assertStatus(403);

        $this->assertSame('processing', $payout->fresh()->status);
    }

    public function test_the_repeated_forgery_that_inflated_a_balance_is_now_blocked(): void
    {
        // Le scénario exact : dix rappels « échec » forgés, dix recrédits.
        Notification::fake();
        config(['services.shap.webhook_secret' => 'le-bon-secret']);
        $payout = $this->pendingPayout(amount: 50000);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(self::URL, $this->forged($payout))->assertStatus(403);
        }

        $this->assertSame(0.0, (float) $payout->organizer->balances()->first()->balance);
    }

    public function test_a_genuine_callback_is_processed(): void
    {
        Notification::fake();
        config(['services.shap.webhook_secret' => 'le-bon-secret']);
        $payout = $this->pendingPayout();

        $this->postJson(self::URL, $this->forged($payout), ['X-Webhook-Secret' => 'le-bon-secret'])
            ->assertOk();

        $this->assertSame('failed', $payout->fresh()->status);
    }

    public function test_the_secret_may_travel_in_the_callback_url(): void
    {
        // Même convention que le rappel d'encaissement : certains fournisseurs
        // ne savent poser que l'URL, pas les en-têtes.
        Notification::fake();
        config(['services.shap.webhook_secret' => 'le-bon-secret']);
        $payout = $this->pendingPayout();

        $this->postJson(self::URL . '?token=le-bon-secret', $this->forged($payout))->assertOk();

        $this->assertSame('failed', $payout->fresh()->status);
    }

    public function test_a_replayed_callback_credits_the_balance_only_once(): void
    {
        // Les passerelles rejouent leurs notifications. Le recrédit était
        // inconditionnel : la première retransmission doublait le montant rendu
        // à l'organisateur.
        Notification::fake();
        config(['services.shap.webhook_secret' => 'le-bon-secret']);
        $payout = $this->pendingPayout(amount: 50000);
        $headers = ['X-Webhook-Secret' => 'le-bon-secret'];

        $this->postJson(self::URL, $this->forged($payout), $headers)->assertOk();
        $this->postJson(self::URL, $this->forged($payout), $headers)->assertOk();
        $this->postJson(self::URL, $this->forged($payout), $headers)->assertOk();

        $this->assertSame(50000.0, (float) $payout->organizer->balances()->first()->balance,
            'le montant n\'est rendu qu\'une fois, quel que soit le nombre de rappels');
    }

    public function test_a_settled_payout_is_never_flipped_back(): void
    {
        // Le solde a déjà été rendu : encaisser ensuite paierait deux fois.
        Notification::fake();
        config(['services.shap.webhook_secret' => 'le-bon-secret']);
        $payout = $this->pendingPayout(amount: 50000);
        $headers = ['X-Webhook-Secret' => 'le-bon-secret'];

        $this->postJson(self::URL, $this->forged($payout), $headers)->assertOk();
        $this->postJson(self::URL, [
            'external_reference' => $payout->external_reference,
            'status' => 'success',
        ], $headers)->assertOk();

        $this->assertSame('failed', $payout->fresh()->status);
        $this->assertSame(50000.0, (float) $payout->organizer->balances()->first()->balance);
    }
}

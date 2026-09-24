<?php

namespace Tests\Feature;

use App\Services\EBillingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Lecture de l'état d'une facture dans la réponse de la passerelle.
 *
 * Le code ne lisait que `e_bill.state`. Or e-billing rend la facture À PLAT,
 * `state` à la racine : la lecture tombait donc systématiquement sur null,
 * alors que la réponse était un 200 parfaitement valide. Comme un état nul
 * signifie « on ne peut pas conclure », 19 paiements de production sont
 * ressortis « toujours en attente » sans qu'aucun n'ait été réellement lu —
 * 739 496 FCFA dont on ignorait s'ils avaient été encaissés.
 */
class EbillingBillStateShapeTest extends TestCase
{
    private function service(): EBillingService
    {
        config([
            'services.ebilling.username' => 'u',
            'services.ebilling.shared_key' => 'k',
            'services.ebilling.server_url' => 'https://gateway.test/api/v1/merchant/e_bills',
            'services.ebilling.post_url' => 'https://gateway.test/',
            'services.ebilling.auth_mode' => 'basic',
        ]);

        return new EBillingService();
    }

    public function test_the_state_is_read_at_the_root_of_the_response(): void
    {
        Http::fake(['*' => Http::response(['bill_id' => '557', 'state' => 'unpaid', 'amount_paid' => 0], 200)]);

        $this->assertSame('unpaid', $this->service()->getBillStatus('557')['bill_status']);
    }

    public function test_a_nested_shape_is_still_understood(): void
    {
        // La forme historique reste acceptée : on élargit la lecture, on ne la
        // déplace pas.
        Http::fake(['*' => Http::response(['e_bill' => ['state' => 'processed']], 200)]);

        $this->assertSame('processed', $this->service()->getBillStatus('557')['bill_status']);
    }

    public function test_a_refused_call_is_reported_as_a_failure(): void
    {
        Http::fake(['*' => Http::response(['error' => 'nope'], 404)]);

        $result = $this->service()->getBillStatus('557');

        $this->assertFalse($result['success']);
        $this->assertSame(404, $result['status']);
    }
}

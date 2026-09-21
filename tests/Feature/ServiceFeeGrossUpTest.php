<?php

namespace Tests\Feature;

use App\Support\ServiceFee;
use Tests\TestCase;

/**
 * La majoration des frais de service.
 *
 * E-billing prélève son taux sur le montant qu'on lui envoie. Ajouter 2,5 % au
 * prix du billet laissait donc l'organisateur en dessous de son prix : sur
 * 1 000 F, on envoyait 1 025, dont il ne restait que 999,375 après prélèvement.
 * La règle : le montant envoyé, diminué du taux, doit redonner le prix du
 * billet — qui, lui, ne bouge pas.
 */
class ServiceFeeGrossUpTest extends TestCase
{
    private const RATE = 2.5;

    public function test_a_thousand_francs_ticket_is_charged_1026(): void
    {
        $this->assertSame(1026, ServiceFee::totalToCharge(1000, self::RATE, true));
        $this->assertSame(26, ServiceFee::amount(1000, self::RATE, true));
    }

    public function test_what_the_gateway_leaves_covers_the_ticket_price(): void
    {
        foreach ([500, 1000, 2500, 5000, 10000, 12345, 999, 1] as $price) {
            $charged = ServiceFee::totalToCharge($price, self::RATE, true);
            $afterGateway = $charged * (1 - self::RATE / 100);

            $this->assertGreaterThanOrEqual(
                $price,
                $afterGateway,
                "sur un billet à {$price}, il reste {$afterGateway} après prélèvement"
            );
        }
    }

    public function test_the_old_formula_would_have_fallen_short(): void
    {
        // Ce que faisait le code avant : prix + 2,5 %.
        $naive = 1000 + (1000 * self::RATE / 100);
        $this->assertLessThan(1000, $naive * (1 - self::RATE / 100));
    }

    public function test_the_added_part_stays_minimal(): void
    {
        // Majorer ne doit pas faire payer beaucoup plus que nécessaire :
        // au plus un franc de plus que la majoration exacte.
        foreach ([500, 1000, 2500, 5000, 10000] as $price) {
            $exact = $price / (1 - self::RATE / 100);
            $this->assertLessThan(1.0, ServiceFee::totalToCharge($price, self::RATE, true) - $exact);
        }
    }

    public function test_platform_bearing_the_fee_charges_the_plain_price(): void
    {
        $this->assertSame(1000, ServiceFee::totalToCharge(1000, self::RATE, false));
        $this->assertSame(0, ServiceFee::amount(1000, self::RATE, false));
    }

    public function test_edge_cases_do_not_invent_fees(): void
    {
        $this->assertSame(0, ServiceFee::totalToCharge(0, self::RATE, true), 'billet gratuit');
        $this->assertSame(1000, ServiceFee::totalToCharge(1000, 0, true), 'taux nul');
        $this->assertSame(1000, ServiceFee::totalToCharge(1000, 100, true), 'taux aberrant : on ne majore pas');
    }
}

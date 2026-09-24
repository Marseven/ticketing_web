<?php

namespace Tests\Feature;

use App\Services\EBillingService;
use Tests\TestCase;

/**
 * Construction de l'URL d'interrogation d'une facture.
 *
 * `rtrim($url, '/e_bills')` retire des CARACTÈRES et non un suffixe : toute
 * base dont le segment précédent se termine par l'une de ces lettres était
 * rongée. L'appel partait alors vers une adresse tronquée, et comme
 * `getBillStatus` ne lève pas d'exception, la panne passait pour une facture
 * simplement pas encore réglée.
 */
class EbillingBillUrlTest extends TestCase
{
    private function urlFor(string $base, string $billId = '5576430277'): string
    {
        config([
            'services.ebilling.username' => 'u',
            'services.ebilling.shared_key' => 'k',
            'services.ebilling.server_url' => $base,
            'services.ebilling.post_url' => 'https://example.test/',
        ]);

        return (new EBillingService())->billStatusUrl($billId);
    }

    public function test_the_usual_base_is_unchanged(): void
    {
        $this->assertSame(
            'https://stg.billing-easy.net/api/v1/merchant/e_bills/5576430277',
            $this->urlFor('https://stg.billing-easy.net/api/v1/merchant/e_bills'),
        );
    }

    public function test_a_base_ending_in_trimmed_letters_survives(): void
    {
        // « ebills » finissait mangé lettre par lettre jusqu'à « /ap ».
        $this->assertSame(
            'https://x.test/api/ebills/e_bills/5576430277',
            $this->urlFor('https://x.test/api/ebills/e_bills'),
        );

        $this->assertSame(
            'https://x.test/bills/e_bills/5576430277',
            $this->urlFor('https://x.test/bills/e_bills'),
        );
    }

    public function test_a_base_without_the_suffix_still_works(): void
    {
        $this->assertSame(
            'https://x.test/api/v1/e_bills/5576430277',
            $this->urlFor('https://x.test/api/v1'),
        );
    }

    public function test_a_trailing_slash_does_not_double_up(): void
    {
        $this->assertSame(
            'https://x.test/api/v1/e_bills/5576430277',
            $this->urlFor('https://x.test/api/v1/e_bills/'),
        );
    }
}

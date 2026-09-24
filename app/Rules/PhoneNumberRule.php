<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Un numéro de téléphone exploitable.
 *
 * Les champs téléphone n'avaient aucun contrôle de format : « abc », « 12 » ou
 * une ligne entière de texte partaient jusqu'à la passerelle de paiement avant
 * d'être rejetés — l'utilisateur voyait alors un échec de paiement là où il
 * avait simplement mal saisi son numéro.
 *
 * On vérifie la STRUCTURE, pas l'opérateur. Le même numéro arrive sous toutes
 * les formes (« +241 77 44 36 38 », « 077443638 », « 24177443638 »), et
 * l'attribution des préfixes change au gré des opérateurs : refuser un préfixe
 * inconnu reviendrait à bloquer des clients légitimes le jour où une plage
 * s'ouvre. On s'assure donc seulement qu'il y a de quoi appeler.
 */
class PhoneNumberRule implements ValidationRule
{
    /** Longueur maximale d'un numéro international (recommandation E.164). */
    private const MAX_DIGITS = 15;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = PhoneNumber::digits(is_scalar($value) ? (string) $value : '');

        if ($digits === '') {
            $fail('Le numéro de téléphone est invalide.');

            return;
        }

        if (strlen($digits) < PhoneNumber::SIGNIFICANT_DIGITS) {
            $fail('Le numéro de téléphone est trop court.');

            return;
        }

        if (strlen($digits) > self::MAX_DIGITS) {
            $fail('Le numéro de téléphone est trop long.');

            return;
        }

        // Avec l'indicatif du Gabon, il doit rester exactement huit chiffres
        // d'abonné — « +241 77 44 36 » passerait sinon pour un numéro complet.
        //
        // ⚠️ Le zéro national survit souvent à l'ajout de l'indicatif :
        // « +241 0 77 85 59 49 » est une saisie courante, et la refuser
        // bloquait des clients légitimes au moment de payer.
        if (str_starts_with($digits, '241')) {
            $abonne = ltrim(substr($digits, 3), '0');

            if (strlen($abonne) !== PhoneNumber::SIGNIFICANT_DIGITS) {
                $fail('Le numéro gabonais doit comporter huit chiffres après l\'indicatif.');
            }
        }
    }
}

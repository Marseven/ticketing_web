<?php

namespace App\Support;

/**
 * Normalisation des numéros de téléphone gabonais.
 *
 * Le même numéro arrive sous toutes les formes : « +241 77 44 36 38 »,
 * « 077443638 », « 24177443638 ». Pour retrouver un billet à partir du numéro,
 * on compare donc les derniers chiffres, seuls stables d'une saisie à l'autre.
 */
class PhoneNumber
{
    /** Nombre de chiffres significatifs comparés (l'abonné, sans indicatif ni 0). */
    public const SIGNIFICANT_DIGITS = 8;

    /**
     * Ne garde que les chiffres : « +241 77 44-36.38 » → « 24177443638 ».
     */
    public static function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    /**
     * Fin du numéro, servant de clé de comparaison. Chaîne vide si le numéro est
     * trop court pour être comparé sans risque de faux positif.
     */
    public static function key(?string $value): string
    {
        $digits = self::digits($value);

        return strlen($digits) >= self::SIGNIFICANT_DIGITS
            ? substr($digits, -self::SIGNIFICANT_DIGITS)
            : '';
    }

    /**
     * Expression SQL qui retire les séparateurs d'une colonne, pour comparer une
     * colonne stockée telle que saisie (`orders.guest_phone`, `users.phone`).
     * Compatible MySQL et SQLite.
     */
    public static function sqlDigits(string $column): string
    {
        $expr = $column;
        foreach (['+', ' ', '-', '.', '(', ')'] as $separator) {
            $expr = "REPLACE({$expr}, '{$separator}', '')";
        }

        return $expr;
    }
}

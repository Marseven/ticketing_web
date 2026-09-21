<?php

namespace App\Support;

/**
 * Frais de service e-billing ajoutés au client.
 *
 * ⚠️ E-billing prélève son pourcentage **sur le montant qu'on lui envoie**, pas
 * sur le prix du billet. Ajouter 2,5 % au prix ne suffit donc pas :
 *
 *   1 000 + 2,5 %       = 1 025 envoyés
 *   1 025 − 2,5 % de 1 025 = 999,375 → l'organisateur touche 999, pas 1 000.
 *
 * Il faut **majorer** : envoyer `prix / (1 − taux)`, de sorte que le
 * prélèvement ramène exactement au prix du billet.
 *
 *   1 000 / 0,975 = 1 025,64 → 1 026 envoyés
 *   1 026 − 2,5 % de 1 026 = 1 000,35 → l'organisateur touche bien ses 1 000.
 *
 * Le XAF n'a pas de centimes : tout est en francs entiers, et l'arrondi se fait
 * **vers le haut** pour que l'organisateur ne soit jamais en dessous du prix.
 * Le prix du billet, lui, ne bouge pas : seule la part ajoutée varie.
 */
class ServiceFee
{
    /**
     * Montant total à faire payer au client.
     *
     * @param  float  $baseAmount   prix des billets, inchangé
     * @param  float  $ratePercent  taux e-billing (ex. 2.5)
     */
    public static function totalToCharge(float $baseAmount, float $ratePercent, bool $customerBears): int
    {
        $base = (int) round($baseAmount);

        if (! $customerBears || $base <= 0 || $ratePercent <= 0 || $ratePercent >= 100) {
            return $base;
        }

        return (int) ceil($base / (1 - $ratePercent / 100));
    }

    /**
     * Part ajoutée au prix des billets (0 si la plateforme absorbe les frais).
     */
    public static function amount(float $baseAmount, float $ratePercent, bool $customerBears): int
    {
        return self::totalToCharge($baseAmount, $ratePercent, $customerBears) - (int) round($baseAmount);
    }
}

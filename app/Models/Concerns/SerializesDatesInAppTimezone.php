<?php

namespace App\Models\Concerns;

use DateTimeInterface;

/**
 * Sérialise les dates avec le décalage du fuseau de l'application.
 *
 * Par défaut, Eloquent convertit une date en UTC au moment de produire du
 * JSON : une séance saisie à 09:00 heure de Libreville ressortait
 * « 2026-12-19T08:00:00.000000Z ». Les formulaires d'administration
 * remplissent leurs champs `datetime-local` en découpant les seize premiers
 * caractères de cette chaîne — ils affichaient donc 08:00, et l'enregistrement
 * stockait 08:00. À chaque passage dans le formulaire, l'horaire reculait
 * d'une heure de plus.
 *
 * Le décalage est désormais explicite (« 2026-12-19T09:00:00+01:00 ») :
 * l'heure lisible dans la chaîne est celle que l'organisateur a saisie, et
 * elle ne dépend pas du fuseau du navigateur qui l'affiche. `new Date()`
 * interprète correctement les deux formes, rien d'autre ne change.
 */
trait SerializesDatesInAppTimezone
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('c');
    }
}

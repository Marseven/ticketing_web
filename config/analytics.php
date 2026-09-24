<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mesure d'audience
    |--------------------------------------------------------------------------
    |
    | ⚠️ La plateforme mesure DÉJÀ sa fréquentation elle-même, sans cookie et
    | sans service extérieur : voir la page Supervision de l'administration.
    | Ce réglage ne concerne qu'un service TIERS, en complément. Il reste sur
    | « none » par défaut, d'autant que les bloqueurs de publicité coupent les
    | domaines d'analytics connus — un outil tiers sous-compte donc une part
    | notable des visiteurs, là où la mesure maison passe par notre domaine.
    |
    | Fournisseur de statistiques de fréquentation. Quatre valeurs possibles :
    |
    |   none      aucune mesure (valeur par défaut)
    |   plausible Plausible Analytics — sans cookie, sans donnée personnelle
    |   umami     Umami — sans cookie, auto-hébergeable
    |   ga4       Google Analytics 4 — dépose des cookies
    |
    | ⚠️ Plausible et Umami ne déposent aucun cookie et n'identifient personne :
    | ils se chargent directement. Google Analytics, lui, dépose des cookies et
    | ne se charge qu'après un consentement explicite du visiteur — c'est ce
    | qu'impose la loi n° 001/2011 relative à la protection des données à
    | caractère personnel. Préférer une solution sans cookie évite la bannière.
    |
    | ⚠️ Ces valeurs sont lues par `config()` et JAMAIS par `env()` en dehors de
    | ce fichier : une fois la configuration mise en cache (`artisan optimize`),
    | `env()` renvoie null partout ailleurs.
    |
    */

    'provider' => env('ANALYTICS_PROVIDER', 'none'),

    /** Domaine déclaré côté fournisseur (Plausible et Umami). */
    'domain' => env('ANALYTICS_DOMAIN', env('APP_URL') ? parse_url(env('APP_URL'), PHP_URL_HOST) : null),

    /** Identifiant de mesure : « G-XXXXXXX » pour GA4, identifiant de site pour Umami. */
    'site_id' => env('ANALYTICS_SITE_ID'),

    /**
     * Adresse du script. Utile pour une instance auto-hébergée, ou pour servir
     * le script depuis son propre domaine et échapper aux bloqueurs.
     */
    'script_url' => env('ANALYTICS_SCRIPT_URL'),

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inscription autonome comme organisateur
    |--------------------------------------------------------------------------
    |
    | Quand c'est faux, une inscription publique crée toujours un compte
    | client : le drapeau « is_organizer » envoyé par le formulaire est
    | ignoré. Les comptes organisateurs sont alors ouverts par
    | l'administration, après échange avec la personne.
    |
    | Masquer les liens dans l'interface ne suffit pas : l'inscription est un
    | appel d'API public, et n'importe qui peut le faire directement. Le refus
    | doit donc être posé côté serveur.
    |
    | Pour rouvrir l'inscription autonome : ORGANIZER_SELF_SIGNUP=true dans le
    | .env, puis rétablir les liens retirés du pied de page, de l'en-tête
    | mobile, de l'accueil, de « Comment ça marche » et de la page de choix
    | organisateur.
    |
    | ⚠️ Valeur lue par `config()` et jamais par `env()` ailleurs : une fois la
    | configuration mise en cache, `env()` renvoie null partout.
    |
    */

    'organizer_self_signup' => env('ORGANIZER_SELF_SIGNUP', false),

];

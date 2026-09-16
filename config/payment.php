<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Frais de service (e-billing)
    |--------------------------------------------------------------------------
    |
    | Pourcentage des frais de service (frais e-billing) appliqués au prix des
    | billets. Ces frais ne sont facturés au client QUE lorsque l'événement est
    | configuré avec service_fee_bearer = 'customer'. Sinon la plateforme les
    | absorbe et le client paie exactement le prix affiché.
    |
    */

    'service_fee_percent' => (float) env('SERVICE_FEE_PERCENT', 2.5),

];

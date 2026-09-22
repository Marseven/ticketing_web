<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * La passerelle de paiement ne peut pas être sollicitée.
 *
 * Configuration incomplète, identifiants refusés, service injoignable : autant
 * de causes qui regardent l'exploitant, pas l'acheteur. Le détail va au
 * journal ; l'écran, lui, ne montre que `PUBLIC_MESSAGE`.
 *
 * Le message technique ne doit jamais remonter tel quel : il n'aide pas le
 * client, et il expose le fonctionnement interne (noms de variables
 * d'environnement, URLs, identifiants).
 */
class PaymentGatewayUnavailable extends RuntimeException
{
    public const PUBLIC_MESSAGE = 'Le paiement est momentanément indisponible. Merci de réessayer dans quelques minutes.';

    public function publicMessage(): string
    {
        return self::PUBLIC_MESSAGE;
    }
}

<?php

namespace App\Support;

/**
 * Masque les données personnelles avant de les écrire au journal.
 *
 * Les appels à la passerelle journalisaient la réponse entière : numéro de
 * téléphone, e-mail et nom de chaque acheteur. Le planificateur interrogeant
 * désormais les factures toutes les cinq minutes, cela faisait des centaines
 * de copies par jour de données personnelles dans `storage/logs` — un fichier
 * sans rotation, lisible par quiconque accède au serveur, aux sauvegardes ou à
 * l'hébergement mutualisé. La loi gabonaise n° 001/2011 impose d'en limiter la
 * conservation ; un journal éternel est le contraire.
 *
 * On garde de quoi DIAGNOSTIQUER — reconnaître un numéro qu'on a sous les yeux,
 * repérer deux acheteurs distincts — sans conserver de quoi identifier ou
 * contacter qui que ce soit.
 */
class Redact
{
    /** Effacés entièrement : leur seule présence est déjà une fuite. */
    private const SECRETS = [
        'password', 'secret', 'token', 'authorization', 'api_key', 'apikey',
        'shared_key', 'client_secret', 'two_factor_secret', 'recovery_code',
    ];

    /** Numéros : on garde les deux derniers chiffres. */
    private const PHONES = [
        'msisdn', 'phone', 'telephone', 'tel', 'payer_msisdn', 'payee_msisdn',
        'guest_phone', 'phone_number', 'payer_phone', 'instant_payout_phone',
        'payout_phone_number',
    ];

    /** Adresses électroniques : on garde le domaine. */
    private const EMAILS = ['email', 'mail', 'payer_email', 'guest_email', 'contact_email'];

    /** Noms et adresses postales : on garde l'initiale. */
    private const NAMES = [
        'payer_name', 'payee_name', 'guest_name', 'customer_name', 'first_name',
        'last_name', 'payer_address', 'payer_city', 'address',
    ];

    /**
     * @param  mixed  $data
     * @return mixed
     */
    public static function payload($data, int $depth = 0)
    {
        if ($depth > 8 || ! is_array($data)) {
            return $data;
        }

        $out = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $out[$key] = self::payload($value, $depth + 1);

                continue;
            }

            $out[$key] = self::value((string) $key, $value);
        }

        return $out;
    }

    /** @return mixed */
    private static function value(string $key, $value)
    {
        $name = strtolower($key);

        if (self::matches($name, self::SECRETS)) {
            return '[masqué]';
        }

        if (! is_scalar($value) || $value === '' || $value === null) {
            return $value;
        }

        if (self::matches($name, self::PHONES)) {
            return self::phone((string) $value);
        }

        if (self::matches($name, self::EMAILS)) {
            return self::email((string) $value);
        }

        if (self::matches($name, self::NAMES)) {
            return self::name((string) $value);
        }

        return $value;
    }

    /**
     * Comparaison sur le nom EXACT de la clé, jamais en sous-chaîne : sinon
     * « payment_system_name » passerait pour un nom de personne et
     * « nom_du_gateway » deviendrait illisible au diagnostic.
     *
     * @param  array<int, string>  $liste
     */
    private static function matches(string $key, array $liste): bool
    {
        return in_array($key, $liste, true);
    }

    private static function phone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return strlen($digits) >= 2
            ? str_repeat('•', max(0, strlen($digits) - 2)) . substr($digits, -2)
            : '[masqué]';
    }

    private static function email(string $value): string
    {
        $at = strrpos($value, '@');

        if ($at === false || $at === 0) {
            return '[masqué]';
        }

        return substr($value, 0, 1) . '•••' . substr($value, $at);
    }

    private static function name(string $value): string
    {
        $value = trim($value);

        return $value === '' ? '[masqué]' : mb_substr($value, 0, 1) . '•••';
    }
}

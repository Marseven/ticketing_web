<?php

namespace App\Services;

/**
 * Codes à usage unique basés sur le temps (TOTP, RFC 6238).
 *
 * C'est ce que lisent Google Authenticator, Authy ou 1Password : un secret
 * partagé, le temps découpé en fenêtres de 30 secondes, et un HMAC tronqué.
 *
 * Écrit ici plutôt qu'importé : l'algorithme tient en quelques lignes, il est
 * figé depuis 2011, et le faire nous-mêmes permet de le vérifier contre les
 * vecteurs de test officiels de la RFC — ce qu'aucune dépendance ne garantit
 * à notre place. Voir TotpServiceTest.
 */
class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function __construct(
        private int $digits = 6,
        private int $period = 30,
        private string $algorithm = 'sha1',
    ) {
    }

    /**
     * Un secret neuf, en base32 — le format qu'attendent les applications
     * d'authentification. 20 octets, comme le recommande la RFC pour SHA-1.
     */
    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    /**
     * Le code attendu à un instant donné.
     */
    public function codeAt(string $secret, ?int $timestamp = null): string
    {
        $counter = intdiv($timestamp ?? time(), $this->period);

        return $this->hotp($this->base32Decode($secret), $counter);
    }

    /**
     * Le code fourni est-il valable ?
     *
     * `$window` autorise les fenêtres voisines : l'horloge du téléphone dérive,
     * et l'utilisateur met quelques secondes à recopier. Une fenêtre de part et
     * d'autre couvre ±30 s, ce que recommande la RFC — au-delà on allongerait
     * inutilement la durée de vie d'un code.
     *
     * La comparaison est à temps constant : comparer avec `==` laisserait
     * mesurer, caractère par caractère, à quel endroit le code diverge.
     */
    public function verify(string $secret, string $code, ?int $timestamp = null, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($code) !== $this->digits) {
            return false;
        }

        $now = $timestamp ?? time();
        $valid = false;

        // On parcourt TOUTES les fenêtres même après un succès : sortir dès la
        // bonne trouvée rendrait la durée de la réponse dépendante de la
        // fenêtre, donc observable.
        for ($offset = -$window; $offset <= $window; $offset++) {
            $candidate = $this->codeAt($secret, $now + ($offset * $this->period));
            $valid = hash_equals($candidate, $code) || $valid;
        }

        return $valid;
    }

    /**
     * L'URI que l'on encode en QR code. `issuer` apparaît dans l'application
     * de l'utilisateur, à côté de son identifiant.
     */
    public function provisioningUri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($account);

        return 'otpauth://totp/' . $label . '?' . http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper($this->algorithm),
            'digits' => $this->digits,
            'period' => $this->period,
        ]);
    }

    /**
     * HOTP (RFC 4226) : HMAC du compteur, puis troncature dynamique.
     */
    private function hotp(string $key, int $counter): string
    {
        $hash = hash_hmac($this->algorithm, pack('J', $counter), $key, true);

        // Les 4 bits de poids faible du dernier octet désignent où lire.
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % (10 ** $this->digits)), $this->digits, '0', STR_PAD_LEFT);
    }

    public function base32Encode(string $bytes): string
    {
        $out = '';
        $buffer = 0;
        $bits = 0;

        foreach (str_split($bytes) as $byte) {
            $buffer = ($buffer << 8) | ord($byte);
            $bits += 8;

            while ($bits >= 5) {
                $bits -= 5;
                $out .= self::ALPHABET[($buffer >> $bits) & 0x1F];
            }
        }

        if ($bits > 0) {
            $out .= self::ALPHABET[($buffer << (5 - $bits)) & 0x1F];
        }

        return $out;
    }

    public function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret) ?? '');
        $out = '';
        $buffer = 0;
        $bits = 0;

        foreach (str_split($secret) as $char) {
            $buffer = ($buffer << 5) | strpos(self::ALPHABET, $char);
            $bits += 5;

            if ($bits >= 8) {
                $bits -= 8;
                $out .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $out;
    }
}

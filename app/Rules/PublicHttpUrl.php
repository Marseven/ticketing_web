<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Une URL que le serveur a le droit d'aller chercher.
 *
 * La règle `url` de Laravel dit seulement qu'une adresse est bien formée. Elle
 * accepte `http://127.0.0.1:3306`, `http://169.254.169.254/` ou l'adresse d'une
 * machine voisine sur le réseau de l'hébergeur. Dès lors qu'on demande au
 * serveur d'aller la consulter, l'utilisateur se sert de nous comme d'un relais
 * pour atteindre ce que lui ne peut pas joindre — et la réponse (accessible ou
 * non, quel type de contenu) lui sert de sonde.
 *
 * On exige donc http(s), et on refuse toute adresse qui ne soit pas publiquement
 * routable : boucle locale, réseaux privés, lien-local — dont l'adresse de
 * métadonnées des hébergeurs cloud — et plages réservées.
 */
class PublicHttpUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('L\'adresse est invalide.');

            return;
        }

        $parts = parse_url($value);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            $fail('Seules les adresses http et https sont acceptées.');

            return;
        }

        foreach ($this->addresses($host) as $ip) {
            if (! $this->isPubliclyRoutable($ip)) {
                // Volontairement vague : préciser « adresse interne » dirait à
                // l'appelant que sa cible existe, ce qui est déjà un renseignement.
                $fail('Cette adresse n\'est pas accessible.');

                return;
            }
        }
    }

    /**
     * Toutes les adresses derrière ce nom. Un nom peut résoudre vers plusieurs
     * adresses, et il suffit d'une seule interne pour que l'appel soit refusé.
     *
     * @return array<int, string>
     */
    private function addresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $records = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];

        $ips = array_values(array_filter(array_map(
            fn ($record) => $record['ip'] ?? $record['ipv6'] ?? null,
            $records
        )));

        // Un nom qui ne résout pas ne mène nulle part : inutile d'aller voir.
        return $ips ?: ['0.0.0.0'];
    }

    private function isPubliclyRoutable(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}

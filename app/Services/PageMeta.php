<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Métadonnées de partage d'une page.
 *
 * L'application est une SPA : le serveur renvoie toujours la même coquille
 * HTML, quelle que soit l'URL. Les aperçus de lien, eux, ne lisent QUE cette
 * coquille — WhatsApp, Facebook et les moteurs de recherche n'exécutent pas le
 * JavaScript. Sans traitement côté serveur, un lien d'événement partagé
 * affichait donc le titre générique du site à la place de l'affiche, du nom et
 * de la date de l'événement.
 *
 * Or au Gabon un billet se partage par WhatsApp : l'aperçu du lien est la
 * première chose que voit l'acheteur.
 *
 * ⚠️ Cette résolution tourne sur CHAQUE page servie. Elle ne doit toucher la
 * base que pour les chemins qui peuvent désigner un événement, et le résultat
 * est mis en cache.
 */
class PageMeta
{
    /** Cinq minutes : assez pour absorber un partage viral, assez court pour qu'une correction d'affiche se voie. */
    private const TTL = 300;

    /**
     * Premiers segments d'URL qui n'ont jamais désigné un événement.
     * Sans cette liste, ouvrir l'espace d'administration déclencherait une
     * requête en base à chaque page.
     */
    private const RESERVED = [
        'admin', 'account', 'organizer', 'scanner', 'api', 'login', 'register',
        'forgot-password', 'reset-password', 'verify-email', 'logout',
        'events', 'checkout', 'cart', 'search', 'contact', 'help',
        'how-it-works', 'terms', 'privacy', 'sales-terms', 'legal-notice',
        'retrieve-ticket', 'ticket', 'tickets', 'orders', 'suivi', 'tracking',
        'storage', 'build', 'images', 'videos', 'favicon.ico', 'robots.txt',
        'sitemap.xml', 'sw.js', 'manifest.webmanifest',
    ];

    /**
     * Pages servies mais tenues hors des moteurs de recherche.
     *
     * Les mentions légales attendent l'identité de l'éditeur : la page reste
     * consultable pour être relue et complétée, mais elle n'a rien à faire
     * dans un index tant qu'elle porte des champs à remplir. Retirer cette
     * entrée une fois la page complétée.
     */
    private const NOINDEX = ['legal-notice'];

    /** Titres des pages fixes, pour que chaque URL ait le sien. */
    private const STATIC_TITLES = [
        'events' => 'Tous les événements',
        'contact' => 'Nous contacter',
        'help' => 'Aide',
        'how-it-works' => 'Comment ça marche',
        'terms' => "Conditions générales d'utilisation",
        'privacy' => 'Politique de confidentialité',
        'sales-terms' => 'Conditions générales de vente',
        'legal-notice' => 'Mentions légales',
        'retrieve-ticket' => 'Récupérer mon billet',
    ];

    /**
     * @return array{title:string, description:string, image:string, type:string, url:string, card:string, jsonld:?string, robots:?string}
     */
    public function forPath(string $path): array
    {
        $brand = Setting::branding();
        $meta = $this->defaults($brand);

        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn ($s) => $s !== ''));
        $first = $segments[0] ?? null;

        if ($first === null) {
            return $meta; // page d'accueil
        }

        if (isset(self::STATIC_TITLES[$first])) {
            $meta['title'] = self::STATIC_TITLES[$first] . ' · ' . $brand['app_name'];

            if (in_array($first, self::NOINDEX, true)) {
                $meta['robots'] = 'noindex, nofollow';
            }

            return $meta;
        }

        if (in_array($first, self::RESERVED, true)) {
            return $meta;
        }

        // `/{slug}` mène à l'achat, `/{slug}/details` à la fiche : ce sont les
        // deux formes qu'on voit passer dans les conversations.
        $isEventPath = count($segments) === 1
            || (count($segments) === 2 && $segments[1] === 'details');

        if (! $isEventPath) {
            return $meta;
        }

        return $this->forEvent($first, $meta, $brand) ?? $meta;
    }

    /** @return array<string, mixed> */
    private function defaults(array $brand): array
    {
        return [
            'title' => $brand['meta_title'],
            'description' => $brand['meta_description'],
            'image' => url($brand['og_image']),
            'type' => 'website',
            'url' => url()->current(),
            'card' => 'summary',
            'jsonld' => null,
            'robots' => null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function forEvent(string $slug, array $meta, array $brand): ?array
    {
        $data = Cache::remember('page-meta:event:' . $slug, self::TTL, function () use ($slug) {
            $event = Event::query()
                ->where('slug', $slug)
                ->where('status', 'published')
                ->where('approval_status', 'approved')
                ->where('is_active', true)
                ->with(['venue:id,name,city', 'schedules' => fn ($q) => $q->where('status', 'active')->orderBy('starts_at')])
                ->first();

            if (! $event) {
                // On mémorise l'absence aussi : sinon un robot qui balaie des
                // URL inexistantes ferait une requête à chaque appel.
                return ['found' => false];
            }

            $schedule = $event->schedules->first();

            return [
                'found' => true,
                'title' => $event->title,
                'description' => $this->describe($event, $schedule),
                'image' => $event->image,
                'starts_at' => $schedule?->starts_at?->toIso8601String(),
                'ends_at' => $schedule?->ends_at?->toIso8601String(),
                'venue' => $event->venue?->name,
                'city' => $event->venue?->city,
            ];
        });

        if (empty($data['found'])) {
            return null;
        }

        $meta['title'] = $data['title'] . ' · ' . $brand['app_name'];
        $meta['description'] = $data['description'];
        $meta['type'] = 'article';

        if (! empty($data['image'])) {
            $meta['image'] = $data['image'];
            // Une affiche mérite la grande vignette ; le logo carré, non.
            $meta['card'] = 'summary_large_image';
        }

        $meta['jsonld'] = $this->jsonLd($data, $meta);

        return $meta;
    }

    /**
     * Une description d'aperçu : date, lieu, puis le début du texte de
     * l'événement. C'est ce que lit quelqu'un avant de décider d'ouvrir.
     */
    private function describe(Event $event, $schedule): string
    {
        $parts = [];

        if ($schedule?->starts_at) {
            $parts[] = ucfirst($schedule->starts_at->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH[h]mm'));
        }

        if ($event->venue?->name) {
            $parts[] = trim($event->venue->name . ($event->venue->city ? ', ' . $event->venue->city : ''));
        }

        $intro = trim(preg_replace('/\s+/', ' ', strip_tags((string) $event->description)));

        if ($intro !== '') {
            $parts[] = mb_strimwidth($intro, 0, 150, '…');
        }

        $description = implode(' · ', array_filter($parts));

        return $description !== ''
            ? mb_strimwidth($description, 0, 300, '…')
            : 'Réservez votre billet sur Primea.';
    }

    /**
     * Données structurées schema.org/Event : c'est ce qui permet à un moteur
     * de recherche d'afficher la date et le lieu directement dans ses
     * résultats.
     */
    private function jsonLd(array $data, array $meta): ?string
    {
        if (empty($data['starts_at'])) {
            return null;
        }

        // Une date de fin antérieure au début fait rejeter tout le bloc par
        // les moteurs. La donnée existe en base (séances mal saisies) : on
        // préfère omettre la fin plutôt que publier un événement impossible.
        $endDate = $data['ends_at'] ?? null;

        if ($endDate !== null && strtotime($endDate) <= strtotime($data['starts_at'])) {
            $endDate = null;
        }

        $payload = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $data['title'],
            'startDate' => $data['starts_at'],
            'endDate' => $endDate,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'image' => $meta['image'] ?: null,
            'description' => $data['description'],
            'url' => $meta['url'],
            'location' => $data['venue'] ? array_filter([
                '@type' => 'Place',
                'name' => $data['venue'],
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'addressLocality' => $data['city'],
                    'addressCountry' => 'GA',
                ]),
            ]) : null,
        ]);

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}

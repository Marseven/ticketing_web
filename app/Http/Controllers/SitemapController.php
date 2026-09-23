<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Plan du site.
 *
 * L'application est une SPA : un moteur de recherche ne découvre les pages
 * d'événements qu'en suivant des liens rendus en JavaScript, ce qu'il ne fait
 * pas toujours. Le plan du site les lui donne directement, avec leur date de
 * dernière modification.
 */
class SitemapController extends Controller
{
    /** Une heure : un événement publié doit être trouvable le jour même. */
    private const TTL = 3600;

    /** Pages fixes, avec leur importance relative. */
    private const STATIC_PAGES = [
        ['', '1.0', 'daily'],
        ['events', '0.9', 'daily'],
        ['how-it-works', '0.5', 'monthly'],
        ['help', '0.4', 'monthly'],
        ['contact', '0.4', 'monthly'],
        ['terms', '0.3', 'yearly'],
        ['privacy', '0.3', 'yearly'],
        ['sales-terms', '0.3', 'yearly'],
        ['legal-notice', '0.3', 'yearly'],
    ];

    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', self::TTL, fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function build(): string
    {
        $urls = [];

        foreach (self::STATIC_PAGES as [$path, $priority, $frequency]) {
            $urls[] = $this->entry(url($path), null, $priority, $frequency);
        }

        Event::query()
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->where('is_active', true)
            ->select(['slug', 'updated_at'])
            ->orderByDesc('updated_at')
            ->limit(2000)
            ->each(function ($event) use (&$urls) {
                // La fiche détaillée est la page à indexer : la racine du slug
                // mène directement à l'achat.
                $urls[] = $this->entry(
                    url($event->slug . '/details'),
                    $event->updated_at?->toAtomString(),
                    '0.8',
                    'weekly'
                );
            });

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . implode("\n", $urls) . "\n"
            . '</urlset>';
    }

    private function entry(string $url, ?string $lastModified, string $priority, string $frequency): string
    {
        $entry = '  <url>' . "\n"
            . '    <loc>' . htmlspecialchars($url, ENT_XML1) . '</loc>' . "\n";

        if ($lastModified) {
            $entry .= '    <lastmod>' . $lastModified . '</lastmod>' . "\n";
        }

        return $entry
            . '    <changefreq>' . $frequency . '</changefreq>' . "\n"
            . '    <priority>' . $priority . '</priority>' . "\n"
            . '  </url>';
    }
}

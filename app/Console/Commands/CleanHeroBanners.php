<?php

namespace App\Console\Commands;

use App\Models\HeroBanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanHeroBanners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hero-banners:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoyer les hero banners dont les fichiers n\'existent pas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Recherche des hero banners avec fichiers manquants...');

        $heroBanners = HeroBanner::all();
        $cleaned = 0;

        foreach ($heroBanners as $banner) {
            // Ignorer les URLs externes
            if (filter_var($banner->media_url, FILTER_VALIDATE_URL)) {
                continue;
            }

            // Vérifier si c'est un fichier local
            if (str_starts_with($banner->media_url, '/storage/')) {
                $filePath = str_replace('/storage/', '', $banner->media_url);

                if (!Storage::disk('public')->exists($filePath)) {
                    $this->warn("❌ Fichier manquant: {$banner->media_url}");
                    $this->warn("   Hero Banner ID: {$banner->id} - Titre: " . ($banner->title ?: 'Sans titre'));

                    if ($this->confirm('Supprimer ce hero banner ?', true)) {
                        $banner->delete();
                        $cleaned++;
                        $this->info("   ✅ Hero banner supprimé");
                    }
                }
            }
        }

        if ($cleaned === 0) {
            $this->info('✨ Aucun hero banner à nettoyer');
        } else {
            $this->info("✅ {$cleaned} hero banner(s) nettoyé(s)");
        }

        return 0;
    }
}

<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Log;

/**
 * QR du billet, rendu en PNG avec GD.
 *
 * Pourquoi ne pas utiliser `QrCode::format('png')` : le pilote PNG de
 * simple-qrcode exige l'extension **imagick**, absente de l'hébergement. Le SVG,
 * lui, s'affiche bien partout mais **ne se rasterise pas correctement dans un
 * canvas sous WebKit** : à la capture du billet sur iPhone, Safari ignorait le
 * `viewBox` et ne dessinait qu'un coin du QR, énormément agrandi — le billet
 * téléchargé était inutilisable à l'entrée.
 *
 * On encode donc la matrice avec BaconQrCode (déjà présent) et on la peint en
 * PNG avec GD : un PNG se dessine sans surprise dans tous les navigateurs.
 */
class TicketQrCode
{
    /** Taille visée de l'image finale, en pixels. */
    private const SIZE = 360;

    /**
     * Marge silencieuse, en modules. Le standard en demande 4 ; 1 suffit ici car
     * le QR est posé sur une carte blanche avec de l'espace autour — et une
     * marge plus épaisse rapetissait visiblement le code face à l'affiche.
     */
    private const QUIET_ZONE = 1;

    /**
     * PNG binaire du QR encodant le texte donné.
     */
    public function png(string $text): string
    {
        $matrix = Encoder::encode($text, ErrorCorrectionLevel::M())->getMatrix();
        $modules = $matrix->getWidth();
        $total = $modules + 2 * self::QUIET_ZONE;

        // Taille de module entière : un module à virgule donnerait des bords
        // flous, que les lecteurs de QR n'aiment pas.
        $moduleSize = max(1, (int) floor(self::SIZE / $total));
        $side = $total * $moduleSize;

        $image = imagecreatetruecolor($side, $side);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $side - 1, $side - 1, $white);

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $left = ($x + self::QUIET_ZONE) * $moduleSize;
                $top = ($y + self::QUIET_ZONE) * $moduleSize;
                imagefilledrectangle($image, $left, $top, $left + $moduleSize - 1, $top + $moduleSize - 1, $black);
            }
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    /**
     * Le même QR prêt à être posé dans un `src`, ou null si la génération échoue
     * (un billet sans QR reste préférable à une page en erreur).
     */
    public function dataUri(string $text): ?string
    {
        try {
            return 'data:image/png;base64,' . base64_encode($this->png($text));
        } catch (\Throwable $e) {
            Log::warning('QR du billet non généré', ['text' => $text, 'error' => $e->getMessage()]);

            return null;
        }
    }
}

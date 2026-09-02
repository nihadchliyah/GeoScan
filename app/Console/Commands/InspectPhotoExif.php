<?php

namespace App\Console\Commands;

use App\Support\ExifGpsReader;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Debug tool: dumps everything readable from a photo's EXIF metadata so
 * you can see exactly why a GPS position was or wasn't found, instead of
 * just getting a yes/no from the web form.
 */
#[Signature('app:inspect-photo-exif {path : Path to the JPEG/TIFF file to inspect}')]
#[Description('Dumps a photo\'s EXIF metadata and explains whether/why a GPS position could be found')]
class InspectPhotoExif extends Command
{
    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("Fichier introuvable : {$path}");

            return self::FAILURE;
        }

        $mime = @mime_content_type($path) ?: 'inconnu';
        $this->info("Fichier : {$path}");
        $this->line("Type MIME détecté : {$mime}");

        if (! in_array($mime, ['image/jpeg', 'image/tiff'], true)) {
            $this->warn(
                'Ce format ne porte jamais de métadonnées EXIF (seuls JPEG et TIFF le peuvent). '.
                'Un PNG, WEBP, ou une capture d\'écran ne contiendront jamais de position GPS, quel que soit le contenu visuel de l\'image.'
            );

            return self::FAILURE;
        }

        $exif = @exif_read_data($path);

        if ($exif === false) {
            $this->warn(
                "PHP n'a trouvé aucun segment EXIF dans ce fichier — probablement une image ".
                're-enregistrée/compressée par une appli (réseau social, messagerie) qui a retiré '.
                'les métadonnées d\'origine à l\'envoi.'
            );

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Balises EXIF trouvées :');
        foreach ($exif as $key => $value) {
            if (is_scalar($value)) {
                $this->line("  {$key}: {$value}");
            }
        }

        if (empty($exif['GPSLatitude']) || empty($exif['GPSLongitude'])) {
            $this->newLine();
            $this->warn(
                'EXIF présent, mais aucune balise GPS dedans — la localisation était probablement '.
                "désactivée sur l'appareil au moment de la prise de vue, ou une étape entre les deux ".
                '(recadrage, appli de partage, capture d\'écran d\'une photo) l\'a retirée.'
            );

            return self::FAILURE;
        }

        $latitude = ExifGpsReader::toDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N');
        $longitude = ExifGpsReader::toDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E');

        $this->newLine();
        $this->info("Position GPS trouvée : {$latitude}, {$longitude}");
        $this->line("Voir sur OpenStreetMap : https://www.openstreetmap.org/?mlat={$latitude}&mlon={$longitude}#map=15/{$latitude}/{$longitude}");

        return self::SUCCESS;
    }
}

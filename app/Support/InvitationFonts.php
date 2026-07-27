<?php

namespace App\Support;

use FontLib\Font;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Google / Fontsource fonts used on invitation overlay designs.
 * HTML: CSS link. PDF (DomPDF): install TTF+UFM into storage/fonts.
 */
class InvitationFonts
{
    /** @var array<string, string> Display name => fontsource id */
    public const CATALOG = [
        'Great Vibes' => 'great-vibes',
        'Dancing Script' => 'dancing-script',
        'Sacramento' => 'sacramento',
        'Pinyon Script' => 'pinyon-script',
        'Allura' => 'allura',
        'Alex Brush' => 'alex-brush',
        'Italianno' => 'italianno',
        'Parisienne' => 'parisienne',
        'Satisfy' => 'satisfy',
        'Tangerine' => 'tangerine',
        'Rouge Script' => 'rouge-script',
        'Mr De Haviland' => 'mr-de-haviland',
        'Playfair Display' => 'playfair-display',
        'Cormorant Garamond' => 'cormorant-garamond',
        'Cinzel' => 'cinzel',
        'Lora' => 'lora',
        'EB Garamond' => 'eb-garamond',
        'Libre Baskerville' => 'libre-baskerville',
        'Merriweather' => 'merriweather',
        'Amiri' => 'amiri',
        'Montserrat' => 'montserrat',
        'Poppins' => 'poppins',
        'Source Sans 3' => 'source-sans-3',
        'Raleway' => 'raleway',
        'Josefin Sans' => 'josefin-sans',
        'Quicksand' => 'quicksand',
    ];

    /**
     * @param  list<string|null>  $families
     */
    public static function googleCssUrl(array $families = []): string
    {
        $names = self::normalizeFamilies($families);
        if ($names === []) {
            $names = array_keys(self::CATALOG);
        }

        $parts = [];
        foreach ($names as $name) {
            $parts[] = 'family='.str_replace(' ', '+', $name).':ital,wght@0,400;0,600;0,700;1,400';
        }

        return 'https://fonts.googleapis.com/css2?'.implode('&', $parts).'&display=swap';
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     */
    public static function googleCssUrlForFields(array $fields): string
    {
        $families = [];
        foreach ($fields as $field) {
            if (($field['field_type'] ?? 'text') === 'qr') {
                continue;
            }
            $families[] = $field['font_family'] ?? null;
        }

        return self::googleCssUrl($families);
    }

    /**
     * Install needed fonts into DomPDF's font dir and return CSS that
     * references them by family name (no @font-face remote/path loading).
     *
     * @param  list<array<string, mixed>>  $fields
     */
    public static function preparePdfFonts(array $fields): string
    {
        self::ensureFontDir();

        $installed = [];
        foreach ($fields as $field) {
            if (($field['field_type'] ?? 'text') === 'qr') {
                continue;
            }
            $family = trim((string) ($field['font_family'] ?? ''));
            if ($family === '' || ! isset(self::CATALOG[$family])) {
                continue;
            }
            $weight = (int) ($field['font_weight'] ?? 400);
            $italic = ($field['font_style'] ?? 'normal') === 'italic';
            if (self::installDompdfFace($family, $weight, $italic)) {
                $installed[$family] = true;
            }
            // Always try regular face as fallback for the family.
            if (self::installDompdfFace($family, 400, false)) {
                $installed[$family] = true;
            }
        }

        // No @font-face needed — DomPDF resolves family via installed-fonts.json.
        return '';
    }

    /** @deprecated use preparePdfFonts */
    public static function pdfFontFaceCss(array $fields): string
    {
        return self::preparePdfFonts($fields);
    }

    public static function cssFontFamily(?string $family): string
    {
        $family = trim((string) $family);
        if ($family !== '' && isset(self::CATALOG[$family]) && self::isFamilyInstalled($family)) {
            return "'{$family}', DejaVu Sans, sans-serif";
        }
        if ($family !== '' && isset(self::CATALOG[$family])) {
            // HTML / not yet installed for PDF
            return "'{$family}', serif";
        }

        return 'DejaVu Sans, sans-serif';
    }

    public static function cssFontFamilyForPdf(?string $family): string
    {
        $family = trim((string) $family);
        if ($family !== '' && isset(self::CATALOG[$family]) && self::isFamilyInstalled($family)) {
            return "'{$family}', DejaVu Sans, sans-serif";
        }

        return 'DejaVu Sans, sans-serif';
    }

    /**
     * @param  list<string|null>  $families
     * @return list<string>
     */
    private static function normalizeFamilies(array $families): array
    {
        $out = [];
        foreach ($families as $family) {
            $name = trim((string) $family);
            if ($name !== '' && isset(self::CATALOG[$name])) {
                $out[$name] = $name;
            }
        }

        return array_values($out);
    }

    private static function ensureFontDir(): void
    {
        $dir = storage_path('fonts');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $cache = storage_path('app/invitation-fonts');
        if (! is_dir($cache)) {
            @mkdir($cache, 0755, true);
        }
    }

    private static function installDompdfFace(string $family, int $weight, bool $italic): bool
    {
        $weight = match (true) {
            $weight >= 700 => 700,
            $weight >= 600 => 600,
            default => 400,
        };
        $styleKey = self::dompdfStyleKey($weight, $italic);
        $slug = self::faceSlug($family, $weight, $italic);
        $fontDir = storage_path('fonts');
        $ttfDest = $fontDir.DIRECTORY_SEPARATOR.$slug.'.ttf';
        $ufmDest = $fontDir.DIRECTORY_SEPARATOR.$slug.'.ufm';

        if (is_file($ttfDest) && is_file($ufmDest) && filesize($ufmDest) > 50) {
            self::rememberFamily($family, $styleKey, $slug);

            return true;
        }

        $source = self::ensureTtf($family, $weight, $italic);
        if ($source === null) {
            return false;
        }

        try {
            if (! @copy($source, $ttfDest)) {
                return false;
            }

            $font = Font::load($ttfDest);
            if (! $font) {
                @unlink($ttfDest);

                return false;
            }
            $font->parse();
            $font->saveAdobeFontMetrics($ufmDest);
            $font->close();

            if (! is_file($ufmDest) || filesize($ufmDest) < 50) {
                @unlink($ttfDest);
                @unlink($ufmDest);

                return false;
            }

            self::rememberFamily($family, $styleKey, $slug);

            return true;
        } catch (Throwable) {
            @unlink($ttfDest);
            @unlink($ufmDest);

            return false;
        }
    }

    private static function dompdfStyleKey(int $weight, bool $italic): string
    {
        if ($italic && $weight >= 700) {
            return 'bold_italic';
        }
        if ($italic) {
            return 'italic';
        }
        if ($weight >= 700) {
            return 'bold';
        }

        return 'normal';
    }

    private static function faceSlug(string $family, int $weight, bool $italic): string
    {
        $id = self::CATALOG[$family] ?? 'font';
        $style = $italic ? 'italic' : 'normal';

        return preg_replace('/[^a-z0-9_]+/', '_', strtolower($id.'_'.$weight.'_'.$style)) ?: 'font';
    }

    private static function installedFontsPath(): string
    {
        return storage_path('fonts/installed-fonts.json');
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function readInstalledFonts(): array
    {
        $path = self::installedFontsPath();
        if (! is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    private static function rememberFamily(string $family, string $styleKey, string $slug): void
    {
        $key = mb_strtolower($family, 'UTF-8');
        $all = self::readInstalledFonts();
        $all[$key] = $all[$key] ?? [];
        $all[$key][$styleKey] = $slug;
        // Keep a normal face pointer for DomPDF lookups.
        if ($styleKey === 'normal' || ! isset($all[$key]['normal'])) {
            $all[$key]['normal'] = $all[$key]['normal'] ?? $slug;
        }
        file_put_contents(
            self::installedFontsPath(),
            json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private static function isFamilyInstalled(string $family): bool
    {
        $key = mb_strtolower($family, 'UTF-8');
        $all = self::readInstalledFonts();
        if (! isset($all[$key]['normal'])) {
            return false;
        }
        $slug = $all[$key]['normal'];
        $ufm = storage_path('fonts/'.$slug.'.ufm');

        return is_file($ufm);
    }

    private static function ensureTtf(string $family, int $weight, bool $italic): ?string
    {
        $id = self::CATALOG[$family] ?? null;
        if ($id === null) {
            return null;
        }

        $weight = match (true) {
            $weight >= 700 => 700,
            $weight >= 600 => 600,
            default => 400,
        };
        $style = $italic ? 'italic' : 'normal';
        $dir = storage_path('app/invitation-fonts');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = "{$dir}/{$id}-{$weight}-{$style}.ttf";
        if (is_file($file) && filesize($file) > 1000) {
            return $file;
        }

        $candidates = [
            "https://cdn.jsdelivr.net/fontsource/fonts/{$id}@latest/latin-{$weight}-{$style}.ttf",
        ];
        if ($weight !== 400 || $style !== 'normal') {
            $candidates[] = "https://cdn.jsdelivr.net/fontsource/fonts/{$id}@latest/latin-400-normal.ttf";
        }

        foreach ($candidates as $url) {
            try {
                $response = Http::timeout(20)->withHeaders([
                    'User-Agent' => 'EkaadhFontCache/1.0',
                ])->get($url);
                if (! $response->successful() || strlen($response->body()) < 1000) {
                    continue;
                }
                file_put_contents($file, $response->body());

                return $file;
            } catch (Throwable) {
                continue;
            }
        }

        return is_file($file) ? $file : null;
    }
}

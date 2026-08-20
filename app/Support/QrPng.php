<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use RuntimeException;

class QrPng
{
    public static function bytes(string $payload, int $size = 240): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('PHP GD is required to generate ticket QR images.');
        }

        $qr = Encoder::encode($payload, ErrorCorrectionLevel::M());
        $matrix = $qr->getMatrix();
        $modules = $matrix->getWidth();
        $quiet = 4;
        $total = $modules + ($quiet * 2);
        $scale = max(1, intdiv(max(80, $size), $total));
        $px = $total * $scale;

        $im = imagecreatetruecolor($px, $px);
        if ($im === false) {
            throw new RuntimeException('Could not allocate QR image.');
        }

        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefill($im, 0, 0, $white);

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }
                $x0 = ($x + $quiet) * $scale;
                $y0 = ($y + $quiet) * $scale;
                imagefilledrectangle($im, $x0, $y0, $x0 + $scale - 1, $y0 + $scale - 1, $black);
            }
        }

        ob_start();
        imagepng($im);
        $bytes = (string) ob_get_clean();
        imagedestroy($im);

        if ($bytes === '') {
            throw new RuntimeException('Could not encode QR PNG.');
        }

        return $bytes;
    }

    public static function dataUri(string $payload, int $size = 240): string
    {
        return 'data:image/png;base64,'.base64_encode(self::bytes($payload, $size));
    }
}

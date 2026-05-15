<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Renders a 1200×630 OG/Twitter card on the fly using PHP GD.
 *
 * Branding: Navy → Cobalt diagonal gradient + hexagonal mark + title/subtitle.
 * Fonts fall back to DejaVu (shipped with most LAMP stacks including Hostinger);
 * the brand display font (Bricolage Grotesque) is variable WOFF2 only and not
 * usable from GD without bundling a static TTF, which we deliberately skip.
 *
 * Cached aggressively (24h CDN) — all params are in the query string so each
 * variation gets its own cache key.
 */
final class OgImageController extends AbstractController
{
    private const WIDTH = 1200;
    private const HEIGHT = 630;

    private const FONT_BOLD_CANDIDATES = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
        '/Library/Fonts/Arial Bold.ttf',
    ];

    private const FONT_REGULAR_CANDIDATES = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans.ttf',
        '/Library/Fonts/Arial.ttf',
    ];

    private const FONT_MONO_CANDIDATES = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSansMono.ttf',
        '/usr/share/fonts/dejavu/DejaVuSansMono.ttf',
    ];

    #[Route('/og.png', name: 'og_image', methods: ['GET'])]
    public function generate(Request $request): Response
    {
        $title = mb_substr(trim((string) $request->query->get('title', 'Studio de développement web')), 0, 80);
        $subtitle = mb_substr(trim((string) $request->query->get('subtitle', 'Sites sur-mesure en Symfony et Vue 3')), 0, 100);
        $eyebrow = strtoupper(mb_substr(trim((string) $request->query->get('kind', 'rivierematthieu.com')), 0, 40));

        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if ($img === false) {
            return new Response('GD allocation failed', 500);
        }

        $this->paintGradient($img);
        $this->paintHexMark($img);
        $this->paintGrain($img);
        $this->paintText($img, $eyebrow, $title, $subtitle);

        ob_start();
        imagepng($img);
        $bytes = (string) ob_get_clean();
        imagedestroy($img);

        return new Response($bytes, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400, immutable',
            'Content-Length' => (string) strlen($bytes),
        ]);
    }

    /** Diagonal Navy → Cobalt gradient mirroring the brand tokens. */
    private function paintGradient(\GdImage $img): void
    {
        // Brand tokens (assets/styles/tokens.scss): Navy #0F2A5F, Cobalt #2B5BD7.
        $start = [0x0F, 0x2A, 0x5F];
        $end   = [0x2B, 0x5B, 0xD7];
        $diag = self::WIDTH + self::HEIGHT;

        for ($y = 0; $y < self::HEIGHT; $y++) {
            for ($x = 0; $x < self::WIDTH; $x += 4) {
                $t = ($x + $y) / $diag;
                $r = (int) ($start[0] + ($end[0] - $start[0]) * $t);
                $g = (int) ($start[1] + ($end[1] - $start[1]) * $t);
                $b = (int) ($start[2] + ($end[2] - $start[2]) * $t);
                $color = imagecolorallocate($img, $r, $g, $b);
                if ($color === false) {
                    continue;
                }
                imagefilledrectangle($img, $x, $y, $x + 3, $y, $color);
            }
        }
    }

    /** Stylised hexagonal mark in the bottom-right, semi-transparent. */
    private function paintHexMark(\GdImage $img): void
    {
        $cx = self::WIDTH - 180;
        $cy = self::HEIGHT - 180;
        $r = 110;
        $points = [];
        for ($i = 0; $i < 6; $i++) {
            $angle = M_PI / 3 * $i - M_PI / 6;
            $points[] = (int) ($cx + $r * cos($angle));
            $points[] = (int) ($cy + $r * sin($angle));
        }
        $stroke = imagecolorallocatealpha($img, 255, 255, 255, 100);
        if ($stroke !== false) {
            imagesetthickness($img, 3);
            imagepolygon($img, $points, $stroke);
        }

        // Inner hex
        $points2 = [];
        for ($i = 0; $i < 6; $i++) {
            $angle = M_PI / 3 * $i - M_PI / 6;
            $points2[] = (int) ($cx + ($r - 36) * cos($angle));
            $points2[] = (int) ($cy + ($r - 36) * sin($angle));
        }
        $stroke2 = imagecolorallocatealpha($img, 255, 255, 255, 110);
        if ($stroke2 !== false) {
            imagesetthickness($img, 1);
            imagepolygon($img, $points2, $stroke2);
        }
    }

    /** Subtle uniform noise to break up the gradient. */
    private function paintGrain(\GdImage $img): void
    {
        for ($i = 0; $i < 8000; $i++) {
            $x = random_int(0, self::WIDTH - 1);
            $y = random_int(0, self::HEIGHT - 1);
            $alpha = random_int(110, 124);
            $c = imagecolorallocatealpha($img, 255, 255, 255, $alpha);
            if ($c !== false) {
                imagesetpixel($img, $x, $y, $c);
            }
        }
    }

    private function paintText(\GdImage $img, string $eyebrow, string $title, string $subtitle): void
    {
        $bold = $this->resolveFont(self::FONT_BOLD_CANDIDATES);
        $regular = $this->resolveFont(self::FONT_REGULAR_CANDIDATES);
        $mono = $this->resolveFont(self::FONT_MONO_CANDIDATES) ?? $regular;

        $whiteFull = imagecolorallocate($img, 255, 255, 255);
        $whiteSoft = imagecolorallocatealpha($img, 255, 255, 255, 30);
        $whiteFaint = imagecolorallocatealpha($img, 255, 255, 255, 60);

        $padX = 80;
        $eyebrowY = 100;
        $titleY = 240;

        if ($mono !== null && $whiteFaint !== false) {
            imagettftext($img, 22, 0, $padX, $eyebrowY, $whiteFaint, $mono, $eyebrow);
        }

        if ($bold !== null && $whiteFull !== false) {
            $titleLines = $this->wrapText($title, 26);
            $y = $titleY;
            foreach ($titleLines as $line) {
                imagettftext($img, 64, 0, $padX, $y, $whiteFull, $bold, $line);
                $y += 80;
            }
        }

        if ($regular !== null && $whiteSoft !== false) {
            $subtitleLines = $this->wrapText($subtitle, 50);
            $y = self::HEIGHT - 130 - (count($subtitleLines) - 1) * 36;
            foreach ($subtitleLines as $line) {
                imagettftext($img, 26, 0, $padX, $y, $whiteSoft, $regular, $line);
                $y += 36;
            }
        }

        if ($mono !== null && $whiteFaint !== false) {
            imagettftext($img, 18, 0, $padX, self::HEIGHT - 50, $whiteFaint, $mono, 'BROSSARD · QUÉBEC · NEQ 2279489522');
        }
    }

    /** @param list<string> $candidates */
    private function resolveFont(array $candidates): ?string
    {
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    /** @return list<string> */
    private function wrapText(string $text, int $maxChars): array
    {
        $wrapped = wordwrap($text, $maxChars, "\n", true);
        return explode("\n", $wrapped);
    }
}

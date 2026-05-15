<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sitemap.xml — generated dynamically so the base URL follows the env config
 * (no need to rewrite a static file when the canonical domain changes).
 *
 * Strategy : every internal page lives at a single canonical URL (FR slugs).
 * The home page is the only one that exposes language-specific URLs (/fr, /en)
 * for crawlers — they're declared with `xhtml:link rel="alternate"` so search
 * engines surface the right one to the right user.
 */
final class SeoController extends AbstractController
{
    public function __construct(private readonly string $publicBaseUrl)
    {
    }

    #[Route('/sitemap.xml', name: 'seo_sitemap', methods: ['GET'])]
    public function sitemap(): Response
    {
        $base = rtrim($this->publicBaseUrl, '/');
        $now = (new \DateTimeImmutable())->format('Y-m-d');

        $pages = [
            ['/', '1.0', true],   // home — has language alternates
            ['/forfaits', '0.9', false],
            ['/profil', '0.7', false],
            ['/contact', '0.7', false],
            ['/devis', '0.6', false],
            ['/mentions-legales', '0.3', false],
            ['/cgv', '0.3', false],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($pages as [$path, $priority, $hasAlternates]) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$base}{$path}</loc>\n";
            $xml .= "    <lastmod>{$now}</lastmod>\n";
            $xml .= "    <priority>{$priority}</priority>\n";
            if ($hasAlternates) {
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"fr-CA\" href=\"{$base}/fr\"/>\n";
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"en-CA\" href=\"{$base}/en\"/>\n";
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$base}/\"/>\n";
            }
            $xml .= "  </url>\n";
        }

        // Standalone language landing pages (each links back to the canonical /).
        foreach (['/fr' => 'fr-CA', '/en' => 'en-CA'] as $path => $hreflang) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$base}{$path}</loc>\n";
            $xml .= "    <lastmod>{$now}</lastmod>\n";
            $xml .= "    <priority>0.9</priority>\n";
            $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$hreflang}\" href=\"{$base}{$path}\"/>\n";
            $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$base}/\"/>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use Pentatrion\ViteBundle\Service\EntrypointRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class SpaController extends AbstractController
{
    public function __construct(
        private readonly EntrypointRenderer $viteEntrypoint,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    #[Route('/{path}', name: 'spa_catch_all', requirements: ['path' => '^(?!api|stripe|build|_|og\.png).*'], priority: -100)]
    #[Route('/', name: 'spa_root', priority: 100)]
    public function index(Request $request): Response
    {
        $links = $this->viteEntrypoint->renderLinks('app');
        $scripts = $this->viteEntrypoint->renderScripts('app');
        $csrf = htmlspecialchars($this->csrfTokenManager->getToken('app')->getValue(), ENT_QUOTES, 'UTF-8');

        [$ogTitle, $ogSubtitle, $ogKind] = $this->resolveOgMeta($request->getPathInfo());
        $ogImage = $this->urls->generate('og_image', [
            'title' => $ogTitle,
            'subtitle' => $ogSubtitle,
            'kind' => $ogKind,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $ogImageEsc = htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8');
        $ogTitleEsc = htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8');
        $ogDescEsc = htmlspecialchars($ogSubtitle, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0A0E1A">
    <link id="favicon" rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/mriviere-mark-180.png">
    <title>Studio de développement web — Matthieu Rivière</title>
    <meta name="description" content="Studio de développement web basé au Québec. Sites sur-mesure en Symfony et Vue 3, hébergés et maintenus. Forfaits à partir de 399 \$ CAD.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{$ogTitleEsc}">
    <meta property="og:description" content="{$ogDescEsc}">
    <meta property="og:image" content="{$ogImageEsc}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="fr_CA">
    <meta property="og:locale:alternate" content="en_CA">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{$ogImageEsc}">
    {$links}
</head>
<body>
    <div id="app" data-csrf="{$csrf}"></div>
    {$scripts}
</body>
</html>
HTML;

        return new Response($html);
    }

    /**
     * Map an SPA path to OG metadata. The Vue router does the same job client-side
     * for the document title; the duplication is acceptable because crawlers don't
     * execute JS and need the meta in the initial HTML response.
     *
     * @return array{string, string, string} [title, subtitle, kind]
     */
    private function resolveOgMeta(string $path): array
    {
        return match (true) {
            $path === '/forfaits' => [
                'Trois forfaits, prix transparents',
                'STARTER, STANDARD, PREMIUM — création + abonnement mensuel en CAD.',
                'Forfaits',
            ],
            str_starts_with($path, '/forfaits/') => [
                'Forfait — Studio Matthieu Rivière',
                'Site sur-mesure, hébergement, maintenance. Engagement 12 mois.',
                'Forfait',
            ],
            $path === '/profil' => [
                'Matthieu Rivière — Studio web Québec',
                'Huit ans à concevoir des sites pour Vidéotron, TVA Nouvelles, Marie Claire et des PME.',
                'Profil',
            ],
            $path === '/devis' => [
                'Demander un devis',
                'Quatre étapes, cinq minutes, acompte 50 % réglé sur Stripe.',
                'Devis',
            ],
            $path === '/contact' => [
                'Parlons de votre projet',
                'Réponse personnelle sous 24 h ouvrées. Aucun chatbot.',
                'Contact',
            ],
            $path === '/mentions-legales' => [
                'Mentions légales',
                'Loi 25 (Québec). NEQ 2279489522. Brossard, Québec.',
                'Légal',
            ],
            $path === '/cgv' => [
                'Conditions générales de vente',
                'Droit civil québécois. TPS/TVQ non applicable (petit fournisseur).',
                'CGV',
            ],
            default => [
                'Studio de développement web — Matthieu Rivière',
                'Sites web sur-mesure en Symfony et Vue 3. Studio basé au Québec.',
                'rivierematthieu.com',
            ],
        };
    }
}

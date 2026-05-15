<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Config\PackageSlug;
use App\Config\QuoteStatus;
use App\Entity\AdminUser;
use App\Entity\ContactMessage;
use App\Entity\Package;
use App\Entity\ProfileContent;
use App\Entity\Quote;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new AdminUser($_ENV['ADMIN_EMAIL'] ?? 'contact@rivierematthieu.com');
        $admin->setPassword($this->hasher->hashPassword($admin, 'change-me-in-prod'));
        $manager->persist($admin);

        $packages = $this->createPackages();
        $packages[0]->setStripeMonthlyPriceId($_ENV['STRIPE_PRICE_STARTER'] ?? null);
        $packages[1]->setStripeMonthlyPriceId($_ENV['STRIPE_PRICE_STANDARD'] ?? null);
        $packages[2]->setStripeMonthlyPriceId($_ENV['STRIPE_PRICE_PREMIUM'] ?? null);
        foreach ($packages as $package) {
            $manager->persist($package);
        }

        $profile = new ProfileContent();
        $profile->setBio(
            "Huit ans à concevoir des sites web pour des médias internationaux (Marie Claire, " .
            "TechRadar, Kiplinger), de grands acteurs québécois (Vidéotron, TVA Nouvelles, " .
            "Groupe Investiir, Groupe Alesco) et des PME dans l'immobilier et la santé. Diplômé " .
            "de l'École 42 à Paris, aujourd'hui établi à Brossard, je mets ce même savoir-faire " .
            "— celui réservé aux grandes marques — au service des artisans et petits commerces " .
            "qui veulent un site vraiment à leur image."
        );
        $profile->setStack(['PHP 8.4', 'Symfony 8', 'Doctrine ORM', 'MariaDB', 'Vue 3', 'TypeScript', 'Vite', 'Pinia', 'SCSS', 'Stripe', 'Hostinger', 'GitHub Actions']);
        $profile->setLinks([
            'github' => 'https://github.com/mriviere-company',
            'linkedin' => 'https://www.linkedin.com/in/rivierematthieu/',
            'instagram' => 'https://www.instagram.com/matthieu_rvr_/',
        ]);
        $manager->persist($profile);

        if (($_ENV['APP_ENV'] ?? 'dev') === 'dev') {
            $this->createDevSeeds($manager, $packages);
        }

        $manager->flush();
    }

    /** @return list<Package> */
    private function createPackages(): array
    {
        $starter = new Package(PackageSlug::Starter, 'STARTER');
        $starter->setOneShotPriceCents(39900)
            ->setMonthlyPriceCents(2900)
            ->setMaxPages(3)
            ->setSortOrder(1)
            ->setHighlighted(false)
            ->setFeatures([
                ['label' => 'Design responsive (mobile / desktop)', 'included' => true],
                ['label' => 'Nom de domaine (.fr ou .com)', 'included' => true],
                ['label' => 'Certificat SSL (HTTPS)', 'included' => true],
                ['label' => 'Formulaire de contact', 'included' => true],
                ['label' => 'Intégration Google Maps', 'included' => true],
                ['label' => 'Galerie photos', 'included' => false],
                ['label' => 'Optimisation SEO de base', 'included' => false],
                ['label' => 'Blog / Actualités', 'included' => false],
                ['label' => 'Rapport mensuel de visites', 'included' => false],
            ]);

        $standard = new Package(PackageSlug::Standard, 'STANDARD');
        $standard->setOneShotPriceCents(59900)
            ->setMonthlyPriceCents(4900)
            ->setMaxPages(5)
            ->setSortOrder(2)
            ->setHighlighted(true)
            ->setFeatures([
                ['label' => 'Design responsive (mobile / desktop)', 'included' => true],
                ['label' => 'Nom de domaine (.fr ou .com)', 'included' => true],
                ['label' => 'Certificat SSL (HTTPS)', 'included' => true],
                ['label' => 'Formulaire de contact', 'included' => true],
                ['label' => 'Intégration Google Maps', 'included' => true],
                ['label' => 'Galerie photos', 'included' => true],
                ['label' => 'Optimisation SEO de base', 'included' => true],
                ['label' => 'Blog / Actualités', 'included' => false],
                ['label' => 'Rapport mensuel de visites', 'included' => false],
            ]);

        $premium = new Package(PackageSlug::Premium, 'PREMIUM');
        $premium->setOneShotPriceCents(89900)
            ->setMonthlyPriceCents(7900)
            ->setMaxPages(8)
            ->setSortOrder(3)
            ->setHighlighted(false)
            ->setFeatures([
                ['label' => 'Design responsive (mobile / desktop)', 'included' => true],
                ['label' => 'Nom de domaine (.fr ou .com)', 'included' => true],
                ['label' => 'Certificat SSL (HTTPS)', 'included' => true],
                ['label' => 'Formulaire de contact', 'included' => true],
                ['label' => 'Intégration Google Maps', 'included' => true],
                ['label' => 'Galerie photos', 'included' => true],
                ['label' => 'Optimisation SEO de base', 'included' => true],
                ['label' => 'Blog / Actualités', 'included' => true],
                ['label' => 'Rapport mensuel de visites', 'included' => true],
            ]);

        return [$starter, $standard, $premium];
    }

    /** @param list<Package> $packages */
    private function createDevSeeds(ObjectManager $manager, array $packages): void
    {
        $quote = new Quote(
            package: $packages[1],
            clientName: 'Camille Dupont',
            clientEmail: 'camille.dupont@example.ca',
            clientPhone: '514 123 4567',
            projectDescription: "Clinique d'orthophonie à Montréal. Besoin d'une vitrine avec horaires, présentation des thérapies, formulaire de prise de rendez-vous, intégration Google Maps. Contenu fourni.",
        );
        $quote->setClientCompany('Clinique Dupont');
        $quote->setClientAddress('14 rue Sherbrooke Ouest, Montréal (Québec) H3A 1G1');
        $quote->setStatus(QuoteStatus::Pending);
        $manager->persist($quote);

        $quote2 = new Quote(
            package: $packages[2],
            clientName: 'Lucas Martin',
            clientEmail: 'l.martin@example.ca',
            clientPhone: '418 555 0199',
            projectDescription: "Pâtisserie artisanale à Québec. Nouvelle ouverture. Besoin d'une vitrine, galerie produits, blog actualités, prises de commandes simples par formulaire.",
        );
        $quote2->setClientCompany('Maison Martin');
        $quote2->setStatus(QuoteStatus::DepositPaid);
        $manager->persist($quote2);

        foreach ([
            ['Sophie Bernard', 'sophie.bernard@example.ca', "Bonjour, je tiens un salon de coiffure à Laval et je voudrais un site simple. Pouvez-vous me rappeler ?"],
            ['Hugo Petit', 'hugo@example.com', "Hello, looking for a dev to redo our non-profit website in Québec City. Available?"],
            ['Manon Garnier', 'm.garnier@example.ca', "Devis pour 5 pages avec blog ? Combien de temps ?"],
        ] as [$name, $email, $body]) {
            $manager->persist(new ContactMessage($name, $email, $body));
        }
    }
}

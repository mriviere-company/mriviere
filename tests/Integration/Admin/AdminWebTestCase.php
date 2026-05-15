<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Base for admin happy-path tests. Boots the kernel once per test, ensures an
 * admin user exists (creating one in the surrounding DAMA transaction if not),
 * and exposes a logged-in `KernelBrowser`.
 *
 * DAMA wraps every test in a transaction that's rolled back on tearDown — so
 * any DB writes (new admin, new contact message, …) vanish between tests.
 */
abstract class AdminWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $container = static::getContainer();
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $repo = $container->get(AdminUserRepository::class);

        $admin = $repo->findOneBy(['email' => 'admin-tests@example.com']);
        if ($admin === null) {
            $admin = new AdminUser('admin-tests@example.com');
            $admin->setPassword($hasher->hashPassword($admin, 'test-password-123'));
            $em = $container->get('doctrine')->getManager();
            $em->persist($admin);
            $em->flush();
        }

        $this->client->loginUser($admin, 'admin');
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Entity\CallbackRequest;
use App\Entity\ContactMessage;
use App\Entity\ManagedSite;

/**
 * Happy-path coverage for the authenticated `/api/admin/*` endpoints.
 *
 * These tests share a logged-in admin (`AdminWebTestCase`) and rely on
 * `dama/doctrine-test-bundle` to roll back every write at tearDown — so each
 * test starts from the same fixtures-loaded state.
 */
final class HappyPathTest extends AdminWebTestCase
{
    public function testDashboardReturnsCounters(): void
    {
        $this->client->request('GET', '/api/admin/dashboard');
        self::assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);

        self::assertIsArray($body);
        foreach (['pendingQuotes', 'activeSubscriptions', 'monthlyRecurring', 'unreadMessages', 'pendingCallbacks'] as $expected) {
            self::assertArrayHasKey($expected, $body, "Missing counter `$expected`");
        }
    }

    public function testQuotesListIsArray(): void
    {
        $this->client->request('GET', '/api/admin/quotes');
        self::assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($body);
        self::assertArrayHasKey('quotes', $body);
        self::assertIsArray($body['quotes']);
    }

    public function testMessagesListMarkRead(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $message = new ContactMessage('Test User', 'test@example.com', 'Test integration message — please ignore.');
        $em->persist($message);
        $em->flush();

        // List endpoint sees the new message.
        $this->client->request('GET', '/api/admin/messages');
        self::assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $ids = array_column($body['messages'] ?? [], 'id');
        self::assertContains($message->getId(), $ids);

        // Mark-read flips the status without HTTP error.
        $this->client->request('POST', sprintf('/api/admin/messages/%d/read', $message->getId()));
        self::assertResponseIsSuccessful();
    }

    public function testCallbacksListAndArchive(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $cb = new CallbackRequest(
            name: 'Test Callback',
            email: 'cb@example.com',
            phone: '+1 514 555 0123',
            preferredSlot: new \DateTimeImmutable('+2 days 10:00'),
        );
        $cb->setMessage('Test integration callback');
        $em->persist($cb);
        $em->flush();

        $this->client->request('GET', '/api/admin/callbacks');
        self::assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $ids = array_column($body['callbacks'] ?? [], 'id');
        self::assertContains($cb->getId(), $ids);

        $this->client->request('POST', sprintf('/api/admin/callbacks/%d/archive', $cb->getId()));
        self::assertResponseIsSuccessful();
    }

    public function testProfileUpdateRoundtrip(): void
    {
        $payload = [
            'bio' => 'Bio mise à jour par les tests d\'intégration. Ce contenu est rollback à la fin.',
            'stack' => ['PHP 8.4', 'Symfony 8', 'Vue 3'],
            'photoUrl' => '/img/profile-test.jpg',
        ];

        $this->client->request(
            'PUT',
            '/api/admin/profile',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );
        self::assertResponseIsSuccessful();

        // Verify the public profile reflects the change.
        $this->client->request('GET', '/api/profile');
        self::assertResponseIsSuccessful();
        $public = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame($payload['photoUrl'], $public['photoUrl']);
        self::assertSame($payload['stack'], $public['stack']);
    }

    public function testManagedSiteCreateListShowDelete(): void
    {
        // CREATE
        $this->client->request(
            'POST',
            '/api/admin/sites',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'domain' => 'test-clone.example.com',
                'label' => 'Test clone',
                'publicKeyFingerprint' => str_repeat('a', 64),
                'enabled' => true,
            ]),
        );
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('test-clone.example.com', $created['domain']);

        // LIST contains it
        $this->client->request('GET', '/api/admin/sites');
        self::assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $domains = array_column($body['sites'] ?? [], 'domain');
        self::assertContains('test-clone.example.com', $domains);

        // SHOW
        $this->client->request('GET', '/api/admin/sites/' . $created['id']);
        self::assertResponseIsSuccessful();

        // DELETE
        $this->client->request('DELETE', '/api/admin/sites/' . $created['id']);
        self::assertResponseIsSuccessful();
    }

    public function testManagedSiteHealthHistoryAndStatsAreEmptyByDefault(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $site = new ManagedSite('history-clone.example.com', 'History clone', str_repeat('b', 64));
        $em->persist($site);
        $em->flush();

        $this->client->request('GET', sprintf('/api/admin/sites/%d/health-history', $site->getId()));
        self::assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame([], $body['history']);
        self::assertSame(0, $body['consecutiveFailures']);

        $this->client->request('GET', sprintf('/api/admin/sites/%d/stats', $site->getId()));
        self::assertResponseIsSuccessful();
        $stats = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame([], $stats['timeseries']);
    }

    public function testAggregatedDashboardReturnsTotals(): void
    {
        $this->client->request('GET', '/api/admin/aggregated');
        self::assertResponseIsSuccessful();
        $body = json_decode($this->client->getResponse()->getContent(), true);

        foreach (['totals', 'timeseries', 'topPaths', 'perSite'] as $key) {
            self::assertArrayHasKey($key, $body);
        }
        foreach (['sites', 'enabledSites', 'pageViews30d', 'uniqueVisitors30d'] as $key) {
            self::assertArrayHasKey($key, $body['totals']);
        }
    }
}

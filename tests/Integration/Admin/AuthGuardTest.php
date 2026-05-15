<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Sanity check: every protected admin route must reject anonymous traffic.
 * This guards against accidental access_control misconfiguration.
 */
final class AuthGuardTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string, string, array<string,mixed>|null}>
     */
    public static function protectedRoutes(): iterable
    {
        yield 'GET dashboard'                 => ['GET',    '/api/admin/dashboard',                 null];
        yield 'GET quotes list'               => ['GET',    '/api/admin/quotes',                    null];
        yield 'GET messages list'             => ['GET',    '/api/admin/messages',                  null];
        yield 'GET callbacks list'            => ['GET',    '/api/admin/callbacks',                 null];
        yield 'GET subscriptions'             => ['GET',    '/api/admin/subscriptions',             null];
        yield 'GET sites list'                => ['GET',    '/api/admin/sites',                     null];
        yield 'POST sites create'             => ['POST',   '/api/admin/sites',                     []];
        yield 'PATCH site'                    => ['PATCH',  '/api/admin/sites/1',                   []];
        yield 'DELETE site'                   => ['DELETE', '/api/admin/sites/1',                   null];
        yield 'POST sites check-health'       => ['POST',   '/api/admin/sites/1/check-health',      null];
        yield 'PUT profile'                   => ['PUT',    '/api/admin/profile',                   []];
    }

    /**
     * @param array<string,mixed>|null $body
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('protectedRoutes')]
    public function testRouteRejectsAnonymous(string $method, string $url, ?array $body): void
    {
        $client = self::createClient();
        $client->request(
            method: $method,
            uri: $url,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $body !== null ? json_encode($body) : null,
        );

        // Symfony's access_control returns 401 (configured) or 403; both signal denial.
        self::assertContains(
            $client->getResponse()->getStatusCode(),
            [401, 403],
            sprintf('Expected anonymous %s %s to be denied', $method, $url),
        );
    }

    public function testLoginRouteRejectsBadCredentials(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/admin/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'nope@example.com',
            'password' => 'wrong',
        ]));
        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testMeRouteIsPublicAndReturnsUnauthorizedWhenAnonymous(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/admin/me');
        // /me is PUBLIC_ACCESS so it goes through; the controller itself returns 401 when no user is in session.
        self::assertSame(401, $client->getResponse()->getStatusCode());
    }
}

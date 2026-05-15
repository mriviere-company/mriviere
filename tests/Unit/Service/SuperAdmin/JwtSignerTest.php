<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\SuperAdmin;

use App\Entity\ManagedSite;
use App\Service\SuperAdmin\JwtSigner;
use PHPUnit\Framework\TestCase;

final class JwtSignerTest extends TestCase
{
    private const ISSUER = 'https://central.test';
    private string $privateKeyPath;
    private string $publicKey;

    protected function setUp(): void
    {
        // Create a fresh Ed25519 keypair in a tmp file and remember the public key
        // so we can verify what the signer produces.
        $keypair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($keypair);
        $this->publicKey = sodium_crypto_sign_publickey($keypair);

        // Build a minimal PKCS#8 PEM (Ed25519 OID + 32-byte seed = first half of secret).
        $seed = substr($secret, 0, 32);
        $der = "\x30\x2e"             // SEQUENCE 46
            . "\x02\x01\x00"          // INTEGER 0 (version)
            . "\x30\x05\x06\x03\x2b\x65\x70" // AlgorithmIdentifier { OID 1.3.101.112 }
            . "\x04\x22"              // OCTET STRING 34
            . "\x04\x20" . $seed;     // OCTET STRING 32 (the seed)
        $pem = "-----BEGIN PRIVATE KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PRIVATE KEY-----\n";

        $this->privateKeyPath = tempnam(sys_get_temp_dir(), 'sa_priv_');
        file_put_contents($this->privateKeyPath, $pem);
    }

    protected function tearDown(): void
    {
        @unlink($this->privateKeyPath);
    }

    public function testSignatureSelfVerifies(): void
    {
        $signer = new JwtSigner($this->privateKeyPath, self::ISSUER);
        $site = new ManagedSite('client.example', 'Test client', 'fp');

        $jwt = $signer->sign($site, 'stats:read', 60);

        [$header, $payload, $signature] = explode('.', $jwt);
        $signingInput = $header . '.' . $payload;
        $sigBytes = self::base64UrlDecode($signature);

        $this->assertTrue(sodium_crypto_sign_verify_detached($sigBytes, $signingInput, $this->publicKey));
    }

    public function testPayloadHasRequiredClaims(): void
    {
        $signer = new JwtSigner($this->privateKeyPath, self::ISSUER);
        $site = new ManagedSite('client.example', 'Test client', 'fp');

        $jwt = $signer->sign($site, 'health:read', 90);
        $payload = self::decodeJwtPayload($jwt);

        $this->assertSame(self::ISSUER, $payload['iss']);
        $this->assertSame('client.example', $payload['aud']);
        $this->assertSame('health:read', $payload['scope']);
        $this->assertArrayHasKey('jti', $payload);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $payload['jti']);

        $now = time();
        $this->assertGreaterThan($now - 10, $payload['nbf']);
        $this->assertLessThan($now + 200, $payload['exp']);
        $this->assertGreaterThanOrEqual(80, $payload['exp'] - $payload['iat']);
    }

    public function testJtiIsUniquePerCall(): void
    {
        $signer = new JwtSigner($this->privateKeyPath, self::ISSUER);
        $site = new ManagedSite('client.example', 'Test client', 'fp');

        $jti1 = self::decodeJwtPayload($signer->sign($site, 'stats:read'))['jti'];
        $jti2 = self::decodeJwtPayload($signer->sign($site, 'stats:read'))['jti'];

        $this->assertNotSame($jti1, $jti2);
    }

    public function testFingerprintMatchesPublicKey(): void
    {
        $signer = new JwtSigner($this->privateKeyPath, self::ISSUER);
        $expected = strtolower(bin2hex(hash('sha256', $this->publicKey, true)));

        $this->assertSame($expected, $signer->getPublicKeyFingerprint());
    }

    public function testThrowsWhenKeyMissing(): void
    {
        $signer = new JwtSigner('/tmp/does/not/exist.pem', self::ISSUER);
        $site = new ManagedSite('client.example', 'X', 'fp');

        $this->expectException(\RuntimeException::class);
        $signer->sign($site, 'stats:read');
    }

    /** @return array<string, mixed> */
    private static function decodeJwtPayload(string $jwt): array
    {
        [, $payload] = explode('.', $jwt);
        return json_decode(self::base64UrlDecode($payload), true, 512, JSON_THROW_ON_ERROR);
    }

    private static function base64UrlDecode(string $s): string
    {
        $pad = strlen($s) % 4;
        if ($pad > 0) {
            $s .= str_repeat('=', 4 - $pad);
        }
        return (string) base64_decode(strtr($s, '-_', '+/'), true);
    }
}

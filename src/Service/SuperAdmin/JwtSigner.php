<?php

declare(strict_types=1);

namespace App\Service\SuperAdmin;

use App\Entity\ManagedSite;
use Symfony\Component\Uid\Uuid;

/**
 * Signs EdDSA (Ed25519) JWTs that the central dashboard sends to client clones.
 *
 * Format follows the contract in docs/CENTRAL_DASHBOARD_BRIEF.md §5 :
 *   header  = { "alg": "EdDSA", "typ": "JWT" }
 *   payload = { iss, aud, exp, nbf, jti, scope }
 *
 * The private key is loaded once from a PEM file, cached as raw seed bytes.
 * libsodium (ext-sodium) does the actual Ed25519 signature.
 */
final class JwtSigner
{
    private ?string $signingKey = null;

    public function __construct(
        private readonly string $superAdminPrivateKeyPath,
        private readonly string $superAdminIssuer,
    ) {
    }

    /**
     * @param string $scope space-separated list, e.g. "stats:read access:write"
     */
    public function sign(ManagedSite $site, string $scope, int $ttlSeconds = 60): string
    {
        $now = time();
        $header = ['alg' => 'EdDSA', 'typ' => 'JWT'];
        $payload = [
            'iss' => $this->superAdminIssuer,
            'aud' => $site->getDomain(),
            'iat' => $now,
            'nbf' => $now - 5,
            'exp' => $now + $ttlSeconds,
            'jti' => Uuid::v4()->toRfc4122(),
            'scope' => $scope,
        ];

        $segments = self::base64UrlEncode((string) json_encode($header, JSON_THROW_ON_ERROR))
            . '.' . self::base64UrlEncode((string) json_encode($payload, JSON_THROW_ON_ERROR));

        $signature = sodium_crypto_sign_detached($segments, $this->getSigningKey());

        return $segments . '.' . self::base64UrlEncode($signature);
    }

    public function getPublicKeyFingerprint(): string
    {
        $publicKey = sodium_crypto_sign_publickey_from_secretkey($this->getSigningKey());
        return strtolower(bin2hex(hash('sha256', $publicKey, true)));
    }

    private function getSigningKey(): string
    {
        if ($this->signingKey !== null) {
            return $this->signingKey;
        }

        if (!is_readable($this->superAdminPrivateKeyPath)) {
            throw new \RuntimeException(sprintf(
                'Super-admin private key not readable at %s. Generate it with: openssl genpkey -algorithm Ed25519 -out %s',
                $this->superAdminPrivateKeyPath,
                $this->superAdminPrivateKeyPath,
            ));
        }

        $pem = (string) file_get_contents($this->superAdminPrivateKeyPath);
        $der = self::pemToDer($pem);
        // Ed25519 PKCS#8 layout: the last 32 bytes of the DER are the seed.
        $seed = substr($der, -32);
        if (strlen($seed) !== 32) {
            throw new \RuntimeException('Unexpected Ed25519 private key length.');
        }

        // sodium_crypto_sign_seed_keypair expects 32 bytes seed and returns 96 bytes
        // (64 bytes secret key followed by 32 bytes public key).
        $keypair = sodium_crypto_sign_seed_keypair($seed);
        $this->signingKey = sodium_crypto_sign_secretkey($keypair);
        return $this->signingKey;
    }

    private static function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private static function pemToDer(string $pem): string
    {
        $body = preg_replace('/-----[A-Z ]+-----/', '', $pem);
        $body = preg_replace('/\s+/', '', (string) $body);
        return (string) base64_decode((string) $body, true);
    }
}

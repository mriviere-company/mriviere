<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ManagedSitePayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le domaine est requis.')]
        #[Assert\Regex(
            pattern: '/^([a-z0-9](-?[a-z0-9])*\.)+[a-z]{2,}$/i',
            message: 'Domaine invalide (ex. exemple.rivierematthieu.com).',
        )]
        #[Assert\Length(max: 191)]
        public string $domain = '',

        #[Assert\NotBlank(message: 'Le label est requis.')]
        #[Assert\Length(max: 128)]
        public string $label = '',

        #[Assert\NotBlank(message: 'Le fingerprint de la clé publique est requis.')]
        #[Assert\Regex(
            pattern: '/^[0-9a-f]{64}$/i',
            message: 'Fingerprint SHA-256 hex (64 caractères) attendu.',
        )]
        public string $publicKeyFingerprint = '',

        public bool $enabled = true,
    ) {
    }
}

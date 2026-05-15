<?php

declare(strict_types=1);

namespace App\Dto;

use App\Config\PackageSlug;
use Symfony\Component\Validator\Constraints as Assert;

final class QuoteRequest
{
    public function __construct(
        #[Assert\NotNull(message: 'Forfait requis.')]
        public ?PackageSlug $package = null,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 128)]
        public string $clientName = '',

        #[Assert\Length(max: 128)]
        public ?string $clientCompany = null,

        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $clientEmail = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 6, max: 32)]
        public string $clientPhone = '',

        #[Assert\Length(max: 255)]
        public ?string $clientAddress = null,

        #[Assert\NotBlank]
        #[Assert\Length(min: 10, max: 5000)]
        public string $projectDescription = '',

        /** @var array<string, mixed> */
        public array $options = [],

        #[Assert\Blank(message: 'Spam détecté.')]
        public ?string $website = null,

        #[Assert\Choice(choices: ['fr', 'en'])]
        public string $locale = 'fr',
    ) {
    }
}

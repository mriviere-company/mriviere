<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ContactRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom est requis.')]
        #[Assert\Length(min: 2, max: 128)]
        public string $name = '',

        #[Assert\NotBlank(message: "L'email est requis.")]
        #[Assert\Email(message: 'Email invalide.')]
        #[Assert\Length(max: 180)]
        public string $email = '',

        #[Assert\NotBlank(message: 'Le message est requis.')]
        #[Assert\Length(min: 10, max: 5000)]
        public string $message = '',

        #[Assert\Blank(message: 'Spam détecté.')]
        public ?string $website = null,
    ) {
    }
}

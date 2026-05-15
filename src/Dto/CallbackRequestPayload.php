<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CallbackRequestPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom est requis.')]
        #[Assert\Length(min: 2, max: 128)]
        public string $name = '',

        // Optionnel : c'est un appel téléphonique, l'email n'est qu'un secours.
        #[Assert\Email(message: 'Adresse courriel invalide.')]
        #[Assert\Length(max: 180)]
        public ?string $email = null,

        #[Assert\NotBlank(message: 'Le téléphone est requis pour vous rappeler.')]
        #[Assert\Length(min: 7, max: 32)]
        #[Assert\Regex(pattern: '/^[\d\s+\-().]+$/', message: 'Format de téléphone invalide.')]
        public string $phone = '',

        // ISO format from `<input type="datetime-local">` : "YYYY-MM-DDTHH:MM"
        #[Assert\NotBlank(message: 'Veuillez choisir un créneau.')]
        #[Assert\Regex(pattern: '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', message: 'Format de créneau invalide.')]
        public string $slot = '',

        #[Assert\Length(max: 2000)]
        public ?string $message = null,

        #[Assert\Blank(message: 'Spam détecté.')]
        public ?string $website = null,
    ) {
    }
}

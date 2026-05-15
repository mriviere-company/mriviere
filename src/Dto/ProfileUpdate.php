<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProfileUpdate
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 10, max: 5000)]
        public string $bio = '',

        /** @var list<string> */
        #[Assert\All([new Assert\Type('string'), new Assert\Length(max: 64)])]
        public array $stack = [],

        #[Assert\Length(max: 255)]
        #[Assert\Regex(pattern: '#^(/|https?://)#', message: 'URL absolue ou chemin commençant par /.', match: true)]
        public ?string $photoUrl = null,
    ) {
    }
}

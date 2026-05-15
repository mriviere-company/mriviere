<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\ContactRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ContactRequestTest extends TestCase
{
    public function testValidPayloadHasNoViolations(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $req = new ContactRequest(
            name: 'Camille Dupont',
            email: 'camille@example.fr',
            message: 'Bonjour, je voudrais un site vitrine pour mon cabinet.',
        );

        $this->assertCount(0, $validator->validate($req));
    }

    public function testHoneypotTriggersViolation(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $req = new ContactRequest(
            name: 'X',
            email: 'a@b.fr',
            message: 'Some long enough message text.',
            website: 'http://spam.example',
        );

        $violations = $validator->validate($req);
        $this->assertGreaterThanOrEqual(1, count($violations));
    }

    public function testInvalidEmailIsRejected(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $req = new ContactRequest(name: 'Foo', email: 'not-an-email', message: 'Long enough message text here.');
        $violations = $validator->validate($req);
        $this->assertGreaterThanOrEqual(1, count($violations));
    }
}

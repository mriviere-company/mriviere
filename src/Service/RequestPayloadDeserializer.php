<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class RequestPayloadDeserializer
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return array{0: T|null, 1: array<string, string>}
     */
    public function deserializeAndValidate(Request $request, string $class): array
    {
        $body = $request->getContent();
        if (!is_string($body) || $body === '') {
            return [null, ['_' => 'Empty payload']];
        }

        try {
            $object = $this->serializer->deserialize($body, $class, JsonEncoder::FORMAT);
        } catch (\Throwable $e) {
            return [null, ['_' => 'Invalid JSON: ' . $e->getMessage()]];
        }

        $errors = $this->validator->validate($object);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $err) {
                $messages[$err->getPropertyPath()] = (string) $err->getMessage();
            }
            return [$object, $messages];
        }

        return [$object, []];
    }
}

<?php

namespace App\Baccarat\Service\Serializer;

use Hyperf\Serializer\ExceptionNormalizer;
use Hyperf\Serializer\ScalarNormalizer;
use Hyperf\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class SerializerFactory
{
    public function __construct(protected string $serializer = Serializer::class)
    {
    }

    public function __invoke()
    {
        $normalizers = [
            new ExceptionNormalizer(),
            new ObjectNormalizer(),
            new ArrayDenormalizer(),
            new ScalarNormalizer(),
        ];

        $encoders = [
            new JsonEncoder(),
            new JsonDecode(),
            new Serializer(),
            new XmlEncoder()
        ];

        return new $this->serializer($normalizers, $encoders);
    }
}
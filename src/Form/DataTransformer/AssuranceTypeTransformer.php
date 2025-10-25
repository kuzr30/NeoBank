<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Enum\AssuranceType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class AssuranceTypeTransformer implements DataTransformerInterface
{
    /**
     * Transforms an AssuranceType enum to a string
     */
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof AssuranceType) {
            throw new TransformationFailedException('Expected an AssuranceType enum.');
        }

        return $value->value;
    }

    /**
     * Transforms a string to an AssuranceType enum
     */
    public function reverseTransform(mixed $value): ?AssuranceType
    {
        if (!$value) {
            return null;
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        $assuranceType = AssuranceType::tryFrom($value);

        if (!$assuranceType) {
            throw new TransformationFailedException(sprintf('The value "%s" is not a valid AssuranceType.', $value));
        }

        return $assuranceType;
    }
}

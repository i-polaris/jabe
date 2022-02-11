<?php

namespace BpmPlatform\Engine\Impl\Form\Validator;

class MaxValidator extends AbstractNumericValidator
{
    protected function validateInteger(int $submittedValue, int $configuration): bool
    {
        return $submittedValue < $configuration;
    }

    protected function validateFloat(float $submittedValue, float $configuration): bool
    {
        return $submittedValue < $configuration;
    }
}

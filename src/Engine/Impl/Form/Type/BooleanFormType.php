<?php

namespace Jabe\Engine\Impl\Form\Type;

use Jabe\Engine\ProcessEngineException;
use Jabe\Engine\Variable\Variables;
use Jabe\Engine\Variable\Value\{
    BooleanValueInterface,
    TypedValueInterface
};

class BooleanFormType extends SimpleFormFieldType
{
    public const TYPE_NAME = "boolean";

    public function getName(): string
    {
        return self::TYPE_NAME;
    }

    public function convertValue(TypedValueInterface $propertyValue): TypedValueInterface
    {
        if ($propertyValue instanceof BooleanValueInterface) {
            return $propertyValue;
        } else {
            $value = $propertyValue->getValue();
            if ($value == null) {
                return Variables::booleanValue(null, $propertyValue->isTransient());
            } elseif (is_bool($value) || is_string($value)) {
                return Variables::booleanValue(boolval($value), $propertyValue->isTransient());
            } else {
                throw new ProcessEngineException("Value '" . $value . "' is not of type Boolean.");
            }
        }
    }
}

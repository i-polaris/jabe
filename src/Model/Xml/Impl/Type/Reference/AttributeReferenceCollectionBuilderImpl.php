<?php

namespace BpmPlatform\Model\Xml\Impl\Type\Reference;

use BpmPlatform\Model\Xml\ModelInterface;
use BpmPlatform\Model\Xml\Exception\ModelException;
use BpmPlatform\Model\Xml\Impl\ModelBuildOperationInterface;
use BpmPlatform\Model\Xml\Impl\Type\Attribute\AttributeImpl;
use BpmPlatform\Model\Xml\Type\Reference\{
    AttributeReferenceCollectionBuilderInterface,
    AttributeReferenceCollection
};

class AttributeReferenceCollectionBuilderImpl implements
    AttributeReferenceCollectionBuilderInterface,
    ModelBuildOperationInterface
{
    private $referenceSourceAttribute;
    protected $attributeReferenceCollection;
    private $referenceTargetElement;

    public function __construct(
        AttributeImpl $referenceSourceAttribute,
        string $referenceTargetElement,
        string $attributeReferenceCollection
    ) {
        $this->referenceSourceAttribute = $referenceSourceAttribute;
        $this->referenceTargetElement = $referenceTargetElement;
        $this->attributeReferenceCollection = new $attributeReferenceCollection($this->referenceSourceAttribute);
    }

    public function build(): AttributeReferenceCollection
    {
        $this->referenceSourceAttribute->registerOutgoingReference($this->attributeReferenceCollection);
        return $this->attributeReferenceCollection;
    }

    public function performModelBuild(ModelInterface $model): void
    {
        $referenceTargetType = $model->getType($this->referenceTargetElement);
        $this->attributeReferenceCollection->setReferenceTargetElementType($referenceTargetType);

        $idAttribute = $referenceTargetType->getAttribute("id");
        if ($idAttribute != null) {
            $idAttribute->registerIncoming($this->attributeReferenceCollection);
            $this->attributeReferenceCollection->setReferenceTargetAttribute($idAttribute);
        } else {
            throw new ModelException(
                sprintf(
                    "Element type %s:%s has no id attribute",
                    $referenceTargetType->getTypeNamespace(),
                    $referenceTargetType->getTypeName()
                )
            );
        }
    }
}

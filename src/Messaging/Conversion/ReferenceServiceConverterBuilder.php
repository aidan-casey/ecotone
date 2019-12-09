<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class ReferenceConverterBuilder
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceServiceConverterBuilder implements ConverterBuilder
{
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var TypeDescriptor
     */
    private $sourceType;
    /**
     * @var TypeDescriptor
     */
    private $targetType;

    /**
     * ReferenceConverter constructor.
     * @param string $referenceName
     * @param string $method
     * @param TypeDescriptor $sourceType
     * @param TypeDescriptor $targetType
     */
    private function __construct(string $referenceName, string $method, TypeDescriptor $sourceType, TypeDescriptor $targetType)
    {
        $this->referenceName = $referenceName;
        $this->methodName = $method;
        $this->sourceType = $sourceType;
        $this->targetType = $targetType;
    }

    /**
     * @param string $referenceName
     * @param string $method
     * @param TypeDescriptor $sourceType
     * @param TypeDescriptor $targetType
     * @return ReferenceServiceConverterBuilder
     */
    public static function create(string $referenceName, string $method, TypeDescriptor $sourceType, TypeDescriptor $targetType) : self
    {
        return new self($referenceName, $method, $sourceType, $targetType);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Converter
    {
        $object = $referenceSearchService->get($this->referenceName);

        $reflectionMethod = new \ReflectionMethod($object, $this->methodName);

        if (count($reflectionMethod->getParameters()) !== 1) {
            throw InvalidArgumentException::create("Converter should have only single parameter: {$reflectionMethod}");
        }

        return ReferenceServiceConverter::create(
            $object,
            $this->methodName,
            $this->sourceType,
            $this->targetType
        );
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [$this->referenceName];
    }
}
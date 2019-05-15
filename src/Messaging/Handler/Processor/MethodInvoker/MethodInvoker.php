<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Annotation\Parameter\AllHeaders;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageProcessor;
use SimplyCodedSoftware\Messaging\Handler\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class MethodInvocation
 * @package Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MethodInvoker implements MessageProcessor
{
    /**
     * @var object
     */
    private $objectToInvokeOn;
    /**
     * @var string
     */
    private $objectMethodName;
    /**
     * @var ParameterConverter[]
     */
    private $orderedMethodArguments;
    /**
     * @var bool
     */
    private $isCalledStatically;
    /**
     * @var ConversionService
     */
    private $conversionService;
    /**
     * @var InterfaceToCall
     */
    private $interfaceToCall;
    /**
     * @var AroundMethodInterceptor[]
     */
    private $aroundMethodInterceptors = [];
    /**
     * @var object[]
     */
    private $endpointAnnotations;

    /**
     * MethodInvocation constructor.
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|ParameterConverter[] $methodParameterConverters
     * @param InterfaceToCall $interfaceToCall
     * @param ConversionService $conversionService
     * @param AroundMethodInterceptor[] $aroundMethodInterceptors
     * @param object[] $endpointAnnotations
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct($objectToInvokeOn, string $objectMethodName, array $methodParameterConverters, InterfaceToCall $interfaceToCall, ConversionService $conversionService, array $aroundMethodInterceptors, array $endpointAnnotations)
    {
        Assert::allInstanceOfType($methodParameterConverters, ParameterConverter::class);

        $this->init($objectToInvokeOn, $objectMethodName, $methodParameterConverters, $interfaceToCall);
        $this->objectToInvokeOn = $objectToInvokeOn;
        $this->conversionService = $conversionService;
        $this->objectMethodName = $objectMethodName;
        $this->interfaceToCall = $interfaceToCall;
        $this->aroundMethodInterceptors = $aroundMethodInterceptors;
        $this->endpointAnnotations = $endpointAnnotations;
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        $methodCall = $this->getMethodCall($message);

        $methodInvokerProcessor = new MethodInvokerChainProcessor(
            $methodCall,
            $this,
            $this->aroundMethodInterceptors,
            $this->objectToInvokeOn,
            $this->interfaceToCall,
            $message,
            $this->endpointAnnotations
        );

        return $methodInvokerProcessor->proceed();
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     * @param int             $passedArgumentsCount
     * @param int             $requiredArgumentsCount
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function canBeInvokedWithDefaultArgument(InterfaceToCall $interfaceToCall, int $passedArgumentsCount, int $requiredArgumentsCount): bool
    {
        return (
            $requiredArgumentsCount === 1
            ||
            $requiredArgumentsCount === 2 && $interfaceToCall->getSecondParameter()->getTypeDescriptor()->isNonCollectionArray()
        ) && $passedArgumentsCount === 0;
    }

    /**
     * @param int $passedArgumentsCount
     * @param int $requiredArgumentsCount
     * @return bool
     */
    private function hasEnoughArguments(int $passedArgumentsCount, int $requiredArgumentsCount): bool
    {
        return $passedArgumentsCount === $requiredArgumentsCount;
    }

    /**
     * @param string $invokedClass
     * @param string $methodToInvoke
     * @param InterfaceParameter $invokeParameter
     * @param array|ParameterConverter[] $methodParameterConverters
     * @return ParameterConverter
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function getMethodArgumentFor(string $invokedClass, string $methodToInvoke, InterfaceParameter $invokeParameter, array $methodParameterConverters): ParameterConverter
    {
        foreach ($methodParameterConverters as $methodParameterConverter) {
            if ($methodParameterConverter->isHandling($invokeParameter)) {
                return $methodParameterConverter;
            }
        }

        throw InvalidArgumentException::create("Invoked object {$invokedClass} with method {$methodToInvoke} has no converter for {$invokeParameter->getName()}");
    }

    /**
     * @param string|object $objectToInvokeOn
     * @return string
     */
    private function objectToClassName($objectToInvokeOn): string
    {
        return $this->isCalledStatically ? $objectToInvokeOn : get_class($objectToInvokeOn);
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param ParameterConverterBuilder[] $methodParameters
     * @param ReferenceSearchService $referenceSearchService
     * @return MethodInvoker
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWith($objectToInvokeOn, string $objectMethodName, array $methodParameters, ReferenceSearchService $referenceSearchService): self
    {
        $messageConverters = [];
        foreach ($methodParameters as $methodParameter) {
            $messageConverters[] = $methodParameter->build($referenceSearchService);
        }

        return self::createWithBuiltParameterConverters($objectToInvokeOn, $objectMethodName, $messageConverters, $referenceSearchService, []);
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param ParameterConverterBuilder[] $methodParameters
     * @param ReferenceSearchService $referenceSearchService
     * @param AroundInterceptorReference[] $orderedAroundMethodInterceptorReferences
     * @param object[] $endpointAnnotations
     * @return MethodInvoker
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWithInterceptors($objectToInvokeOn, string $objectMethodName, array $methodParameters, ReferenceSearchService $referenceSearchService, array $orderedAroundMethodInterceptorReferences, array $endpointAnnotations = []): self
    {
        $messageConverters = [];
        foreach ($methodParameters as $methodParameter) {
            $messageConverters[] = $methodParameter->build($referenceSearchService);
        }

        return self::createWithBuiltParameterConverters($objectToInvokeOn, $objectMethodName, $messageConverters, $referenceSearchService, AroundInterceptorReference::createAroundInterceptors($referenceSearchService, $orderedAroundMethodInterceptorReferences), $endpointAnnotations);
    }


    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param ParameterConverter[] $methodParameters
     * @param ReferenceSearchService $referenceSearchService
     * @param AroundMethodInterceptor[] $interceptorsReferences
     * @param object[] $endpointAnnotations
     * @return MethodInvoker
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWithBuiltParameterConverters($objectToInvokeOn, string $objectMethodName, array $methodParameters, ReferenceSearchService $referenceSearchService, array $interceptorsReferences = [], array $endpointAnnotations = []): self
    {
        /** @var InterfaceToCallRegistry $interfaceToCallRegistry */
        $interfaceToCallRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);
        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);

        return new self($objectToInvokeOn, $objectMethodName, $methodParameters, $interfaceToCallRegistry->getFor($objectToInvokeOn, $objectMethodName), $conversionService, $interceptorsReferences, $endpointAnnotations);
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|ParameterConverter[] $methodParameterConverters
     * @param InterfaceToCall $interfaceToCall
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function init($objectToInvokeOn, string $objectMethodName, array $methodParameterConverters, InterfaceToCall $interfaceToCall): void
    {
        $this->isCalledStatically = false;
        if (!is_object($objectToInvokeOn)) {
            if (!$interfaceToCall->isStaticallyCalled()) {
                throw InvalidArgumentException::create("Reference to invoke must be object given {$objectToInvokeOn}");
            }
            $this->isCalledStatically = true;
        }

        $parametersForObjectToInvoke = $interfaceToCall->getInterfaceParameters();
        $passedArgumentsCount = count($methodParameterConverters);
        $requiredArgumentsCount = count($interfaceToCall->getInterfaceParameters());

        if ($this->canBeInvokedWithDefaultArgument($interfaceToCall, $passedArgumentsCount, $requiredArgumentsCount)) {
            if ($interfaceToCall->hasMoreThanOneParameter()) {
                $methodParameterConverters = [
                    $this->createPayloadOrMessageParameter($interfaceToCall, $interfaceToCall->getFirstParameter()),
                    $this->createPayloadOrMessageParameter($interfaceToCall, $interfaceToCall->getSecondParameter())
                ];

                $passedArgumentsCount = 2;
            }else {
                $methodParameterConverters = [$this->createPayloadOrMessageParameter($interfaceToCall, $interfaceToCall->getFirstParameter())];

                $passedArgumentsCount = 1;
            }
        }

        if (!$this->hasEnoughArguments($passedArgumentsCount, $requiredArgumentsCount)) {
            throw InvalidArgumentException::create("Object {$interfaceToCall} requires {$requiredArgumentsCount} parameters converters, but passed {$passedArgumentsCount}");
        }

        $orderedMethodArguments = [];
        foreach ($parametersForObjectToInvoke as $invokeParameter) {
            $orderedMethodArguments[] = $this->getMethodArgumentFor($this->objectToClassName($objectToInvokeOn), $objectMethodName, $invokeParameter, $methodParameterConverters);
        }

        $this->objectToInvokeOn = $objectToInvokeOn;
        $this->objectMethodName = $objectMethodName;
        $this->orderedMethodArguments = $orderedMethodArguments;
    }

    /**
     * @param Message $message
     * @return MethodCall
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function getMethodCall(Message $message): MethodCall
    {
        $sourceMediaType = $message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)
            ? MediaType::parseMediaType($message->getHeaders()->get(MessageHeaders::CONTENT_TYPE))
            : MediaType::createApplicationXPHPObject();
        $replyMediaType = MediaType::createApplicationXPHPObject();

        $methodArguments = [];
        $count = count($this->orderedMethodArguments);

        for ($index = 0; $index < $count; $index++) {
            $interfaceParameter = $this->interfaceToCall->getParameterAtIndex($index);
            $data = $this->orderedMethodArguments[$index]->getArgumentFrom($interfaceParameter, $message);
            $isPayloadConverter = $this->orderedMethodArguments[$index] instanceof PayloadConverter;
            $sourceTypeDescriptor = $isPayloadConverter && $sourceMediaType->hasTypeParameter()
                ? TypeDescriptor::create($sourceMediaType->getParameter("type"))
                : TypeDescriptor::createFromVariable($data);

            $currentParameterMediaType = $isPayloadConverter ? $sourceMediaType : MediaType::createApplicationXPHPObject();
            if ($this->canConvertParameter(
                $index,
                $sourceTypeDescriptor,
                $currentParameterMediaType,
                $replyMediaType
            )) {
                $data = $this->doConversion($data, $index, $sourceTypeDescriptor, $currentParameterMediaType, $replyMediaType);
            }

            $methodArguments[] = MethodArgument::createWith($interfaceParameter, $data);
        }

        return MethodCall::createWith($methodArguments);
    }

    /**
     * @param int $index
     * @param MediaType $requestMediaType
     * @param MediaType $replyMediaType
     * @param TypeDescriptor $requestType
     * @return bool
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function canConvertParameter(int $index, TypeDescriptor $requestType, MediaType $requestMediaType, MediaType $replyMediaType): bool
    {
        return $this->conversionService->canConvert(
            $requestType,
            $requestMediaType,
            $this->interfaceToCall->getParameterAtIndex($index)->getTypeDescriptor(),
            $replyMediaType
        );
    }

    /**
     * @param $data
     * @param int $index
     * @param MediaType $requestMediaType
     * @param MediaType $replyMediaType
     * @param TypeDescriptor $requestType
     * @return mixed
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function doConversion($data, int $index, TypeDescriptor $requestType, MediaType $requestMediaType, MediaType $replyMediaType)
    {
        $data = $this->conversionService->convert(
            $data,
            $requestType,
            $requestMediaType,
            $this->interfaceToCall->getParameterAtIndex($index)->getTypeDescriptor(),
            $replyMediaType
        );

        return $data;
    }

    /**
     * @return InterfaceToCall
     */
    public function getInterfaceToCall(): InterfaceToCall
    {
        return $this->interfaceToCall;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->interfaceToCall;
    }

    /**
     * @param InterfaceToCall    $interfaceToCall
     * @param InterfaceParameter $parameter
     *
     * @return ParameterConverter
     */
    private function createPayloadOrMessageParameter(InterfaceToCall $interfaceToCall, InterfaceParameter $parameter)
    {
        if ($parameter->isMessage()) {
            return MessageConverter::create($parameter->getName());
        } else if ($parameter->getTypeDescriptor()->isNonCollectionArray() && $interfaceToCall->hasMoreThanOneParameter()) {
            return new AllHeadersConverter($parameter->getName());
        } else {
            return PayloadConverter::create($parameter->getName());
        }
}
}
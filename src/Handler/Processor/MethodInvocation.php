<?php

namespace Messaging\Handler\Processor;
use Messaging\Handler\MessageProcessor;
use Messaging\Message;
use Messaging\Support\Assert;
use Messaging\Support\InvalidArgumentException;

/**
 * Class MethodInvocation
 * @package Messaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MethodInvocation implements MessageProcessor
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
     * @var MethodArgument[]
     */
    private $orderedMethodArguments;

    /**
     * MethodInvocation constructor.
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|MethodArgument[] $orderedMethodArguments
     */
    private function __construct($objectToInvokeOn, string $objectMethodName, array $orderedMethodArguments)
    {
        Assert::isObject($objectToInvokeOn, "Passed value for invocation is not an object");
        Assert::allInstanceOfType($orderedMethodArguments, MethodArgument::class);

        $this->init($objectToInvokeOn, $objectMethodName, $orderedMethodArguments);
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array $methodArguments
     * @return MethodInvocation
     */
    public static function createWith($objectToInvokeOn, string $objectMethodName, array $methodArguments) : self
    {
        return new self($objectToInvokeOn, $objectMethodName, $methodArguments);
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        return call_user_func_array([$this->objectToInvokeOn, $this->objectMethodName], $this->getMethodArguments($message));
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array $orderedMethodArguments
     * @throws \Messaging\MessagingException
     */
    private function init($objectToInvokeOn, string $objectMethodName, array $orderedMethodArguments) : void
    {
        if (!$this->hasObjectMethod($objectToInvokeOn, $objectMethodName)) {
            throw InvalidArgumentException::create("Object {$this->objectToClassName($objectToInvokeOn)} does not contain method {$objectMethodName}");
        }

        $objectReflection = new \ReflectionMethod($objectToInvokeOn, $objectMethodName);
        $passedArgumentsCount = count($orderedMethodArguments);
        $requiredArgumentsCount = count($objectReflection->getParameters());

        if ($this->canBeInvokedWithDefaultPayloadArgument($passedArgumentsCount, $requiredArgumentsCount)) {
            $orderedMethodArguments = [PayloadArgument::create()];
            $passedArgumentsCount = 1;
        }

        if (!$this->hasEnoughArguments($passedArgumentsCount, $requiredArgumentsCount)) {
            throw InvalidArgumentException::create("Object {$this->objectToClassName($objectToInvokeOn)} requires {$requiredArgumentsCount}, but passed {$passedArgumentsCount}");
        }

        $this->objectToInvokeOn = $objectToInvokeOn;
        $this->objectMethodName = $objectMethodName;
        $this->orderedMethodArguments = $orderedMethodArguments;
    }

    /**
     * @param Message $message
     * @return array
     */
    private function getMethodArguments(Message $message) : array
    {
        $methodArguments = [];

        foreach ($this->orderedMethodArguments as $methodArgument) {
            $methodArguments[] = $methodArgument->getFrom($message);
        }

        return $methodArguments;
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @return bool
     */
    private function hasObjectMethod($objectToInvokeOn, string $objectMethodName): bool
    {
        return method_exists($objectToInvokeOn, $objectMethodName);
    }

    /**
     * @param $objectToInvokeOn
     * @return string
     */
    private function objectToClassName($objectToInvokeOn): string
    {
        return get_class($objectToInvokeOn);
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
     * @param $requiredArgumentsCount
     * @param $passedArgumentsCount
     * @return bool
     */
    private function canBeInvokedWithDefaultPayloadArgument(int $passedArgumentsCount, int $requiredArgumentsCount): bool
    {
        return $requiredArgumentsCount === 1 && $passedArgumentsCount === 0;
    }

    public function __toString()
    {
        $objectToInvokeOn = get_class($this->objectToInvokeOn);

        return "Object {$objectToInvokeOn}, method {$this->objectMethodName}";
    }
}
<?php


namespace Ecotone\Messaging\Annotation;

use Ecotone\Messaging\Support\Assert;

#[\Attribute(\Attribute::TARGET_METHOD)]
class ConsoleCommand
{
    private string $name;

    public function __construct(string $consoleCommandName)
    {
        Assert::notNullAndEmpty($consoleCommandName, "Console command name can not be empty string");
        $this->name = $consoleCommandName;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
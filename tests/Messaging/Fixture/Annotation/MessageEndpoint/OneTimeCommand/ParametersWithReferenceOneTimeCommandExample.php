<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Annotation\ConsoleCommand;

class ParametersWithReferenceOneTimeCommandExample
{
    #[ConsoleCommand("doSomething")]
    public function execute(string $name, string $surname, \stdClass $object) : void
    {

    }
}
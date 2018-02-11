<?php

namespace Test\SimplyCodedSoftware\Messaging\Handler\Processor;

use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder\PayloadParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadParameterConverter;
use Test\SimplyCodedSoftware\Messaging\MessagingTest;

/**
 * Class PayloadParameterConverterBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadParameterConverterBuilderTest extends MessagingTest
{
    public function test_creating_parameter_converter()
    {
        $parameterName = "parameterName";

        $this->assertEquals(
            PayloadParameterConverter::create($parameterName),
            PayloadParameterConverterBuilder::create($parameterName)->build(InMemoryReferenceSearchService::createEmpty())
        );
    }
}
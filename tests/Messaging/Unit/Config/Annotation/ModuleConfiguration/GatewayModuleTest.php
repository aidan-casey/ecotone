<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\GatewayModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gateway\CombinedGatewayBuilder;
use Ecotone\Messaging\Handler\Gateway\CombinedGatewayDefinition;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderExpressionBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionBuilder;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\BookStoreGatewayExample;
use Test\Ecotone\Messaging\Fixture\Handler\Gateway\MultipleMethodsGatewayExample;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_registering_gateway()
    {
        $annotationGatewayConfiguration = GatewayModule::create(
            InMemoryAnnotationFinder::createFrom([BookStoreGatewayExample::class])
        );

        $messagingSystemConfiguration = $this->createMessagingSystemConfiguration();
        $annotationGatewayConfiguration->prepare($messagingSystemConfiguration, [],ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(
                        BookStoreGatewayExample::class, BookStoreGatewayExample::class,
                        "rent", "requestChannel"
                    )
                        ->withErrorChannel("errorChannel")
                        ->withRequiredInterceptorNames(['dbalTransaction'])
                        ->withReplyMillisecondTimeout(100)
                        ->withParameterConverters([
                            GatewayPayloadExpressionBuilder::create("bookNumber", "upper(value)"),
                            GatewayHeaderBuilder::create("rentTill", "rentDate"),
                            GatewayHeaderBuilder::create("cost", "cost"),
                            GatewayHeadersBuilder::create("data"),
                            GatewayHeaderValueBuilder::create("secret", "123")
                        ])
                ),
            $messagingSystemConfiguration
        );
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_registering_gateway_with_multiple_methods()
    {
        $annotationGatewayConfiguration = GatewayModule::create(InMemoryAnnotationFinder::createFrom([MultipleMethodsGatewayExample::class]));

        $messagingSystemConfiguration = $this->createMessagingSystemConfiguration();
        $annotationGatewayConfiguration->prepare($messagingSystemConfiguration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(
                        MultipleMethodsGatewayExample::class, MultipleMethodsGatewayExample::class,
                        "execute1", "channel1"
                    )
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(
                        MultipleMethodsGatewayExample::class, MultipleMethodsGatewayExample::class,
                        "execute2", "channel2"
                    )
                ),
            $messagingSystemConfiguration
        );
    }

    /**
     * @inheritDoc
     */
    protected function createAnnotationConfiguration(): string
    {
        return GatewayModule::class;
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "MessageEndpoint\Gateway";
    }
}
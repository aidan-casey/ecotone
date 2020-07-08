<?php

namespace Test\Ecotone\Modelling\Unit\Config;

use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Config\ModellingMessageRouterModule;
use Ecotone\Modelling\Config\BusRouterBuilder;
use stdClass;
use Test\Ecotone\Messaging\Unit\MessagingTest;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerExample;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithDoubledActionMethod;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithDoubledFactoryMethod;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithFactoryMethod;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithRedirectionByChannelName;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithRedirectionByClass;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateNoInputChannelAndNoMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\DoStuffCommand;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\ServiceCommandHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\ServiceCommandHandlerWithInputChannelName;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\ServiceCommandHandlerWithInputChannelNameAndIgnoreMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\ServiceCommandHandlerWithInputChannelNameAndObject;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\AggregateCommandHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\AggregateCommandHandlerWithInputChannelName;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\AggregateCommandHandlerWithInputChannelNameAndIgnoreMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\AggregateCommandHandlerWithInputChannelNameAndObject;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\CommandHandlerWithNoInputChannelName;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate\AggregateEventHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate\AggregateEventHandlerWithListenTo;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate\AggregateEventHandlerWithListenToAndObject;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate\AggregateEventHandlerWithListenToRegex;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\ServiceEventHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\ServiceEventHandlerWithListenTo;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\ServiceEventHandlerWithListenToAndObject;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\ServiceEventHandlerWithListenToToRegex;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate\AggregateQueryHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate\AggregateQueryHandlerWithInputChannel;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate\AggregateQueryHandlerWithInputChannelAndIgnoreMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate\AggregateQueryHandlerWithInputChannelAndObject;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlersWithAllowedNotUniqueClass;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlersWithNotUniqueClass;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlerWithInputChannel;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlerWithInputChannelAndIgnoreMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlerWithInputChannelAndObject;

/**
 * Class AggregateMessageRouterModuleTest
 * @package Test\Ecotone\Modelling\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ModellingMessageRouterModuleTest extends MessagingTest
{
    public function test_registering_service_command_handler_with_endpoint_id()
    {
        $annotatedClasses = [
            AggregateCommandHandlerWithClass::class
        ];
        $mapping = [
            stdClass::class => stdClass::class
        ];

        $this->assertRouting($annotatedClasses, $mapping, $mapping, [], [], [], []);
    }

    public function test_throwing_configuration_exception_if_command_handler_has_no_information_about_channel()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationRegistrationService::createFrom([
                CommandHandlerWithNoInputChannelName::class
            ])
        );
    }

    public function test_throwing_exception_when_registering_non_unique_query_handlers()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationRegistrationService::createFrom([
                ServiceQueryHandlersWithNotUniqueClass::class
            ])
        );
    }

    public function test_throwing_exception_when_not_unique_aggregate_factory_methods()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationRegistrationService::createFrom([
                AggregateCommandHandlerWithDoubledFactoryMethod::class
            ])
        );
    }

    public function test_throwing_exception_when_not_unique_aggregate_action_methods()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationRegistrationService::createFrom([
                AggregateCommandHandlerWithDoubledActionMethod::class
            ])
        );
    }

    public function test_throwing_when_factory_and_action_channels_are_same_between_different_aggregates()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationRegistrationService::createFrom([
                AggregateCommandHandlerExample::class,
                AggregateCommandHandlerWithFactoryMethod::class
            ])
        );
    }

    public function test_registering_not_unique_handlers_if_allowed()
    {
        $annotatedClasses = [ServiceQueryHandlersWithAllowedNotUniqueClass::class];
        $mapping = [
            stdClass::class => \stdClass::class
        ];

        $this->assertRouting($annotatedClasses, [], [], $mapping, $mapping, [], []);
    }

    public function test_registering_service_command_handler_with_input_channel()
    {
        $annotatedClasses = [
            AggregateCommandHandlerWithInputChannelName::class
        ];

        $this->assertRouting($annotatedClasses, [], ["execute" => "execute"], [], [], [], []);
    }

    public function test_registering_aggregate_command_handler_with_input_channel()
    {
        $annotatedClasses = [
            ServiceCommandHandlerWithInputChannelName::class
        ];

        $this->assertRouting($annotatedClasses, [], ["execute" => "execute"], [], [], [], []);
    }

    public function test_registering_service_command_handler_with_input_channel_and_class()
    {
        $annotatedClasses = [
            AggregateCommandHandlerWithInputChannelNameAndObject::class
        ];

        $this->assertRouting($annotatedClasses,
            [\stdClass::class => "execute"],["execute" => "execute"], [], [], [], []);
    }

    public function test_registering_service_command_handler_with_input_channel_and_ignore_class()
    {
        $annotatedClasses = [
            AggregateCommandHandlerWithInputChannelNameAndIgnoreMessage::class
        ];

        $this->assertRouting($annotatedClasses,
            [],["execute" => "execute"],[], [], [], []);
    }

    public function test_registering_aggregate_command_handler_with_input_channel_and_ignore_class()
    {
        $annotatedClasses = [
            ServiceCommandHandlerWithInputChannelNameAndIgnoreMessage::class
        ];

        $this->assertRouting($annotatedClasses,
            [],
            [
                "execute" => "execute"
            ]
            , [], [], [], []);
    }

    public function test_registering_service_query_handler()
    {
        $annotatedClasses = [
            ServiceQueryHandlerWithClass::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [stdClass::class => stdClass::class], [stdClass::class => stdClass::class], [], []);
    }

    public function test_registering_aggregate_query_handler()
    {
        $annotatedClasses = [
            AggregateQueryHandlerWithClass::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [stdClass::class => stdClass::class], [stdClass::class => stdClass::class], [], []);
    }

    public function test_registering_service_query_handler_with_input_channel()
    {
        $annotatedClasses = [
            ServiceQueryHandlerWithInputChannel::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], ["execute" => "execute"], [], []);
    }

    public function test_registering_aggregate_query_handler_with_input_channel()
    {
        $annotatedClasses = [
            AggregateQueryHandlerWithInputChannel::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], ["execute" => "execute"], [], []);
    }

    public function test_registering_service_query_handler_with_input_channel_and_class()
    {
        $annotatedClasses = [
            ServiceQueryHandlerWithInputChannelAndObject::class
        ];

        $this->assertRouting($annotatedClasses, [], [],
            [
                \stdClass::class => "execute"
            ],
            [
                "execute" => "execute"
            ],
            [],
            []
        );
    }

    public function test_registering_service_query_handler_with_input_channel_and_ignore_message()
    {
        $annotatedClasses = [
            ServiceQueryHandlerWithInputChannelAndIgnoreMessage::class
        ];

        $this->assertRouting($annotatedClasses, [], [],
            [],
            [
                "execute" => "execute"
            ],
            [],
            []
        );
    }

    public function test_registering_aggregate_query_handler_with_input_channel_and_ignore_message()
    {
        $annotatedClasses = [
            AggregateQueryHandlerWithInputChannelAndIgnoreMessage::class
        ];

        $this->assertRouting($annotatedClasses, [], [],
            [],
            [
                "execute" => "execute"
            ],
            [],
            []
        );
    }

    public function test_registering_service_event_handler()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithClass::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [], [stdClass::class => stdClass::class], [stdClass::class => stdClass::class]);
    }

    public function test_registering_aggregate_event_handler()
    {
        $annotatedClasses = [
            AggregateEventHandlerWithClass::class
        ];;

        $this->assertRouting($annotatedClasses, [], [], [], [], [stdClass::class => stdClass::class], [stdClass::class => stdClass::class]);
    }

    public function test_registering_service_event_handler_with_listen_to()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithListenTo::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [], [], ["execute" => "execute"]);
    }

    public function test_registering_aggregate_event_handler_with_listen_to()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithListenTo::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [], [], ["execute" => "execute"]);
    }

    public function test_registering_service_event_handler_with_listen_to_and_class()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithListenToAndObject::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [],
            [
                \stdClass::class => "execute"
            ],
            [
                "execute" => "execute"
            ]
        );
    }

    public function test_registering_service_event_handler_with_listen_regex()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithListenToToRegex::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [],
            [],
            [
                "order.*" => "order.*"
            ]
        );
    }

    public function test_throwing_exception_if_event_handler_with_listen_regex_registered_for_aggregate()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationRegistrationService::createFrom([
                AggregateEventHandlerWithListenToRegex::class
            ])
        );
    }

    /**
     * @param InMemoryAnnotationRegistrationService $annotationRegistrationService
     * @return \Ecotone\Messaging\Config\Configuration
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \ReflectionException
     */
    private function prepareModule(InMemoryAnnotationRegistrationService $annotationRegistrationService): \Ecotone\Messaging\Config\Configuration
    {
        $module = ModellingMessageRouterModule::create($annotationRegistrationService);

        $extendedConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $module->prepare(
            $extendedConfiguration,
            [],
            ModuleReferenceSearchService::createEmpty()
        );
        return $extendedConfiguration;
    }

    private function assertRouting(array $annotatedClasses, array $commandObjectMapping, array $commandMapping, array $queryObjectMapping, array $queryMapping, array $eventObjectMapping, array $eventNameMapping): void
    {
        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom($annotatedClasses);
        $extendedConfiguration = $this->prepareModule($annotationRegistrationService);

        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerMessageHandler(BusRouterBuilder::createCommandBusByObject($commandObjectMapping))
                ->registerMessageHandler(BusRouterBuilder::createCommandBusByName($commandMapping))
                ->registerMessageHandler(BusRouterBuilder::createQueryBusByObject($queryObjectMapping))
                ->registerMessageHandler(BusRouterBuilder::createQueryBusByName($queryMapping))
                ->registerMessageHandler(BusRouterBuilder::createEventBusByObject($eventObjectMapping))
                ->registerMessageHandler(BusRouterBuilder::createEventBusByName($eventNameMapping)),
            $extendedConfiguration
        );
    }
}
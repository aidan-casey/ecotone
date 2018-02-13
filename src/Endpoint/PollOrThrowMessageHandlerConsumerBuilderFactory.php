<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;

/**
 * Class PollOrThrowPollableFactory
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollOrThrowMessageHandlerConsumerBuilderFactory implements MessageHandlerConsumerBuilderFactory
{
    /**
     * @inheritDoc
     */
    public function isSupporting(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder): bool
    {
        return $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName()) instanceof PollableChannel;
    }

    /**
     * @inheritDoc
     */
    public function create(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder): ConsumerLifecycle
    {
        /** @var PollableChannel $pollableChannel */
        $pollableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());

        return PollOrThrowExceptionConsumer::create($messageHandlerBuilder->getConsumerName(), $pollableChannel, $messageHandlerBuilder->build(
            $channelResolver, $referenceSearchService
        ));
    }
}
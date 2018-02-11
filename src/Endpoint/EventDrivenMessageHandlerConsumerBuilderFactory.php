<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\SubscribableChannel;

/**
 * Class EventDrivenConsumerFactory
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EventDrivenMessageHandlerConsumerBuilderFactory implements MessageHandlerConsumerBuilderFactory
{
    /**
     * @inheritDoc
     */
    public function isSupporting(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder): bool
    {
        return $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName()) instanceof SubscribableChannel;
    }

    /**
     * @inheritDoc
     */
    public function create(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder): ConsumerLifecycle
    {
        /** @var SubscribableChannel $subscribableChannel */
        $subscribableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());

        return new EventDrivenConsumer(
            $messageHandlerBuilder->getConsumerName(),
            $subscribableChannel,
            $messageHandlerBuilder->build($channelResolver, $referenceSearchService)
        );
    }
}
<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

/**
 * Interface MessageHandlerBuilderWithOutputChannel
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageHandlerBuilderWithOutputChannel extends MessageHandlerBuilder
{
    /**
     * @param string $messageChannelName
     *
     * @return static
     */
    public function withOutputMessageChannel(string $messageChannelName);

    /**
     * @return string
     */
    public function getOutputMessageChannelName() : string;
}
<?php declare(strict_types=1);

namespace WyriHaximus\React\ObservableBunny;

use Bunny\Channel;
use Bunny\Message as BunnyMessage;

final class Message
{
    /**
     * @var BunnyMessage
     */
    private $message;

    /**
     * @var Channel
     */
    private $channel;

    /**
     * @param BunnyMessage $message
     * @param Channel      $channel
     */
    public function __construct(BunnyMessage $message, Channel $channel)
    {
        $this->message = $message;
        $this->channel = $channel;
    }

    /**
     * @return BunnyMessage
     */
    public function getMessage(): BunnyMessage
    {
        return $this->message;
    }

    /**
     * @return Channel
     */
    public function getChannel(): Channel
    {
        return $this->channel;
    }

    /**
     * Convenience ack.
     *
     * @return bool|\React\Promise\PromiseInterface
     */
    public function ack()
    {
        return $this->channel->ack($this->message);
    }

    /**
     * Convenience nack.
     *
     * @return bool|\React\Promise\PromiseInterface
     */
    public function nack()
    {
        return $this->channel->nack($this->message);
    }
}

<?php declare(strict_types=1);

namespace WyriHaximus\Tests\React\ObservableBunny;

use ApiClients\Tools\TestUtilities\TestCase;
use Bunny\Channel;
use Bunny\Message as BunnyMessage;
use WyriHaximus\React\ObservableBunny\Message;
use function React\Promise\resolve;

final class MessageTest extends TestCase
{
    public function testGetters()
    {
        $message = $this->prophesize(BunnyMessage::class)->reveal();
        $channel = $this->prophesize(Channel::class)->reveal();

        $messageDto = new Message($message, $channel);

        self::assertSame($message, $messageDto->getMessage());
        self::assertSame($channel, $messageDto->getChannel());
    }

    public function testAck()
    {
        $message = $this->prophesize(BunnyMessage::class)->reveal();
        $channel = $this->prophesize(Channel::class);
        $channel->ack($message)->shouldBeCalled()->willReturn(resolve(true));

        $messageDto = new Message($message, $channel->reveal());

        $messageDto->ack();
    }

    public function testNack()
    {
        $message = $this->prophesize(BunnyMessage::class)->reveal();
        $channel = $this->prophesize(Channel::class);
        $channel->nack($message)->shouldBeCalled()->willReturn(resolve(true));

        $messageDto = new Message($message, $channel->reveal());

        $messageDto->nack();
    }
}

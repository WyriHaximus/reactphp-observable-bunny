<?php declare(strict_types=1);

namespace WyriHaximus\Tests\React\ObservableBunny;

use ApiClients\Tools\TestUtilities\TestCase;
use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message as BunnyMessage;
use Bunny\Protocol\MethodBasicConsumeOkFrame;
use Exception;
use Prophecy\Argument;
use React\EventLoop\Factory;
use function React\Promise\reject;
use WyriHaximus\React\ObservableBunny\Message;
use WyriHaximus\React\ObservableBunny\ObservableBunny;
use function React\Promise\resolve;

final class ObservableBunnyTest extends TestCase
{
    public function testConsume()
    {
        $message = new BunnyMessage('abc', 'xyz', false, '', 'beer', [], 'foo.bar');

        $methodBasicConsumeOkFrame = new MethodBasicConsumeOkFrame();
        $methodBasicConsumeOkFrame->consumerTag = 'abc';

        $loop = Factory::create();

        $channel = $this->prophesize(Channel::class);
        $channel->close()->shouldBeCalled()->willReturn(resolve(true));
        $channel->cancel('abc')->shouldBeCalled()->willReturn(resolve(true));
        $channel->consume(
            Argument::that(function ($lambda) use ($message, $channel, $loop) {
                $loop->futureTick(function () use ($lambda, $message, $channel) {
                    $lambda($message, $channel->reveal());
                });

                return true;
            }),
            'queue:name',
            '',
            false,
            false,
            false,
            false,
            []
        )->shouldBeCalled()->willReturn(resolve($methodBasicConsumeOkFrame));

        $bunny = $this->prophesize(Client::class);
        $bunny->channel()->shouldBeCalled()->willReturn(resolve($channel->reveal()));

        $observableBunny = new ObservableBunny($loop, $bunny->reveal());
        $subject = $observableBunny->consume('queue:name');
        /** @var Message $messageDto */
        $messageDto = null;
        $subject->subscribe(function (Message $message) use (&$messageDto, $subject) {
            $messageDto = $message;
            $subject->dispose();
        });

        $loop->run();

        self::assertSame($message, $messageDto->getMessage());
    }

    public function testConsumeError()
    {
        $error = new Exception('consume:error');

        $loop = Factory::create();

        $channel = $this->prophesize(Channel::class);
        $channel->consume(
            Argument::that(function () {
                return true;
            }),
            'queue:name',
            '',
            false,
            false,
            false,
            false,
            []
        )->shouldBeCalled()->willReturn(reject($error));

        $bunny = $this->prophesize(Client::class);
        $bunny->channel()->shouldBeCalled()->willReturn(resolve($channel->reveal()));

        $observableBunny = new ObservableBunny($loop, $bunny->reveal());
        $subject = $observableBunny->consume('queue:name');

        $errorDot = null;
        $subject->subscribe(null, function (Exception $error) use (&$errorDot) {
            $errorDot = $error;
        });

        $loop->run();

        self::assertSame($error, $errorDot);
    }

    public function testChannelError()
    {
        $error = new Exception('channel:error');

        $loop = Factory::create();

        $bunny = $this->prophesize(Client::class);
        $bunny->channel()->shouldBeCalled()->willReturn(reject($error));

        $observableBunny = new ObservableBunny($loop, $bunny->reveal());
        $subject = $observableBunny->consume('queue:name');

        $errorDot = null;
        $subject->subscribe(null, function (Exception $error) use (&$errorDot) {
            $errorDot = $error;
        });

        $loop->run();

        self::assertSame($error, $errorDot);
    }
}

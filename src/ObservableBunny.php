<?php declare(strict_types=1);

namespace WyriHaximus\React\ObservableBunny;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message as BunnyMessage;
use Bunny\Protocol\MethodBasicConsumeOkFrame;
use React\EventLoop\LoopInterface;
use Rx\Subject\Subject;

final class ObservableBunny
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Client
     */
    private $bunny;

    /**
     * @param LoopInterface $loop
     * @param Client        $bunny
     */
    public function __construct(LoopInterface $loop, Client $bunny)
    {
        $this->loop = $loop;
        $this->bunny = $bunny;
    }

    public function consume(
        string $queue = '',
        string $consumerTag = '',
        bool $noLocal = false,
        bool $noAck = false,
        bool $exclusive = false,
        bool $nowait = false,
        array $arguments = []
    ): Subject {
        $subject = new Subject();
        $consumeArgs = [$queue, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $arguments];

        $channel = $this->bunny->channel();
        $channel->then(function (Channel $channel) use ($subject, $consumeArgs) {
            $consumerTag = null;
            $timer = $this->loop->addPeriodicTimer(1, function () use ($channel, $subject, &$timer, &$consumerTag) {
                if (!$subject->isDisposed()) {
                    return;
                }

                $channel->cancel($consumerTag)->done([$subject, 'onComplete'], [$subject, 'onError']);
                $this->loop->cancelTimer($timer);
            });
            $channel->consume(
                function (BunnyMessage $message, Channel $channel) use ($subject, &$timer, &$consumerTag) {
                    if ($subject->isDisposed()) {
                        $channel->nack($message);
                        $channel->cancel($consumerTag)->done([$subject, 'onComplete'], [$subject, 'onError']);
                        $this->loop->cancelTimer($timer);

                        return;
                    }

                    $subject->onNext(new Message($message, $channel));
                },
                ...$consumeArgs
            )->then(function (MethodBasicConsumeOkFrame $response) use (&$consumerTag) {
                $consumerTag = $response->consumerTag;
            })->done(null, [$subject, 'onError']);
        })->done(null, [$subject, 'onError']);

        return $subject;
    }
}

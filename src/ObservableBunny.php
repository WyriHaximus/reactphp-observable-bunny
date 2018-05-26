<?php declare(strict_types=1);

namespace WyriHaximus\React\ObservableBunny;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message as BunnyMessage;
use Bunny\Protocol\MethodBasicConsumeOkFrame;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\PromiseInterface;
use Rx\Subject\Subject;
use Throwable;

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
     * @var float
     */
    private $checkInterval;

    /**
     * @param LoopInterface $loop
     * @param Client        $bunny
     * @param float         $checkInterval
     */
    public function __construct(LoopInterface $loop, Client $bunny, float $checkInterval = 1.0)
    {
        $this->loop = $loop;
        $this->bunny = $bunny;
        $this->checkInterval = $checkInterval;
    }

    public function consume(
        string $queue = '',
        array $qos = [],
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
        $channel->then(function (Channel $channel) use ($qos) {
            if (count($qos) === 0) {
                return $channel;
            }

            return $channel->qos(...$qos)->then(function () use ($channel) {
                return $channel;
            });
        })->then(function (Channel $channel) use ($subject, $consumeArgs) {
            /** @var string $consumerTag */
            $consumerTag = null;
            $timer = $this->loop->addPeriodicTimer($this->checkInterval, function () use ($channel, $subject, &$timer, &$consumerTag) {
                if (!$subject->isDisposed()) {
                    return;
                }

                $this->cancelSubscription(
                    $timer,
                    $channel,
                    $consumerTag
                )->done([$subject, 'onComplete'], $this->onError($subject, $timer));
            });
            $channel->consume(
                function (BunnyMessage $message, Channel $channel) use ($subject, &$timer, &$consumerTag) {
                    if ($subject->isDisposed()) {
                        $channel->nack($message);
                        $this->cancelSubscription(
                            $timer,
                            $channel,
                            $consumerTag
                        )->done([$subject, 'onComplete'], $this->onError($subject, $timer));

                        return;
                    }

                    $subject->onNext(new Message($message, $channel));
                },
                ...$consumeArgs
            )->then(function (MethodBasicConsumeOkFrame $response) use (&$consumerTag) {
                $consumerTag = $response->consumerTag;
            })->done(null, $this->onError($subject, $timer));
        })->done(null, [$subject, 'onError']);

        return $subject;
    }

    private function cancelSubscription(TimerInterface $timer, Channel $channel, string $consumerTag): PromiseInterface
    {
        $this->loop->cancelTimer($timer);

        return $channel->cancel($consumerTag)->then(function () use ($channel) {
            return $channel->close();
        });
    }

    private function onError(Subject $subject, TimerInterface $timer): callable
    {
        return function (Throwable $et) use ($subject, $timer) {
            $this->loop->cancelTimer($timer);
            $subject->onError($et);
        };
    }
}

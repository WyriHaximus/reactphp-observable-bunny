<?php declare(strict_types=1);

// react/event-loop 0.5 moved the location of the TimerInterface
// This file creates a class alias to be compatible with both 0.4 and 0.5

if (
    !interface_exists('React\EventLoop\TimerInterface') &&
    interface_exists('React\EventLoop\Timer\TimerInterface')
) {
    class_alias(
        'React\EventLoop\Timer\TimerInterface', // react/event-loop <  0.5
        'React\EventLoop\TimerInterface'        // react/event-loop >= 0.5
    );
}

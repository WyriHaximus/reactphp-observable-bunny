# RxPHP decorator around bunny

[![Build Status](https://travis-ci.org/WyriHaximus/reactphp-observable-bunny.svg?branch=master)](https://travis-ci.org/WyriHaximus/reactphp-observable-bunny)
[![Latest Stable Version](https://poser.pugx.org/WyriHaximus/react-observable-bunny/v/stable.png)](https://packagist.org/packages/WyriHaximus/react-observable-bunny)
[![Total Downloads](https://poser.pugx.org/WyriHaximus/react-observable-bunny/downloads.png)](https://packagist.org/packages/WyriHaximus/react-observable-bunny)
[![Code Coverage](https://scrutinizer-ci.com/g/WyriHaximus/reactphp-observable-bunny/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/WyriHaximus/reactphp-observable-bunny/?branch=master)
[![License](https://poser.pugx.org/WyriHaximus/react-observable-bunny/license.png)](https://packagist.org/packages/WyriHaximus/react-observable-bunny)
[![PHP 7 ready](http://php7ready.timesplinter.ch/WyriHaximus/reactphp-observable-bunny/badge.svg)](https://travis-ci.org/WyriHaximus/reactphp-observable-bunny)

# Installation

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require wyrihaximus/react-observable-bunny 
```

# Usage

The following example will connect to RabbitMQ, consume messages from the `queue:name` queue, with prefetch (QOS) set 
to 10 for two minutes before canceling the subscription and disconnect the client.

```php
<?php

use Bunny\Async\Client;
use React\EventLoop\Factory;
use WyriHaximus\React\ObservableBunny\Message;
use WyriHaximus\React\ObservableBunny\ObservableBunny;

$loop = Factory::create();
$bunny = new Client($loop);
$observableBunny = new ObservableBunny($loop, $bunny);
// OR to check the dispose status on another interval then once a second, like twice a second
$observableBunny = new ObservableBunny($loop, $bunny, 0.5);
$queue = $observableBunny->consume('queue:name', [0, 10]);
$loop->addTimer(120, function () use ($queue, $bunny) {
    $queue->dispose();
    $bunny->disconnect();
});
$queue->subscribe(function (Message $message) {
    // Handle message
});

$loop->run();

```

# License

The MIT License (MIT)

Copyright (c) 2018 Cees-Jan Kiewiet

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

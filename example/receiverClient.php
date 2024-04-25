<?php

use OpenSwoole\Coroutine;

Coroutine::run(function () {

    $client = new \OpenSwoole\Coroutine\Http\Client('0.0.0.0', 8080);

    $client->setHeaders([
        "User-Agent" => 'Chrome/49.0.2587.3',
        'Accept' => 'text/html,application/xhtml+xml,application/xml',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Sec-WebSocket-Protocol' => 'wamp.2.json, wamp.2.msgpack'
    ]);

    $upgraded = $client->upgrade('/');
    Coroutine::create(function () use ($upgraded)  {
        if ($upgraded) {

        }
    });

    Coroutine::create(function () use ($client) {
        while ($client->connected) {
            Coroutine::usleep(1);
            $data = $client->recv();
            Coroutine::create(function (\OpenSwoole\WebSocket\Frame $frame) {
                echo 'Received: ' . $frame->data . PHP_EOL;
            }, $data);
        }
    });
});

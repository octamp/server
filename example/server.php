<?php

use Octamp\Server\Connection\Connection;
use Octamp\Server\Server;
use OpenSwoole\WebSocket\Frame;

require_once __DIR__ . '/../vendor/autoload.php';

$websocket = Server::createWebsocketServer('0.0.0.0', 8080, ['worker_num' => 2]);
$server = new Server($websocket);
$server->on('beforeStart', function (Server $server) {
    $adapter = new \Octamp\Server\Adapter\RedisAdapter('0.0.0.0', 6379);
    $server->setAdapter($adapter);
});
$server->on('open', function (Server $server, Connection $connection) {
    $connection->send('Welcome');
});
$server->on('message', function (Server $server, Connection $connection, Frame $frame) {
    $connections = $server->getConnectionStorage()->getAll();
    foreach ($connections as $connection) {
        $connection->send($frame->data);
    }
});
$server->on('close', function (Server $server, Connection $connection) {
    echo 'Connection Closed: ' . $connection->getId() . PHP_EOL;
});

$server->start();
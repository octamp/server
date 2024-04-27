<?php

namespace Octamp\Server;

use Octamp\Server\Connection\Connection;

interface ServerInterface
{
    public function getServerId(): string;

    public function sendMessage(string $serverId, int $fd, ?string $data = null): void;

    public function ping(int $fd, ?string $data = null): void;

    public function pong(int $fd, ?string $data = null): void;

    public function closeConnection(Connection $connection): void;
}
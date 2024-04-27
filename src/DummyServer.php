<?php

namespace Octamp\Server;

use Octamp\Server\Connection\Connection;

class DummyServer implements ServerInterface
{
    public function __construct(public readonly string $serverId, private ServerInterface $server)
    {
    }

    public function getServerId(): string
    {
        return $this->serverId;
    }

    public function sendMessage(string $serverId, int $fd, ?string $data = null): void
    {
        $this->server->sendMessage($serverId, $fd, $data);
    }

    public function ping(int $fd, ?string $data = null): void
    {
    }

    public function pong(int $fd, ?string $data = null): void
    {
        // TODO: Implement pong() method.
    }

    public function closeConnection(Connection $connection): void
    {
        // TODO: Implement closeConnection() method.
    }
}
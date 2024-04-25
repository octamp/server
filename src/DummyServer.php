<?php

namespace Octamp\Server;

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
}
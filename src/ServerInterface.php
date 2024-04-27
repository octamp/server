<?php

namespace Octamp\Server;

interface ServerInterface
{
    public function getServerId(): string;

    public function sendMessage(string $serverId, int $fd, ?string $data = null): void;

    public function ping(int $fd, ?string $data): void;
}
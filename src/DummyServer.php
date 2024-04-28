<?php
declare(strict_types=1);

namespace Octamp\Server;

use Octamp\Server\Connection\Connection;
use Octamp\Server\Generator\IDGeneratorInterface;
use Octamp\Server\Generator\ServerIdGeneratorInterface;

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

    public function getGenerator(): ?ServerIdGeneratorInterface
    {
        // TODO: Implement getGenerator() method.
    }

    public function setGenerator(ServerIdGeneratorInterface $generator): void
    {
        // TODO: Implement setGenerator() method.
    }
}
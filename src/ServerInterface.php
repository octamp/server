<?php
declare(strict_types=1);

namespace Octamp\Server;

use Octamp\Server\Connection\Connection;
use Octamp\Server\Generator\ServerIdGeneratorInterface;

interface ServerInterface
{
    public function getServerId(): string;

    public function sendMessage(string $serverId, int $fd, ?string $data = null, int $opcode = \OpenSwoole\WebSocket\Server::WEBSOCKET_OPCODE_TEXT): void;

    public function ping(int $fd, ?string $data = null): void;

    public function pong(int $fd, ?string $data = null): void;

    public function closeConnection(Connection $connection): void;

    public function getGenerator(): ?ServerIdGeneratorInterface;

    public function setGenerator(ServerIdGeneratorInterface $generator): void;
}
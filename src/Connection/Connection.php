<?php
declare(strict_types=1);

namespace Octamp\Server\Connection;

use JetBrains\PhpStorm\ArrayShape;
use Octamp\Server\DummyServer;
use Octamp\Server\ServerInterface;
use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Server;

class Connection
{
    protected string $id;

    public static function createFromArray(
        #[ArrayShape([
            'id' => 'string',
            'fd' => 'int',
            'server' => 'string',
            'request' => 'array',
        ])] array $data,
        ServerInterface $server
    ): static {
        $connectionServer = $server;
        if ($server->getServerId() !== $data['server']) {
            $connectionServer = new DummyServer($data['server'], $server);
        }

        $request = new Request();
        $request->fd = $data['request']['fd'];
        $request->header = $data['request']['header'];
        $request->server = $data['request']['server'];
        $request->cookie = $data['request']['cookie'];
        $request->get = $data['request']['get'];
        $request->files = $data['request']['files'];
        $request->post = $data['request']['post'];
        $request->tmpfiles = $data['request']['tmpfiles'];

        return new static($request, $connectionServer);
    }

    public static function generateId(string $serverId, int $fd): string
    {
        return $serverId . ':' . $fd;
    }

    public static function getIdPart(string $id): array
    {
        [$serverId, $fd] = explode(':' , $id);

        return ['serverId' => $serverId, 'fd' => (int) $fd];
    }

    public function __construct(public readonly Request $request, private readonly ServerInterface $server, ?string $id = null)
    {
        $this->id = $id ?? static::generateId($this->server->getServerId(), $this->request->fd);
    }

    public function getFd(): int
    {
        return $this->request->fd;
    }

    public function send(null|array|string $data, int $opcode = Server::WEBSOCKET_OPCODE_TEXT): void
    {
        $message = $data;
        if (is_array($data)) {
            $message = json_encode($message);
        }
        $this->server->sendMessage($this->getServerId(), $this->getFd(), $message, $opcode);
    }

    public function close(): void
    {
        $this->server->closeConnection($this);
    }

    public function ping(?string $data = null): void
    {
        $this->server->ping($this->getFd(), $data);
    }

    public function pong(?string $data = null): void
    {
        $this->server->pong($this->getFd(), $data);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getServerId(): string
    {
        return $this->server->getServerId();
    }

    public function requestToArray(): array
    {
        return [
            'fd' => $this->request->fd,
            'header' => $this->request->header,
            'server' => $this->request->server,
            'cookie' => $this->request->cookie,
            'get' => $this->request->get,
            'files' => $this->request->files,
            'post' => $this->request->post,
            'tmpfiles' => $this->request->tmpfiles,
        ];
    }
}
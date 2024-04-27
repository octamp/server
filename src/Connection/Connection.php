<?php

namespace Octamp\Server\Connection;

use JetBrains\PhpStorm\ArrayShape;
use Octamp\Server\DummyServer;
use Octamp\Server\ServerInterface;
use OpenSwoole\Http\Request;
use Octamp\Server\Server;

class Connection
{
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

    public function __construct(public readonly Request $request, private readonly ServerInterface $server)
    {
        // Noting to implement
    }

    public function getFd(): int
    {
        return $this->request->fd;
    }

    public function send(null|array|string $data): void
    {
        $message = $data;
        if (is_array($data)) {
            $message = json_encode($message);
        }
        $this->server->sendMessage($this->getServerId(), $this->getFd(), $message);
    }

    public function close(): void
    {
        // TODO: Implement close() method.
    }

    public function ping(?string $data): void
    {
        $this->server->ping($this->getFd(), $data);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getId(): string
    {
        return $this->server->getServerId() . ':' . $this->getFd();
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
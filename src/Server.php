<?php

namespace Octamp\Server;


use Octamp\Server\Adapter\AdapterInterface;
use Octamp\Server\Connection\Connection;
use Octamp\Server\Connection\ConnectionStorage;
use OpenSwoole\Constant;
use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server as WebsocketServer;

class Server implements ServerInterface
{
    public ?string $serverId = null;
    private ?AdapterInterface $adapter = null;
    protected array $callbacks = [];
    protected ?ConnectionStorage $connectionStorage = null;

    protected bool $ready = false;

    public static function createWebsocketServer(string $host, int $port, array $options = []): WebsocketServer
    {
        \OpenSwoole\Coroutine::set(['hook_flags' => \OpenSwoole\Runtime::HOOK_ALL]);
        \OpenSwoole\Runtime::enableCoroutine(true);
        $server = new WebsocketServer($host, $port, WebsocketServer::POOL_MODE, Constant::SOCK_TCP);
        $server->set($options);

        return $server;
    }


    public function __construct(private WebsocketServer $server)
    {
        $this->initWebsocket();
    }

    private function initWebsocket(): void
    {
        $this->server->on("Start", function(WebsocketServer $server) {
            echo 'Server Started:' . $server->host . ':' . $server->port . PHP_EOL;
            $this->dispatch('serverStart');
        });

        $this->server->on('Open', function(WebsocketServer $server, Request $request) {
            $this->onOpen($request);
        });

        $this->server->on('Message', function(WebsocketServer $server, Frame $frame) {
            $this->onMessage($frame);
        });

        $this->server->on('Close', function(WebsocketServer $server, int $fd) {
            $this->onClose($fd);
        });

        $this->server->on('WorkerStart', function (WebsocketServer $server, int $workerId) {
            $this->onStart();
        });
    }

    public function start(): void
    {
        $this->server->start();
    }


    protected function onStart(): void
    {
        if ($this->ready) {
            return;
        }

        $this->dispatch('beforeStart');
        if ($this->serverId === null) {
            $this->serverId = uniqid('', true) . '-' . $this->server->worker_id;
        }

        if ($this->adapter === null) {
            $this->server->stop($this->server->worker_id);
            throw new \RuntimeException('Need to have adapter');
        }
        $this->adapter->start($this->serverId);
        $this->connectionStorage = new ConnectionStorage($this, $this->adapter);

        $this->adapter->subscribe('client:push', function (string $serverId, int $fd, string $data) {
            if ($this->serverId === $serverId) {
                $connection = $this->connectionStorage->getUsingServerFd($serverId, $fd);
                $connection->send($data);
            }
        });

        $this->dispatch('afterStart');
        $this->ready = true;
    }

    protected function onOpen(Request $request): void
    {
        if (!$this->ready) {
            $this->server->close($request->fd);
            return;
        }

        $connection = new Connection($request, $this);
        $this->connectionStorage->save($connection);

        $this->dispatch('open', $connection);
    }

    protected function onMessage(Frame $frame): void
    {
        if (!$this->ready) {
            $this->server->close($frame->fd);
            return;
        }

        $connection = $this->connectionStorage->getUsingServerFd($this->serverId, $frame->fd);
        $this->dispatch('message', $connection, $frame);
    }

    protected function onClose(int $fd): void
    {
        if (!$this->ready) {
            return;
        }

        $connection = $this->connectionStorage->getUsingServerFd($this->serverId, $fd);
        if ($connection === null) {
            return;
        }
        $this->connectionStorage->remove($connection);
        $this->dispatch('close', $connection);
    }

    public function sendMessage(string $serverId, int $fd, ?string $data = null): void
    {
        if ($serverId === $this->serverId) {
            $this->server->push($fd, $data);
        } else {
            $this->adapter->publish('client:push', [$serverId, $fd, $data], $serverId);
        }
    }

    public function pong(int $fd, ?string $data = null): void
    {
        $frame = new Frame();
        $frame->opcode = WebsocketServer::WEBSOCKET_OPCODE_PONG;
        $frame->data = $data;

        $this->server->push($fd, $frame);
    }

    public function ping(int $fd, ?string $data = null): void
    {
        $frame = new Frame();
        $frame->opcode = WebsocketServer::WEBSOCKET_OPCODE_PING;
        $frame->data = $data;

        $this->server->push($fd, $frame);
    }

    public function getServerId(): string
    {
        return $this->serverId;
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function getConnectionStorage(): ConnectionStorage
    {
        return $this->connectionStorage;
    }

    /**
     * Replaced the events
     *
     * @param string $event
     * @param callable $callback
     * @return void
     */
    public function on(string $event, callable $callback): void
    {
        $this->callbacks[$event] = $callback;
    }

    public function dispatch(string $event, ...$args): void
    {
        if (isset($this->callbacks[$event]) && is_callable($this->callbacks[$event])) {
            $this->callbacks[$event]($this, ...$args);
        }
    }

    public function setAdapter(AdapterInterface $adapter): void
    {
        $this->adapter = $adapter;
    }
}
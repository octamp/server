<?php

namespace Octamp\Server\Connection;

use Octamp\Server\Adapter\AdapterInterface;
use Octamp\Server\Adapter\RedisAdapter;
use Octamp\Server\Server;

class ConnectionRepository
{
    public function __construct(private Server $server, private AdapterInterface $adapter)
    {
    }

    public function saveConnection(Connection $connection): void
    {
        $id = $connection->getId();
        $this->adapter->set('con:' . $id, [
            'id' => $id,
            'fd' => $connection->getFd(),
            'server' => $connection->getServerId(),
            'request' => $connection->requestToArray(),
        ]);
    }

    public function getConnection(string $id): ?Connection
    {
        $data = $this->adapter->get('con:' . $id);
        if ($data === null) {
            return null;
        }

        return Connection::createFromArray($data, $this->server);
    }

    public function allRaw(): array
    {
        return $this->adapter->find('con:*');
    }

    public function getAllId(): array
    {
        $keys = $this->adapter->keys('con:*');
        $results = [];
        foreach ($keys as $key) {
            $results[] = explode(':', $key, 2)[1];
        }

        return $results;
    }

    /**
     * @return array<string, array{serverId: string, fd: int}>
     */
    public function getAllIdParted(): array
    {
        $ids = $this->getAllId();
        $results = [];

        foreach ($ids as $id) {
            [$serverId, $fd] = explode(':', $id);
            $results[$id] = ['serverId' => $serverId, 'fd' => (int) $fd];
        }

        return $results;
    }

    public function removeConnection(string $id): void
    {
        $this->adapter->del('con:' . $id);
    }
}
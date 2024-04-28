<?php
declare(strict_types=1);

namespace Octamp\Server\Connection;

use Octamp\Server\Adapter\AdapterInterface;
use Octamp\Server\Server;

class ConnectionStorage
{
    private ConnectionRepository $connectionRepository;

    /**
     * @var Connection[]
     */
    private array $connections = [];

    public function __construct(private Server $server, private AdapterInterface $adapter)
    {
        $this->connectionRepository = new ConnectionRepository($this->server, $this->adapter);
    }

    public function save(Connection $connection): void
    {
        $this->connections[$connection->getId()] = $connection;
        $this->connectionRepository->saveConnection($connection);
    }

    public function remove(Connection $connection): void
    {
        $connection = $this->connections[$connection->getId()];
        $this->connectionRepository->removeConnection($connection->getId());
        unset($this->connections[$connection->getId()]);
    }

    public function getUsingServerFd(string $serverId, int $fd): ?Connection
    {
        $id = Connection::generateId($serverId, $fd);

        return $this->get($id);
    }

    public function get(string $id): ?Connection
    {
        if ($this->isLocal($id)) {
            return $this->connections[$id];
        }

        try {
            return $this->connectionRepository->getConnection($id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return array<string, array{serverId: string, fd: int}>
     */
    public function allIds(): array
    {
        return $this->connectionRepository->getAllIdParted();
    }

    /**
     * @return Connection[]
     */
    public function getAll(): array
    {
        $raw = $this->connectionRepository->allRaw();
        $results = [];

        foreach ($raw as $value) {
            try {
                $result = Connection::createFromArray($value, $this->server);
                $results[] = $result;
            } catch (\Exception $exception) {
                // don nothing
            }
        }

        return $results;
    }

    public function isLocal(string $id): bool
    {
        return isset($this->connections[$id]);
    }
}
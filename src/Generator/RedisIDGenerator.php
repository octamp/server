<?php
declare(strict_types=1);

namespace Octamp\Server\Generator;

use Octamp\Server\Adapter\RedisAdapter;
use Octamp\Server\Server;

class RedisIDGenerator implements ServerIdGeneratorInterface
{
    public function __construct(protected Server $server, protected RedisAdapter $adapter)
    {
    }

    public function generateServerId(int $workerId): string
    {
        $prefix = date('Ym');
        $incremented = (string) $this->adapter->inc('server:id', 1, $prefix);

        return $prefix . str_pad($incremented, 3, '0', STR_PAD_LEFT);
    }
}
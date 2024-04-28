<?php
declare(strict_types=1);

namespace Octamp\Server\Generator;

class DefaultIDGenerator implements ServerIdGeneratorInterface
{
    public function generateServerId(int $workerId): string
    {
        return (string) $workerId;
    }
}
<?php
declare(strict_types=1);

namespace Octamp\Server\Generator;

interface ServerIdGeneratorInterface
{
    public function generateServerId(int $workerId): string;
}
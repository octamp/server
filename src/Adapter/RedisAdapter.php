<?php
declare(strict_types=1);

namespace Octamp\Server\Adapter;

use OpenSwoole\Coroutine;
use Predis\Client as PredisClient;
use Predis\PubSub\Consumer;

class RedisAdapter implements AdapterInterface
{
    private PredisClient $publisher;

    private PredisClient $subscriber;

    private ?Consumer $pubSubConsumer = null;

    private ?int $subscriberId = null;

    private bool $active = false;

    private array $subscribers = [];

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly ?string $username = null,
        private readonly ?string $password= null,
        private readonly array $options = []
    ) {
        $this->publisher = $this->createPredis();
        $this->subscriber = $this->createPredis();
    }

    public function createPredis(?string $host = null, ?int $port = null, ?array $options = null): PredisClient
    {
        $parameters = [
            'host' => $host ?? $this->host,
            'port' => $port ?? $this->port,
            'client_info' => true,
        ];
        if ($this->username !== null) {
            $parameters['username'] = $this->username;
        }
        if ($this->password !== null) {
            $parameters['password'] = $this->password;
        }

        return new PredisClient($parameters, $options ?? $this->options);
    }

    public function start(string $serverId): void
    {
        $this->active = true;

        Coroutine::create(function () use ($serverId) {
            $this->subscriberId = $this->subscriber->client('id');

            $this->pubSubConsumer = $this->subscriber->pubSubLoop();
            $this->pubSubConsumer->subscribe($serverId . ':message');
            $this->pubSubConsumer->subscribe('global:message');

            while ($this->active) {
                try {
                    foreach ($this->pubSubConsumer as $message) {
                        Coroutine::create(function () use ($message) {
                            $mainKind = $message->kind;
                            // $channel = $message->channel;
                            $payload = $message->payload;

                            if ($mainKind !== 'message') {
                                return;
                            }
                            $data = json_decode($payload, true);
                            if (is_array($data)) {
                                $this->onMessage($data);
                            }
                        });
                    }
                } catch (\Exception $exception) {
                    // nothing
                }
            }
        });
    }

    public function onMessage(array $data): void
    {
        [$topic, $payload] = $data;
        if (!isset($this->subscribers[$topic])) {
            return;
        }
        $this->subscribers[$topic](...$payload);
    }

    public function subscribe(string $topic, callable $callback): void
    {
        $this->subscribers[$topic] = $callback;
    }

    public function publish(string $topic, array $payload = [], ?string $serverId = null): void
    {
        $channel = ($serverId ?? 'global') . ':message';
        $client = $this->createPredis();
        $client->publish($channel, json_encode([$topic, $payload]));
        $client->quit();
    }

    public function set(string $key, array $data = []): void
    {
        $client = $this->createPredis();
        foreach ($data as $field => $value) {
            if (is_array($value)) {
                $client->hset($key, (string) $field, json_encode($value));
            } else {
                $client->hset($key, (string) $field, $value);
            }
        }
        $client->quit();
    }

    public function del(string $key, array $fields = []): void
    {
        $client = $this->createPredis();
        if (!empty($fields)) {
            $client->hdel($key, $fields);
        } else {
            $client->del($key);
        }
        $client->quit();
    }

    public function get(string $key, array $fields = []): ?array
    {
        $client = $this->createPredis();
        if (!$client->exists($key)) {
            $client->quit();
            return null;
        }
        $result = [];

        if (empty($fields)) {
            $result = $client->hgetall($key);
        } else {
            foreach ($fields as $field) {
                $result[$field] = $client->hget($key, $field);
            }
        }

        $client->quit();

        return $this->decodeData($result);
    }

    public function find(string $search): array
    {
        $client = $this->createPredis();
        $keys = $client->keys($search);
        $results = [];
        foreach ($keys as $key) {
            $results[] = $this->decodeData($client->hgetall($key));
        }

        $client->quit();

        return $results;
    }

    public function keys(string $search): array
    {
        $client = $this->createPredis();
        $keys = $client->keys($search);
        $client->quit();

        return $keys;
    }

    private function decodeData(array $data): array
    {
        foreach ($data as &$value) {
            try {
                $newValue = json_decode($value, true);
                if (is_array($newValue)) {
                    $value = $newValue;
                }
            } catch (\Exception $exception) {
            }
        }

        return $data;
    }

    public function inc(string $key, int $increment = 1, ?string $field = null): int
    {
        $client = $this->createPredis();
        if ($field !== null) {
            $value = $client->hincrby($key, $field, $increment);
        } else {
            $value = $client->incrby($key, $increment);
        }
        $client->quit();

        return $value;
    }
}
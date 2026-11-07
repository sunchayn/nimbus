<?php

namespace Workbench\App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * A personal access token kept in the file cache, so both workbench servers can read it.
 */
class DemoAccessToken
{
    private const PREFIX = 'workbench-demo-token.';

    /**
     * @param  list<string>  $abilities
     */
    public function __construct(
        public readonly string $key,
        public readonly int|string $userId,
        public readonly array $abilities,
    ) {}

    /**
     * @param  list<string>  $abilities
     */
    public static function create(int|string $userId, array $abilities): NewDemoAccessToken
    {
        $plainTextToken = bin2hex(random_bytes(20));
        $key = self::PREFIX.hash('sha256', $plainTextToken);

        Cache::store('file')->put($key, ['user_id' => $userId, 'abilities' => $abilities], now()->addHour());

        return new NewDemoAccessToken($plainTextToken, new self($key, $userId, $abilities));
    }

    public static function find(?string $plainTextToken): ?self
    {
        if ($plainTextToken === null || $plainTextToken === '') {
            return null;
        }

        $key = self::PREFIX.hash('sha256', $plainTextToken);
        $payload = Cache::store('file')->get($key);

        return is_array($payload) ? new self($key, $payload['user_id'], $payload['abilities']) : null;
    }

    public function delete(): bool
    {
        return Cache::store('file')->forget($this->key);
    }
}

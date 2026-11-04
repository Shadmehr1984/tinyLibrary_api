<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;
use App\Repositories\Exceptions\KeyIsEmptyException;
use App\Repositories\Exceptions\HashSetIsEmptyException;
use App\Repositories\Exceptions\ExpireTimeNotInTheRangeException;
use App\Repositories\Exceptions\KeyNoInCacheException;

class CacheRepository
{
    const MIN_EXPIRE_TIME = 30;
    const MAX_EXPIRE_TIME = 180;
    const SCAN_COUNT = 5;

    public static function set(string $key, array $hash_set, int $expire_time)
    {
        static::validate($key, $hash_set, $expire_time);

        foreach ($hash_set as $hash_key => $hash_value) {
            Redis::hSet($key, $hash_key, $hash_value);
        }
        Redis::expire($key, $expire_time);
    }

    public static function get(string $key): array
    {
        static::validate($key);

        $exists = Redis::exists($key);

        if ($exists == 0) {
            throw new KeyNoInCacheException($key);
        }

        $result = Redis::hGetAll($key);

        return $result;
    }

    public static function search_keys(string $word, int $count = null)
    {
        $pattern = "*" . $word . "*";

        $count = isset($count) && $count > 0 ? $count : static::SCAN_COUNT;

        $cursor = '0';
        $keys = [];
        do {
            [$cursor, $batch] = Redis::scan($cursor, 'MATCH', $pattern, 'COUNT', $count);
            $keys = array_merge($keys, $batch);
        } while ($cursor !== '0');

        static::fix_searched_keys($keys);

        return $keys;
    }

    public static function exists(string $key)
    {
        static::validate($key);

        $result = Redis::exists($key);

        $exists = $result == 0 ? false : true;

        return $exists;
    }

    public static function obj_count(): int
    {
        $obj_count = Redis::dbSize();

        return $obj_count;
    }

    private static function fix_searched_keys(array &$keys)
    {
        $prefix = 'laravel-database-';
        $length = strlen($prefix);

        $keys = array_map(
            fn ($s) => str_starts_with($s, $prefix) ? substr($s, $length) : $s,
            $keys
        );
    }

    private static function validate(string $key, array $hash_set = null, int $expire_time = null)
    {
        if (strlen($key) == 0) {
            throw new KeyIsEmptyException();
        }
        if (isset($hash_set) && sizeof($hash_set) == 0) {
            throw new HashSetIsEmptyException();
        }
        if (isset($expire_time) && $expire_time < static::MIN_EXPIRE_TIME || $expire_time > static::MAX_EXPIRE_TIME) {
            throw new ExpireTimeNotInTheRangeException(static::MIN_EXPIRE_TIME, static::MAX_EXPIRE_TIME);
        }
    }
}

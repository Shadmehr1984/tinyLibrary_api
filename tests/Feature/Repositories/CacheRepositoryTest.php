<?php

namespace Tests\Feature\Repositories;

use App\Repositories\CacheRepository;
use App\Repositories\Exceptions\ExpireTimeNotInTheRangeException;
use App\Repositories\Exceptions\HashSetIsEmptyException;
use App\Repositories\Exceptions\KeyIsEmptyException;
use App\Repositories\Exceptions\KeyNoInCacheException;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class CacheRepositoryTest extends TestCase
{
    public function test_set_writes_hash_fields_and_expiration(): void
    {
        Redis::shouldReceive('hSet')->once()->with('book:1', 'title', 'Clean Code');
        Redis::shouldReceive('hSet')->once()->with('book:1', 'author', 'Robert Martin');
        Redis::shouldReceive('expire')->once()->with('book:1', 60);

        CacheRepository::set('book:1', [
            'title' => 'Clean Code',
            'author' => 'Robert Martin',
        ], 60);

        $this->assertTrue(true);
    }

    public function test_set_rejects_empty_key_hash_and_expiration_outside_range(): void
    {
        try {
            CacheRepository::set('', ['field' => 'value'], 60);
            $this->fail('Expected empty-key exception.');
        } catch (KeyIsEmptyException $exception) {
            $this->assertSame('key is empty', $exception->getMessage());
        }

        try {
            CacheRepository::set('key', [], 60);
            $this->fail('Expected empty-hash exception.');
        } catch (HashSetIsEmptyException $exception) {
            $this->assertSame('hash set is empty', $exception->getMessage());
        }

        foreach ([29, 181] as $expireTime) {
            try {
                CacheRepository::set('key', ['field' => 'value'], $expireTime);
                $this->fail('Expected invalid-expiration exception.');
            } catch (ExpireTimeNotInTheRangeException $exception) {
                $this->assertSame('expire time not in range 30 and 180', $exception->getMessage());
            }
        }
    }

    public function test_get_returns_hash_and_throws_when_key_is_missing(): void
    {
        Redis::shouldReceive('exists')->once()->with('missing')->andReturn(0);

        try {
            CacheRepository::get('missing');
            $this->fail('Expected missing-key exception.');
        } catch (KeyNoInCacheException $exception) {
            $this->assertSame('key missing is not in cache', $exception->getMessage());
        }

        Redis::shouldReceive('exists')->once()->with('book:1')->andReturn(1);
        Redis::shouldReceive('hGetAll')->once()->with('book:1')->andReturn([
            'title' => 'Clean Code',
        ]);

        $this->assertSame(['title' => 'Clean Code'], CacheRepository::get('book:1'));
    }

    public function test_get_and_exists_reject_empty_keys(): void
    {
        try {
            CacheRepository::get('');
            $this->fail('Expected empty-key exception.');
        } catch (KeyIsEmptyException $exception) {
            $this->assertSame('key is empty', $exception->getMessage());
        }

        try {
            CacheRepository::exists('');
            $this->fail('Expected empty-key exception.');
        } catch (KeyIsEmptyException $exception) {
            $this->assertSame('key is empty', $exception->getMessage());
        }
    }

    public function test_exists_returns_boolean_for_both_redis_results(): void
    {
        Redis::shouldReceive('exists')->once()->with('present')->andReturn(1);
        Redis::shouldReceive('exists')->once()->with('absent')->andReturn(0);

        $this->assertTrue(CacheRepository::exists('present'));
        $this->assertFalse(CacheRepository::exists('absent'));
    }

    public function test_obj_count_returns_redis_database_size(): void
    {
        Redis::shouldReceive('dbSize')->once()->andReturn(42);

        $this->assertSame(42, CacheRepository::obj_count());
    }

    public function test_search_keys_scans_multiple_batches_and_removes_laravel_prefix(): void
    {
        Redis::shouldReceive('scan')
            ->once()
            ->with('0', 'MATCH', '*book*', 'COUNT', 2)
            ->andReturn(['1', ['laravel-database-book:1', 'book:2']]);

        Redis::shouldReceive('scan')
            ->once()
            ->with('1', 'MATCH', '*book*', 'COUNT', 2)
            ->andReturn(['0', ['laravel-database-book:3']]);

        $this->assertSame(
            ['book:1', 'book:2', 'book:3'],
            CacheRepository::search_keys('book', 2)
        );
    }

    public function test_search_keys_uses_default_count_for_null_or_non_positive_count(): void
    {
        Redis::shouldReceive('scan')
            ->once()
            ->with('0', 'MATCH', '**', 'COUNT', CacheRepository::SCAN_COUNT)
            ->andReturn(['0', []]);

        $this->assertSame([], CacheRepository::search_keys(''));

        Redis::shouldReceive('scan')
            ->once()
            ->with('0', 'MATCH', '*member*', 'COUNT', CacheRepository::SCAN_COUNT)
            ->andReturn(['0', ['member:1']]);

        $this->assertSame(['member:1'], CacheRepository::search_keys('member', 0));
    }
}

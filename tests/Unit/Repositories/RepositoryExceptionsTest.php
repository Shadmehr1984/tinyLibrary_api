<?php

namespace Tests\Unit\Repositories;

use App\Repositories\Exceptions\ExpireTimeNotInTheRangeException;
use App\Repositories\Exceptions\HashSetIsEmptyException;
use App\Repositories\Exceptions\KeyIsEmptyException;
use App\Repositories\Exceptions\KeyNoInCacheException;
use App\Repositories\Exceptions\MemberNotExistsException;
use PHPUnit\Framework\TestCase;

class RepositoryExceptionsTest extends TestCase
{
    public function test_key_is_empty_exception_message(): void
    {
        $exception = new KeyIsEmptyException();

        $this->assertSame('key is empty', $exception->getMessage());
    }

    public function test_key_not_in_cache_exception_includes_key(): void
    {
        $exception = new KeyNoInCacheException('book:42');

        $this->assertSame('key book:42 is not in cache', $exception->getMessage());
    }

    public function test_hash_set_is_empty_exception_message(): void
    {
        $exception = new HashSetIsEmptyException();

        $this->assertSame('hash set is empty', $exception->getMessage());
    }

    public function test_member_not_exists_exception_includes_member_id(): void
    {
        $exception = new MemberNotExistsException(42);

        $this->assertSame('member_id 42 not exists', $exception->getMessage());
    }

    public function test_expire_time_out_of_range_exception_includes_bounds(): void
    {
        $exception = new ExpireTimeNotInTheRangeException(30, 180);

        $this->assertSame('expire time not in range 30 and 180', $exception->getMessage());
    }
}

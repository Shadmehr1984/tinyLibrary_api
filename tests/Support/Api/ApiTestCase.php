<?php

namespace Tests\Support\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // CacheRepository calls the Redis facade directly. Replace its facade
        // root in API tests so an unconfigured call can never reach real Redis.
        Redis::swap(Mockery::mock());
    }
}

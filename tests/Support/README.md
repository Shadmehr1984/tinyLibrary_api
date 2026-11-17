# API test support

The API test suite has its own support layer under `tests/Support`.

- `Api/ApiTestCase.php`: API feature-test base class. It refreshes the database and replaces the Redis facade root with a Mockery mock so tests cannot accidentally connect to a developer's Redis server.
- `Factories/TestDataFactory.php`: creates deterministic domain records and explicitly wires foreign-key relationships. Category defaults use a count-based unique name so multiple books can be created in one test. Do not depend on arbitrary foreign-key IDs from the application's default factories.
- `Helpers/AuthenticatesApiUsers.php`: creates Sanctum tokens for member and librarian identities and attaches them as Bearer tokens to HTTP requests.

Feature tests should extend `Tests\Support\Api\ApiTestCase` and use the helper trait when needed:

```php
use Tests\Support\Helpers\AuthenticatesApiUsers;

class ExampleApiTest extends ApiTestCase
{
    use AuthenticatesApiUsers;
}
```

When a code path uses `CacheRepository`, explicitly configure the required Redis facade expectations with `Redis::shouldReceive(...)`. Do not replace or mock the cache service/business logic itself merely to avoid Redis. The API base case intentionally installs a strict Mockery mock; an unconfigured Redis command fails rather than reaching a real Redis connection.

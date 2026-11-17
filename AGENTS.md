# Agent Instructions

## Mandatory project guidance
- Read `.agent/endpoint-testing-plan.md` before working on the endpoint API testing task.
- Treat that document as the source of truth for scope, constraints, phase gates, proposed test organization, and progress.
- Update its progress section immediately after every meaningful advancement. Do not defer progress updates until the end of a phase or conversation.
- Do not start test implementation until Phase 1 (API contract analysis) is documented and explicitly approved by the user.
- Do not rely on or extend the user's existing tests for this task; maintain the independent test organization described in the plan.
- Do not change production behavior or API contracts without separate explicit user approval.
- Be precise about verification: never report a test, command, or phase as successful unless it was actually run or completed and the result was observed.

## Handoff rule
At the beginning of each task, read the plan and its latest progress table. At the end, leave the plan updated with completed work, current status, blockers, and the exact next step.

## API test infrastructure conventions
- All HTTP-level Feature tests must call the effective Laravel API URL with the `/api` prefix (for example, `/api/v1/member/login` and `/api/v1/librarian/search_book`). The paths declared inside `routes/api.php` omit this framework-added prefix; never copy those route declarations directly into HTTP test URLs.
- New API Feature tests must extend `Tests\\Support\\Api\\ApiTestCase`, not the user's existing test case directly.
- Use `Tests\\Support\\Factories\\TestDataFactory` for explicit, related domain records; override unique values when creating multiple records of the same type in one test.
- Use `Tests\\Support\\Helpers\\AuthenticatesApiUsers` for member/librarian Sanctum Bearer tokens.
- API test base case replaces the Redis facade root with a strict Mockery mock. Configure explicit `Redis::shouldReceive(...)` expectations for Redis-backed paths; never allow tests to reach a real Redis server.
- The Phase 2 infrastructure is not complete until the new infrastructure smoke tests have actually run successfully. Record the exact command and observed result in the progress document.

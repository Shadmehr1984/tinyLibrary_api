# Agent Instructions

## Mandatory project guidance
- Read `.agent/endpoint-testing-plan.md` before working on the endpoint API testing task.
- Read `.agent/coverage-improvement-plan.md` before working on coverage analysis or coverage-focused tests.
- Treat the relevant plan as the source of truth for its scope, constraints, phase gates, test organization, and progress.
- Update the relevant plan's progress section immediately after every meaningful advancement. Do not defer progress updates until the end of a phase or conversation.
- Do not start endpoint test implementation until Phase 1 (API contract analysis) is documented and explicitly approved by the user.
- Do not rely on or extend the user's existing tests for endpoint API work; maintain the independent test organization described in the endpoint plan.
- Do not change production behavior, API contracts, or PHPUnit coverage source scope without separate explicit user approval.
- Do not add tests merely to execute lines or inflate a percentage; assertions must verify meaningful behavior, state transitions, validation, or error handling.
- Be precise about verification: never report a test, command, coverage run, or phase as successful unless it was actually run or completed and the result was observed.

## Handoff rule
At the beginning of each task, read the applicable plan and its latest progress table/log. At the end, leave the relevant plan updated with completed work, current status, blockers, and the exact next step.

## API test infrastructure conventions
- All HTTP-level Feature tests must call the effective Laravel API URL with the `/api` prefix (for example, `/api/v1/member/login` and `/api/v1/librarian/search_book`). The paths declared inside `routes/api.php` omit this framework-added prefix; never copy those route declarations directly into HTTP test URLs.
- New API Feature tests must extend `Tests\\Support\\Api\\ApiTestCase`, not the user's existing test case directly.
- Use `Tests\\Support\\Factories\\TestDataFactory` for explicit, related domain records; override unique values when creating multiple records of the same type in one test.
- Use `Tests\\Support\\Helpers\\AuthenticatesApiUsers` for member/librarian Sanctum Bearer tokens.
- API test base case replaces the Redis facade root with a strict Mockery mock. Configure explicit `Redis::shouldReceive(...)` expectations for Redis-backed paths; never allow tests to reach a real Redis server.
- The Phase 2 infrastructure is not complete until the new infrastructure smoke tests have actually run successfully. Record the exact command and observed result in the progress document.

## Coverage work conventions
- Coverage analysis and focused tests must follow `.agent/coverage-improvement-plan.md`.
- Classify uncovered code by actual route/call-graph reachability before deciding whether to add tests.
- Do not test empty base-class placeholders or unused code solely to raise the aggregate percentage.
- Keep the full-`app/` coverage metric honest. Any proposed source-scope adjustment requires explicit user approval and must be documented.
- Separate confirmed production defects from coverage gaps; production fixes require explicit approval.

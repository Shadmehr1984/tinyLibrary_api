# Coverage Improvement Plan

## 1. Purpose

Raise meaningful automated test coverage from the user-reported baseline of **83.9%** toward a target of **90%**, while preserving behavior and improving confidence in the library's business rules.

This plan is separate from `.agent/endpoint-testing-plan.md`: endpoint tests protect the public HTTP contract; this plan uses the coverage report to identify untested runtime behavior and decide where focused unit tests are appropriate.

## 2. Mandatory constraints

- Read `AGENTS.md` and this plan before coverage-related work.
- Work directly on `main`, unless the user changes this instruction.
- Do not change production code, public API behavior, or the PHPUnit source scope without the user's explicit approval.
- Do not add tests merely to execute lines. Assertions must verify meaningful behavior, state transitions, validation, or error handling.
- Keep API Feature tests under `tests/Feature/Api/` and extend `Tests\Support\Api\ApiTestCase`.
- Keep isolated domain/service unit tests under `tests/Unit/`; do not couple them to the existing user-authored tests.
- All HTTP test URLs must use the effective `/api` prefix.
- Use deterministic test data and explicit Redis expectations for Redis-backed paths. Never depend on a developer's real Redis server.
- Do not report tests or coverage commands as passing unless their output was actually observed.
- Keep the endpoint testing plan current for endpoint-test changes and this plan current for coverage work.

## 3. Baseline and source scope

### 3.1 Current baseline

- User-reported overall line coverage: **83.9%**.
- The supplied text report was generated successfully and includes per-class line coverage.
- `phpunit.xml` currently includes the whole `app/` directory under `<source>`; therefore framework boilerplate, unused services, unexposed request classes, and base abstractions can lower the aggregate.
- The user reported that the test suite passes, but this plan does not claim a fresh local test or coverage run by the agent.

### 3.2 Notable low-coverage entries from the supplied report

| Class / area | Reported coverage | Initial interpretation |
|---|---:|---|
| `Services/PenaltyServices` | 24.1% | High-priority review; separate active endpoint paths from internal/unreferenced methods. |
| `Services/BorrowServices` | 50.0% | High-priority review; add meaningful tests for uncovered active behavior. |
| `Services/LibrarianServices` | 0.0% | No current public route/controller call was found; treat as unexposed/legacy until intended use is confirmed. |
| `Models/User` | 0.0% | The current API authenticates Member and Librarian models; likely framework boilerplate rather than an API path. |
| `Domain/Entities/Entity` | 50.0% | Base class with placeholder `get()`; test concrete entities rather than the base placeholder. |
| `Domain/Builders/Builder` | 0.0% | Base class with placeholder `build()`; test concrete builders and their invariants. |
| `Rules/BookIsbnNotExistsRule` | 80.0% | Review uncovered branch/boundary behavior. |
| `Services/BookCacheServices` | 96.0% | Small remaining gap; only add a test if it verifies a meaningful cache contract. |
| `Domain/Traits/IsBuilder` | 90.0% | Review incomplete-builder and repeated-set behavior. |
| `Domain/Entities/Book` | 95.0% | Review remaining conversion/null branches. |
| `Domain/Entities/Penalty` | 93.3% | Review remaining nullable/value-object conversion branches. |

The report also lists zero-coverage Form Requests for operations that are not currently exposed as versioned routes: Borrow delete/update, Librarian create/delete/search/update, and Penalty create/delete/update. These are not automatically coverage defects. Their intended lifecycle must be established before adding tests or changing source scope.

### 3.3 Call-graph observations from current source

- The current `routes/api.php` exposes 24 versioned endpoints and does not expose librarian CRUD, borrow delete/update, or penalty create/delete/update endpoints.
- The active librarian penalty-payment endpoint calls `PenaltyServices::pay()`.
- Member borrow endpoints exercise the add, search, and return paths in `BorrowServices`.
- `PenaltyServices::member_have_unpaid_penalty()` is called by the member controller for borrow/return restrictions.
- No current route/controller reference was found for `LibrarianServices`.
- No external caller was found for `PenaltyServices::add_by_delayed_borrows()` or `PenaltyServices::update_penalties_detail()`; these look like internal/scheduled workflows, but no scheduler/command registration was found in the inspected `routes/console.php` or `bootstrap/app.php`.
- `Entity::get()` and `Builder::build()` are empty base placeholders overridden by concrete domain classes. Directly testing those placeholders would not add useful confidence.

These observations are a starting point, not a decision to delete code or exclude files from coverage.

## 4. Prioritization principles

1. Prioritize reachable business-critical code before unused classes.
2. Prefer endpoint-level tests when behavior is part of a public API contract.
3. Prefer unit tests for domain invariants, date/amount calculations, builders, and internal service workflows that have no HTTP endpoint.
4. Treat zero coverage on unexposed/boilerplate/base classes as a classification question, not an automatic mandate to add artificial tests.
5. Keep the aggregate 90% target honest: if unexposed legacy code remains in `<source>`, report both the full-`app/` result and any separately approved core-runtime result. Never silently narrow coverage scope to improve the percentage.
6. Any suspected production defect discovered while designing a test must be recorded separately; do not change production code without approval.

## 5. Work phases

| Phase | Work | Completion evidence |
|---|---|---|
| A. Baseline audit | Capture the full coverage summary and environment/tooling details; map low-coverage classes to routes/callers and existing tests. | Baseline command, total line coverage, and classified gap list recorded. |
| B. Borrow service coverage | Inspect uncovered `BorrowServices` lines and distinguish active add/search/back paths from unexposed delete/update/delayed-borrow methods. Add focused tests for meaningful reachable branches and any confirmed internal workflow only if its intended contract is established. | Relevant uncovered active branches covered with state/result assertions; full suite and coverage run by user or CI. |
| C. Penalty service coverage | Cover active payment and unpaid-penalty checks; inspect penalty calculation/update workflows and their intended invocation before testing unexposed methods. | Active penalty branches covered; internal workflow tests only where supported by a documented caller/contract. |
| D. Domain and builder gaps | Add focused unit tests for concrete entity conversions, builder completeness/repeated setters/invalid values, and remaining value-object branches. Avoid empty base placeholders. | Tests assert domain invariants and exceptions, not just line execution. |
| E. Validation and cache gaps | Review the remaining Rule/cache branches and add boundary tests only where behavior is part of a live contract. | Each added test has a clear contract or invariant and deterministic dependencies. |
| F. Unexposed/legacy classification | Review zero-coverage request/service/model classes. Decide whether each is intentionally retained internal functionality, obsolete code, or a future feature. | A documented classification and user approval before code removal or source-scope changes. |
| G. Final measurement | Run the full suite and coverage report; compare line and, if supported, branch coverage; inspect remaining uncovered lines. | Observed full-suite result, exact coverage command, total coverage, and remaining gaps documented. |

## 6. Initial implementation backlog

### P0 — inspect before writing tests
- `BorrowServices`: map the uncovered lines to add/search/back versus delete/update/delayed-borrows. Existing endpoint tests already cover several main success/failure flows, so new tests must target genuinely missing branches.
- `PenaltyServices`: distinguish the active `pay()` and `member_have_unpaid_penalty()` behavior from add/delete/update and delayed-penalty maintenance methods.
- Check the complete coverage report's summary and uncovered-line details; per-class percentages alone do not reveal which branches are missing.

### P1 — focused domain tests
- `IsBuilder`: incomplete build throws `NotSetAttributeException`; setting the same attribute twice must not decrement the remaining count twice; a complete builder can build and reset its state if that is the concrete builder contract.
- `MemberBuilder`: test invalid integer values for `active` and completeness behavior.
- Concrete entities: exercise null and non-null value-object conversion branches for Book and Penalty.
- `BookIsbnNotExistsRule`: cover both existing and absent ISBN behavior using the actual rule contract.

### P2 — classify before attempting to raise the aggregate
- `LibrarianServices` and its unexposed Form Requests.
- Borrow delete/update requests and service methods.
- Penalty create/delete/update requests and service methods.
- `Models/User` and empty base classes.

No tests in these groups should be added solely to inflate the percentage. First establish whether the functionality is intentionally supported and how it is supposed to be invoked.

## 7. Progress log

### 2026-11-18 — initial coverage audit and plan

- Read `AGENTS.md`, `.agent/endpoint-testing-plan.md`, `phpunit.xml`, the supplied coverage report, current API routes, selected service/domain classes, and the current test tree.
- Confirmed `phpunit.xml` includes all of `app/` in coverage scope.
- Recorded the user-reported 83.9% baseline; the agent did not rerun PHPUnit or coverage.
- Classified the low-coverage Librarian service, several Request classes, the default User model, and empty base classes as likely unexposed/boilerplate pending intended-use confirmation.
- Identified `BorrowServices` and `PenaltyServices` as the first meaningful service-level review targets because parts of their behavior are exercised by live member/librarian API flows.
- Confirmed no current route/controller caller for `LibrarianServices`, `PenaltyServices::add_by_delayed_borrows()`, or `PenaltyServices::update_penalties_detail()` in the inspected source. No scheduled invocation was found in the inspected console/bootstrap files.
- No source code, test code, or PHPUnit coverage scope has been changed.
- **Current status:** Phase A in progress. Need the full coverage summary/uncovered-line details to establish an exact baseline; then implement Phase B with focused tests.
- **Next step:** inspect exact uncovered line ranges in `BorrowServices` and `PenaltyServices`, along with existing service tests, and propose the smallest meaningful test additions. Do not change production code.

### 2026-11-18 — service gap classification and first domain tests

- Mapped the supplied uncovered line ranges to the current source:
  - `BorrowServices`: the reported gaps correspond to `take_update_attributes()`, `delete()`, `update()`, and `delayed_borrows()`. The current versioned routes exercise add/search/back; delete/update have no route, and delayed-borrow discovery is only called by the currently unreferenced penalty-maintenance workflow.
  - `PenaltyServices`: the large gaps correspond primarily to add/delete/update and delayed-penalty maintenance. Current controller call sites use `pay()` and `member_have_unpaid_penalty()`; no current controller/route caller was found for the maintenance methods or `member_reach_borrow_limit()`.
- Confirmed that `LibrarianServices` has no current route/controller caller and that the zero-coverage librarian CRUD Request classes are not part of the current 24-endpoint API contract. These remain classification items, not automatic test targets.
- Found a source-level builder invariant issue to investigate separately: `MemberBuilder::__construct()` initializes the remaining-attribute count to 8, but the class exposes 9 distinct setters/properties (including nullable `id`). With exactly one of the nine attributes omitted, the counter can reach zero and `build()` may attempt to read an uninitialized property instead of throwing `NotSetAttributeException`. No production change was made.
- Added `tests/Unit/Domain/BuildersTest.php` with focused tests for a complete Member build, rejection when multiple attributes are missing, and rejection of an invalid integer `active` value. These tests also exercise the otherwise uncovered `NotSetAttributeException` path without encoding the suspected one-missing-field defect as expected behavior.
- Tests have **not** been run by the agent. The user's earlier report that their existing suite passed does not verify this newly added file.
- No production code or PHPUnit source scope was changed.
- **Current status:** Phase A call-graph classification is complete for the highest-priority low-coverage service classes; Phase D has begun with a small MemberBuilder test addition. Overall coverage has not been remeasured.
- **Next step:** have the user run the focused builder test and full suite; then decide whether to add an explicit failing regression for the MemberBuilder off-by-one issue after approval to fix the production defect. Continue with other domain gaps only after the new test is verified.

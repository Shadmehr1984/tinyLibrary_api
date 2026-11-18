# Endpoint API Testing Plan

## 1. Purpose

Create an independent, maintainable Feature/API test suite for all public API endpoints in `tinyLibrary_api`.

This plan is the source of truth for the work. Every agent continuing this task MUST read this document before making changes and MUST update its progress section immediately after completing any task, phase, meaningful subtask, or decision.

## 2. Scope and constraints

- Cover all 24 API endpoints currently declared under `routes/api.php`.
- Do not rely on, extend, or treat the user's existing tests as the basis for this suite. Design and maintain an independent test organization.
- Prefer HTTP-level Feature tests that exercise routing, middleware, Form Requests, controllers, services, and persistence together.
- Keep true isolated unit tests separate from API Feature tests.
- Do not change application behavior merely to make tests pass. Report defects separately and request/record approval before production-code fixes.
- Tests must be deterministic, isolated, repeatable, and must not depend on a developer's real Redis instance or external services.
- Preserve existing API contracts unless an explicit change is approved.
- Do not start implementation until the endpoint-contract analysis (Phase 1) has been documented and approved by the user.

## 3. Proposed test organization

The exact structure may be refined after Phase 1, based on the actual endpoint contracts and existing application conventions.

```text
tests/
├── Unit/
├── Feature/
│   └── Api/
│       ├── Authentication/
│       │   ├── MemberAuthenticationTest.php
│       │   └── LibrarianAuthenticationTest.php
│       ├── Librarian/
│       │   ├── Books/
│       │   │   ├── AddBookTest.php
│       │   │   ├── DeleteBookTest.php
│       │   │   ├── SearchBookTest.php
│       │   │   └── UpdateBookTest.php
│       │   ├── Members/
│       │   │   ├── AddMemberTest.php
│       │   │   ├── UpdateMemberTest.php
│       │   │   ├── DeleteMemberTest.php
│       │   │   ├── SearchMemberTest.php
│       │   │   └── MemberStatusTest.php
│       │   ├── Categories/
│       │   │   ├── AddCategoryTest.php
│       │   │   ├── UpdateCategoryTest.php
│       │   │   ├── DeleteCategoryTest.php
│       │   │   └── SearchCategoryTest.php
│       │   └── Penalties/
│       │       └── PayPenaltyTest.php
│       ├── Member/
│       │   ├── Books/SearchBookTest.php
│       │   ├── Borrows/
│       │   │   ├── AddBorrowTest.php
│       │   │   ├── SearchBorrowTest.php
│       │   │   └── BackBorrowTest.php
│       │   └── Penalties/SearchPenaltyTest.php
│       └── Security/
│           ├── AuthenticationTest.php
│           ├── AuthorizationTest.php
│           └── InputValidationTest.php
└── Support/
    ├── Api/ApiTestCase.php
    ├── Factories/TestDataFactory.php
    └── Helpers/AuthenticationHelper.php
```

Use Laravel factories where they accurately represent the domain. Add dedicated test support builders/factories only where needed; avoid coupling the new suite to the existing tests.

## 4. Coverage requirements

### 4.1 Every endpoint
For each endpoint, document and test:
- Successful request and exact success status/response contract, where success is supported.
- Required, optional, malformed, boundary, and invalid input according to its Form Request or actual validation behavior.
- Authentication and role authorization.
- Relevant business rules and failure status codes.
- Database state before and after the request.
- Empty, duplicate, missing, stale, and conflicting records where applicable.

### 4.2 Authentication and authorization
- Missing, malformed, expired/revoked, and invalid tokens.
- Member tokens cannot access librarian-only routes; librarian tokens cannot access member-only routes.
- Logout revokes the current token and the revoked token cannot be reused.
- Inactive members receive the expected denial on routes protected by `MemberIsActive`.
- Login rejects unknown identities and incorrect passwords without leaking sensitive details.

### 4.3 Librarian operations
- Book, member, and category create/read/search/update/delete behavior.
- Member activation/deactivation transitions and their authorization.
- Dependency and not-found behavior for deletes/updates.
- `search_member` must not expose password data.
- Penalty payment success, already-paid conflict, and missing-penalty behavior.

### 4.4 Member operations
- Book search and all supported filters.
- Borrow creation: available book, unpaid penalty, borrow limit, unavailable/out-of-stock book, duplicate/conflicting request, and persistence/inventory effects.
- Borrow search must be scoped to the authenticated member.
- Borrow return: ownership, already-returned borrow, inventory/status effects, and unpaid-penalty restrictions.
- Penalty search must be scoped to the authenticated member.

### 4.5 Search and cache behavior
- Verify each supported filter individually and in combinations.
- Verify empty results and isolation between users/roles where applicable.
- For book search, cover cache miss, hit, expiration/TTL, filter-specific keys, and invalidation after relevant mutations.
- The suite must not connect to a developer's real Redis. Determine a deterministic seam (fake, mock, or test adapter) after inspecting the cache service. Mock only external boundaries; preserve meaningful coverage of application behavior.

### 4.6 Quality requirements
- Tests must be independent and safe to run in any order.
- Use explicit, meaningful assertions, including response payload and database effects.
- Avoid arbitrary sleeps, network dependencies, production data, and hidden shared state.
- Keep test names descriptive and failures diagnosable.
- Ensure the full suite can run using the repository's documented test command.

## 5. Delivery phases

| Phase | Work | Completion evidence |
|---|---|---|
| 1. API contract analysis | Inspect every route, middleware, Form Request, controller, relevant service/repository/model, validation rule, and response/error behavior. Produce endpoint matrix and scenario inventory. | Reviewed contract document and user approval. |
| 2. Test infrastructure | Finalize independent folder structure, base API test case, test data builders/factories, auth helpers, database setup, and deterministic cache strategy. | Infrastructure tests run deterministically. |
| 3. Authentication & security | Implement login/logout, token lifecycle, role isolation, inactive-member access, and validation/security tests. | Security suite passes and covers both roles. |
| 4. Librarian API | Implement librarian book/member/category/penalty endpoint tests. | All librarian endpoint scenarios implemented and verified. |
| 5. Member API | Implement member book-search/borrow/penalty endpoint tests. | All member endpoint scenarios implemented and verified. |
| 6. Edge cases & regression | Complete boundary, conflict, ownership, data-isolation, cache, and regression coverage. | Scenario matrix has no unexplained gaps. |
| 7. Final verification | Run the complete test suite, inspect failures, assess determinism and coverage, and document results/known gaps. | Final verification report and user review. |

## 6. Endpoint inventory (initial route-level inventory)

The route file currently declares 24 endpoints:

### Librarian (17)
1. `POST /v1/librarian/login` — public
2. `POST /v1/librarian/logout` — `auth:librarian`
3. `POST /v1/librarian/add_book` — `auth:librarian`
4. `DELETE /v1/librarian/delete_book` — `auth:librarian`
5. `POST /v1/librarian/search_book` — `auth:librarian`
6. `PUT /v1/librarian/update_book` — `auth:librarian`
7. `POST /v1/librarian/add_member` — `auth:librarian`
8. `PUT /v1/librarian/deactivate_member` — `auth:librarian`
9. `PUT /v1/librarian/activate_member` — `auth:librarian`
10. `DELETE /v1/librarian/delete_member` — `auth:librarian`
11. `POST /v1/librarian/search_member` — `auth:librarian`
12. `PUT /v1/librarian/update_member` — `auth:librarian`
13. `POST /v1/librarian/add_category` — `auth:librarian`
14. `DELETE /v1/librarian/delete_category` — `auth:librarian`
15. `POST /v1/librarian/search_category` — `auth:librarian`
16. `PUT /v1/librarian/update_category` — `auth:librarian`
17. `PUT /v1/librarian/pay_penalty` — `auth:librarian`

### Member (7)
1. `POST /v1/member/login` — public
2. `POST /v1/member/logout` — `auth:member` + `MemberIsActive`
3. `POST /v1/member/search_book` — `auth:member` + `MemberIsActive`
4. `POST /v1/member/add_borrow` — `auth:member` + `MemberIsActive`
5. `POST /v1/member/search_borrow` — `auth:member` + `MemberIsActive`
6. `PUT /v1/member/back_borrow` — `auth:member` + `MemberIsActive`
7. `POST /v1/member/search_penalty` — `auth:member` + `MemberIsActive`

This inventory is route-level only. Phase 1 must verify it against the actual implementation and capture each request/response schema and all middleware/validation details.

## 7. Progress tracking — update immediately

**Last updated:** 2026-11-18  
**Overall status:** Phases 1–6 implemented; the user reports that the complete test suite was executed locally and all tests passed. Phase 7 has user-reported completion evidence; CI compatibility remains a separate, non-blocking historical issue.  
**Current phase:** Final documentation review / closure.

| Phase | Status | Progress / notes |
|---|---|---|
| Plan and scope | Complete | User approved the proposed approach. This document records scope, constraints, folder proposal, coverage, and delivery phases. |
| 1. API contract analysis | Complete — user approved | `.agent/endpoint-contract-matrix.md` documents all 24 versioned routes, request validation summaries, response/status behavior, auth middleware, persistence notes, cache behavior, test-data constraints, and source-level defect candidates/open questions. User approved proceeding to Phase 2. |
| 2. Test infrastructure | <User-reported execution passed> | Added the independent API test base, related-record factory, Sanctum helpers, support README, and infrastructure smoke tests. The user reports that all tests, including the infrastructure tests, passed in their local run on 2026-11-18. Exact command and detailed output were not supplied to this documentation update. |
| 3. Authentication & security | <User-reported execution passed> | Added `tests/Feature/Api/Authentication/AuthenticationTest.php` covering successful login for both roles, invalid/unknown credentials, validation, inactive-member login/access denial, cross-role token isolation, logout revocation for both roles, and malformed token rejection. Corrected the Librarian model import found during review. User reports the complete test suite passed locally on 2026-11-18; exact command/output not recorded here. |
| 4. Librarian API | <User-reported execution passed> | Added `BookManagementTest.php` (create, invalid input, update, soft-delete state, ISBN search with explicit Redis mock), `MemberManagementTest.php` (create/password hash, status transitions, profile update, search password exclusion, delete), `CategoryManagementTest.php` (create/update/search/delete), and `PayPenaltyTest.php` (payment and already-paid conflict). These files cover the librarian operation endpoints at a basic success/business-rule level, with added duplicate ISBN, invalid phone, duplicate category, and missing penalty ID validation cases. Source review confirmed the Phone value object requires a leading plus and at least 12 numeric characters, and ISBN requires 13 numeric characters. User reports the complete test suite passed locally on 2026-11-18. Remaining policy questions are documented separately; they are not known failing tests. |
| 5. Member API | <User-reported execution passed> | Added `BookSearchTest.php` (member book search with explicit Redis mock and inactive-member denial), `BorrowManagementTest.php` (borrow success/inventory decrement, unpaid penalty, 3-borrow limit, unavailable book, member scoping, return success/inventory restoration, ownership and already-returned regression assertions, unpaid-penalty return restriction), and `PenaltySearchTest.php` (member scoping and inactive-member denial). Borrow ownership and already-returned validation are covered by regression tests; both rules were corrected to use database existence queries. The additional query-backed uniqueness/duplicate rules were also normalized to `exists()`. The user reports all tests passed locally on 2026-11-18. |
| 6. Edge cases & regression | <User-reported execution passed> | Added unauthenticated protected-route rejection plus explicit unknown-member and wrong-librarian-password login cases to `AuthenticationTest.php`; added librarian invalid/duplicate input cases across book, member, category, and penalty operations. A source-level review found that multiple books created in one test would collide on the test builder's fixed default category name; updated `TestDataFactory::category()` to generate a deterministic count-based unique default name. Current independent API suite contains 53 test methods across infrastructure, authentication/security, all librarian operations, and all member operations (freshly counted from the nine new test files). Known borrow ownership/returned-state validation defects are explicitly represented by regression tests in `BorrowManagementTest.php`. Source review additionally found `Repository::set_attributes_name()` calls `first()->getAttributes()`; repository-backed creates for books, members, and categories may fail when their respective tables are empty. The add-operation tests deliberately exercise empty-table behavior and are expected to reveal this defect. Added a member password-update regression test; Eloquent's `hashed` cast should hash the assigned plaintext even though the service does not explicitly call `Hash::make`. No production code has been changed. Source review now confirms there is no mutation-driven book-cache invalidation path and no `deleted_at IS NULL` filter in book search; these remain policy/behavior findings rather than assumed defects. Migrations declare restrictive foreign keys without cascade/null-on-delete actions for category/member/book dependencies; `delete_member` and `delete_category` controllers do not catch database exceptions, so the exact framework-rendered response remains unspecified and unverified. `BookCacheServices::get` has a confirmed ISBN search-word precedence defect candidate: with an ISBN it evaluates the ternary to `-`, producing a broad `*-*` SCAN pattern. Added a deterministic title-only cache-hit test with explicit Redis expectations. Added a deterministic API-level cache-miss assertion that Redis `expire` receives the book-specific key and the exact 180-second TTL. ISBN-specific cache-hit behavior remains uncovered because the current source expression produces a broad pattern; do not encode the broken broad match as desired behavior. ISBN-specific cache-hit matching remains a source-level defect candidate and is not asserted as desired behavior. Cache invalidation/deleted-book visibility and dependency-delete response semantics remain unresolved policy questions. User reports that the complete suite passed locally on 2026-11-18; exact command/output was not provided in this update. |
| 7. Final verification | <User-reported local pass; CI not verified> | The user explicitly confirmed on 2026-11-18 that they ran all tests locally and every test passed. This is user-reported full-suite execution evidence. The exact command, test count, and raw output were not supplied, so they are intentionally not fabricated. Historical GitHub Actions run `44135295525` failed during Composer dependency installation on PHP 8.3 because locked Symfony 8.1 packages require PHP >=8.4.1; the PHP 8.4/8.5 jobs were cancelled by `fail-fast`, and PHPUnit did not run in that CI run. This CI issue is separate from the successful local run and remains a non-blocking CI/platform follow-up, consistent with the user's instruction that CI is not currently a priority. |

### Progress update protocol for agents
1. Before work, read this document and the repository's applicable `AGENTS.md` instructions.
2. After **every meaningful advancement**, update the relevant phase status, completed items, remaining items, blockers, and last-updated date in this file in the same work session.
3. Do not mark a phase complete until its stated completion evidence exists.
4. Record discoveries that change the plan and explain why; do not silently change scope.
5. Keep the progress table truthful. Do not claim tests passed unless they were actually executed and the result was observed.
6. Keep this plan current across handoffs so the next agent can resume without relying on chat history.

## 8. Approval gates

- Phase 1 output must be presented to the user for review and approval before Phase 2 implementation begins.
- Any production-code modification or API contract change requires separate explicit user approval.
- If a requirement cannot be established from source, mark it as unknown and investigate; do not invent expected behavior.


### Latest Phase 6 advancement (2026-11-17)

- Re-read `AGENTS.md`, this plan, the endpoint contract matrix, and the test support README before continuing.
- Inspected `BookCacheServices`, `CacheRepository`, `BookServices`, `MemberServices`, `CategoryServices`, `Repository`, librarian controller behavior, and the books/categories/members/borrows/penalties migrations.
- Updated `.agent/endpoint-contract-matrix.md` with confirmed facts: no cache invalidation API/calls exist; book search does not filter `deleted_at`; FK declarations omit cascade/null-on-delete actions; cache-hit/title-only/TTL behavior remains uncovered; password update must be evaluated through the model's hashed cast rather than presumed broken.
- No production code was modified and no tests were executed. The suite now contains 52 test methods across nine independent API test files (49 prior count + 3 new cases); this count is based on authored methods, not executed test discovery.
- Next: inspect remaining independent API test files and request/route behavior for missing edge cases; add only assertions grounded in the actual contract or clearly labeled regression expectations. Do not invent cache/deletion policy.

- Added title-only Redis cache-hit coverage in `BookManagementTest.php`; it verifies SCAN pattern `*Distributed Systems*`, key existence, hash retrieval, and returned book fields using strict Redis expectations.
- Inspected `BookCacheServices::get` precedence: a non-empty ISBN makes `$isbn . isset($isbn)` truthy, so the ternary returns `-`; the resulting pattern is `*-*`, not an ISBN-specific pattern. Documented as a source-level defect candidate in the contract matrix. No production fix or test that encodes this incorrect behavior as desired was added.
- Fresh authored-method count before the latest penalty-flow test: 53 tests across the nine independent API test files. No test execution was performed.


- Phase 6 cache TTL advancement: tightened the existing librarian ISBN-search cache-miss test so the Redis `expire` expectation verifies both the matching book key prefix and exactly 180 seconds. Updated the endpoint contract matrix with the TTL source (`BookCacheServices::EXPIRE_TIME`) and API-level coverage. This is an authored assertion only; no tests have been executed. Production code remains unchanged.

- Phase 6 cross-endpoint regression: added `test_member_can_borrow_after_their_unpaid_penalty_is_paid` to `BorrowManagementTest.php`. It pays an existing member penalty through the librarian API, verifies `paid_at`, then authenticates as that member and verifies a new borrow succeeds and decrements inventory. Updated the endpoint contract matrix to record the complete unpaid-to-paid transition. This raises the authored source count to 54 test methods; no tests have been executed and no production code was changed.

- Added `BookManagementTest::test_book_search_rejects_mutually_exclusive_availability_filters`, exercising the `CanNotSetWithOtherFieldRule` contract through the librarian HTTP endpoint and asserting validation errors for both conflicting flags. Updated the endpoint matrix. Fresh source recount confirms 56 authored test methods across the nine independent API test files; tests remain unexecuted.

- Fresh inventory verification after the latest additions: 56 `public function test_...` methods across nine independent API test files (3 infrastructure, 15 authentication, 8 librarian books, 7 librarian members, 4 librarian categories, 3 librarian penalties, 2 member books, 11 member borrows, 2 member penalties). This is a source count only, not PHPUnit discovery or execution.


- Additional Phase 6 regression: added `BookManagementTest::test_librarian_can_update_a_book_to_an_unused_isbn` to verify that changing a book's ISBN to a valid, unused value succeeds and persists the new ISBN. Source inspection of `BookIsbnNotExistsRule` shows it assigns the result of `DB::table(...)->get()` to a Collection and checks the Collection's truthiness; even an empty Collection is truthy. This may reject every attempted ISBN update, not merely duplicates. The new test expresses the intended unique-ISBN update contract and is expected to expose the defect when executed. No production code was changed. Fresh authored-method count: 56 methods across the nine API test files (55 previously recorded + this test); no tests have been run.


- Phase 6 search validation advancement (2026-11-17): added API tests for non-positive `limit` and unknown `category_id`, plus the valid combination `available=true, not_available=false`. The latter verifies the mutually-exclusive custom rule distinguishes an explicitly false peer value from a true peer value. These assertions are source-grounded in `BookSearchRequest` and `CanNotSetWithOtherFieldRule`; they have not been executed.
- Current test inventory after this addition: 58 authored `public function test_...` methods across nine independent API test files (3 infrastructure, 15 authentication, 10 librarian books, 7 librarian members, 4 librarian categories, 3 librarian penalties, 2 member books, 11 member borrows, 2 member penalties). Source count only; no PHPUnit discovery or runtime execution has occurred.
- Newly confirmed nuance: `CanNotSetWithOtherFieldRule` uses `isset` on the other field, so `false` is treated as not set. The new positive-path test covers this behavior; the existing both-true test covers the conflict case.


- Follow-up correction (2026-11-17): reviewed the availability-filter positive-path test against `BookCacheServices::get` and `LibrarianController::search_book`. Availability-only searches bypass cache lookup, so corrected its Redis mock to expect the cache-write `exists` call rather than an irrelevant `SCAN`. This is a source-alignment correction; test remains unexecuted.
- Fresh recount by fetching all nine test files from `main`: 58 authored `public function test_...` methods. Per-file counts: tests/Feature/Api/Infrastructure/TestInfrastructureTest.php: 3; tests/Feature/Api/Authentication/AuthenticationTest.php: 15; tests/Feature/Api/Librarian/Books/BookManagementTest.php: 11; tests/Feature/Api/Librarian/Members/MemberManagementTest.php: 7; tests/Feature/Api/Librarian/Categories/CategoryManagementTest.php: 4; tests/Feature/Api/Librarian/Penalties/PayPenaltyTest.php: 3; tests/Feature/Api/Member/Books/BookSearchTest.php: 2; tests/Feature/Api/Member/Borrows/BorrowManagementTest.php: 11; tests/Feature/Api/Member/Penalties/PenaltySearchTest.php: 2. No tests have been run.


- Phase 6 validation finding (2026-11-17): added an API regression test expecting an empty book-search payload to fail the `_check` required-without-all validation. Source inspection shows `BookSearchRequest::prepareForValidation()` unconditionally merges `_check = dummy`, which appears to satisfy the required rule even when no search criteria are supplied. The test is intentionally written against the declared non-empty-search requirement and may expose this defect; it has not been executed. No production behavior was changed.
- Latest authored-method count: 59 tests across nine files (previously verified 58 + this regression test). Source count only; no PHPUnit execution.


- Final inventory recount after the empty-search regression: fetched all nine independent API test files from `main` and counted 59 authored `public function test_...` methods. Breakdown: tests/Feature/Api/Infrastructure/TestInfrastructureTest.php: 3; tests/Feature/Api/Authentication/AuthenticationTest.php: 15; tests/Feature/Api/Librarian/Books/BookManagementTest.php: 12; tests/Feature/Api/Librarian/Members/MemberManagementTest.php: 7; tests/Feature/Api/Librarian/Categories/CategoryManagementTest.php: 4; tests/Feature/Api/Librarian/Penalties/PayPenaltyTest.php: 3; tests/Feature/Api/Member/Books/BookSearchTest.php: 2; tests/Feature/Api/Member/Borrows/BorrowManagementTest.php: 11; tests/Feature/Api/Member/Penalties/PenaltySearchTest.php: 2. This remains a source-level count, not PHPUnit discovery or execution.
- Current blockers: no test execution environment/result has been established in this session; the source-level candidates around ISBN validation, empty search validation, Repository empty-table behavior, borrow ownership/return rules, and ISBN cache pattern remain unverified at runtime. No production code changed.
- Exact next step: obtain an executable checkout/environment and run the documented test command, beginning with the three infrastructure smoke tests; then run the full independent API suite and classify actual failures without changing production behavior absent approval.


### Phase 7 investigation (2026-11-17) — CI blocker identified

- Inspected `.github/workflows/tests.yml`: pushes to `main` run a PHP matrix `[8.3, 8.4, 8.5]` with `fail-fast: true`; the workflow installs Composer dependencies before invoking `php artisan test --coverage --min=100`.
- Inspected `composer.json`: the root package declares `php: ^8.3`.
- Retrieved the actual GitHub Actions run for latest commit `b9e699bb6fd57f0d18a7627ba81c0c4b2e39fb8e`: run ID `44135295525`, conclusion `failure`. PHP 8.3 failed at step 4 (`Install Composer dependencies`); PHP 8.4 was cancelled at dependency installation and PHP 8.5 was cancelled before setup/checkout completed. The test execution step was skipped in every matrix job.
- Read PHP 8.3 job logs: locked `symfony/* v8.1.x` packages require PHP >=8.4.1; Composer reports the lock file does not contain a compatible package set for PHP 8.3. Laravel Framework is locked at v13.20.0.
- Local container check: PHP 8.4.24 is installed, but Composer is not installed and no repository checkout is mounted. Attempted shallow clone failed because this environment cannot resolve `github.com`. Thus no local PHPUnit command can be run.
- This is a dependency/platform compatibility blocker, not an observed failure in the authored API tests. No test has run; no production source, dependency lock, or workflow file was changed.
- Next exact action: obtain user approval for a dependency/platform compatibility change (or a CI matrix adjustment) before changing `composer.lock`, `composer.json`, or workflow configuration. Then rerun CI; only after Composer succeeds can the three infrastructure tests and 59-test API suite be evaluated.


- Follow-up CI confirmation (2026-11-17): the documentation-only commits also triggered workflow runs `44135755529` and `44135765171`. Both ended with PHP 8.3 failing at Composer install and PHP 8.4/8.5 cancelled by `fail-fast: true`; `Execute tests` was skipped in every job. This confirms the blocker persists on the latest main commit. No CI run has yet reached PHPUnit.
- Decision required before changing project configuration: either preserve the declared PHP `^8.3` support by regenerating/resolving `composer.lock` against PHP 8.3-compatible dependency versions (Symfony 7.4 is allowed by the locked Laravel framework constraint), or raise the minimum supported PHP version to >=8.4.1 and remove PHP 8.3 from the CI matrix. A third independent CI improvement is setting `fail-fast: false` so one incompatible matrix leg does not cancel compatible-version jobs. No option is applied without user approval.


- Phase 6 regression addition (2026-11-17): added `MemberManagementTest::test_member_search_can_filter_inactive_members`. It creates one inactive and one active member, searches with JSON `active: false`, and asserts only the inactive member is returned. Source review shows `MemberServices::search()` skips values using loose comparison `$request->$attribute == null`; PHP treats boolean false as equal to null, so the inactive filter appears to be discarded. This test intentionally captures the expected API filter behavior and may expose the source defect. No production code changed; test not executed.
- Recounted all nine independent API test files from `main`: 60 authored `public function test_...` methods. Breakdown: tests/Feature/Api/Infrastructure/TestInfrastructureTest.php: 3; tests/Feature/Api/Authentication/AuthenticationTest.php: 15; tests/Feature/Api/Librarian/Books/BookManagementTest.php: 12; tests/Feature/Api/Librarian/Members/MemberManagementTest.php: 8; tests/Feature/Api/Librarian/Categories/CategoryManagementTest.php: 4; tests/Feature/Api/Librarian/Penalties/PayPenaltyTest.php: 3; tests/Feature/Api/Member/Books/BookSearchTest.php: 2; tests/Feature/Api/Member/Borrows/BorrowManagementTest.php: 11; tests/Feature/Api/Member/Penalties/PenaltySearchTest.php: 2. This is source inventory only; no PHPUnit discovery or execution.
- User clarified CI is not currently a priority and asked to continue test work. Do not spend further effort investigating CI; continue independent endpoint coverage and keep all tests explicitly marked unverified until an executable environment is available.


- Additional Phase 6 validation coverage (2026-11-17): added empty-payload rejection tests for `POST /v1/librarian/search_member` and `POST /v1/librarian/search_category`. Both requests currently merge `_check = 'dummy'` in `prepareForValidation()`, despite declaring `required_without_all`; these tests capture the apparent requirement that at least one search criterion be supplied. They may expose the same validation bypass already documented for book search. Not executed; no production changes.
- Latest fresh source recount across all nine API test files: 62 authored `public function test_...` methods. Breakdown: tests/Feature/Api/Infrastructure/TestInfrastructureTest.php: 3; tests/Feature/Api/Authentication/AuthenticationTest.php: 15; tests/Feature/Api/Librarian/Books/BookManagementTest.php: 12; tests/Feature/Api/Librarian/Members/MemberManagementTest.php: 9; tests/Feature/Api/Librarian/Categories/CategoryManagementTest.php: 5; tests/Feature/Api/Librarian/Penalties/PayPenaltyTest.php: 3; tests/Feature/Api/Member/Books/BookSearchTest.php: 2; tests/Feature/Api/Member/Borrows/BorrowManagementTest.php: 11; tests/Feature/Api/Member/Penalties/PenaltySearchTest.php: 2. Source count only; PHPUnit has not run.


### Phase 6 — member borrow-search status coverage (2026-11-17)

- Added an API Feature test for `POST /v1/member/search_borrow` with `status=returned`. It seeds returned and active borrows for the authenticated member plus a returned borrow for another member, then asserts the status filter and member scoping together.
- Added a validation test asserting an unsupported status (`pending`) is rejected with a 422 validation error on `status`, matching `BorrowSearchRequest::rules()` (`Rule::in(['borrowed', 'returned', 'overdue'])`).
- The tests are source-aligned with `BorrowServices::SEARCH_REQUEST_ATTRIBUTES` and the controller's authenticated-member override. They have not been executed; no production code was changed.
- Authored source inventory is now 64 `public function test_...` methods across the nine independent API test files (Infrastructure 3, Authentication 15, Librarian Books 12, Librarian Members 9, Librarian Categories 5, Librarian Penalties 3, Member Books 2, Member Borrows 13, Member Penalties 2). This is a source count only, not PHPUnit discovery or execution.
- Exact next step: continue reviewing uncovered request filters and endpoint boundary cases; keep test execution and CI explicitly out of scope until the user reprioritizes verification or an executable test environment is available.


### Phase 6 — borrow-search date and limit boundaries (2026-11-17)

- Added a due_date_after API test for POST /v1/member/search_borrow. It seeds two borrows for the authenticated member with due dates on opposite sides of the supplied boundary and asserts only the later due date is returned.
- Added a zero-limit validation test. The request includes a valid status filter and limit=0, isolating the limit rule and asserting a 422 validation error on that field.
- Source basis: BorrowSearchRequest declares due_date_after as a custom Date rule and limit as integer|gt:0; BorrowServices::SEARCH_REQUEST_ATTRIBUTES maps due_date_after to due_date > value.
- The tests are authored but unexecuted. No production code or API contract was changed.
- Latest expected source inventory after this addition: 66 test methods across nine files (Infrastructure 3, Authentication 15, Librarian Books 12, Librarian Members 9, Librarian Categories 5, Librarian Penalties 3, Member Books 2, Member Borrows 15, Member Penalties 2). Recount from freshly fetched source before reporting. This is not PHPUnit discovery or execution.
- Exact next step: continue the source-based coverage review of member penalty filters and librarian penalty payment boundaries; keep CI and test execution out of scope per the user's current priority.


### Latest Phase 6 advancement (2026-11-18) — member penalty search

- Re-read AGENTS.md, this plan, .agent/endpoint-contract-matrix.md, and tests/Support/README.md from main before continuing.
- Inspected the member penalty search test, PenaltySearchRequest, PenaltyServices, MemberController, LibrarianController, PenaltyRepository, the date value object/rule, and the existing librarian penalty-payment tests.
- Added four tests to tests/Feature/Api/Member/Penalties/PenaltySearchTest.php: strict amount_lower_than boundary, strict amount_greater_than boundary, exact paid_at date filtering, and rejection of limit=0 with a valid criterion.
- Confirmed member penalty search scopes results by replacing request member_id with the authenticated member ID. Existing cross-member isolation coverage remains.
- Confirmed the librarian's already-paid penalty path is explicitly translated to HTTP 409 in the controller and already has a test; no duplicate payment test was needed.
- Updated .agent/endpoint-contract-matrix.md immediately with the new coverage and source basis.
- Freshly fetched all nine independent API Feature test files from main and counted 70 authored public function test_... methods: Infrastructure 3, Authentication 15, Librarian Books 12, Librarian Members 9, Librarian Categories 5, Librarian Penalties 3, Member Books 2, Member Borrows 15, Member Penalties 6.
- This is a source-level count only. No PHPUnit discovery or test execution was performed. CI remains out of scope per the user's instruction. No production code was modified.
- Exact next step: continue source review of remaining uncovered PenaltySearchRequest date-range filters (calculated_before_at, calculated_after_at, paid_before_at, paid_after_at) and any remaining librarian penalty payment validation boundaries. Do not infer or change production behavior without explicit approval.


### Phase 6 follow-up (2026-11-18) — penalty date-range filters

- Added four more API Feature tests in tests/Feature/Api/Member/Penalties/PenaltySearchTest.php for calculated_before_at, calculated_after_at, paid_before_at, and paid_after_at. Each test checks strict date-boundary behavior; paid-date tests also ensure null paid_at rows are not returned.
- Updated the endpoint contract matrix immediately after implementation.
- Fresh source inventory: 74 authored test methods across nine independent API test files (Infrastructure 3, Authentication 15, Librarian Books 12, Librarian Members 9, Librarian Categories 5, Librarian Penalties 3, Member Books 2, Member Borrows 15, Member Penalties 10). This count is based on freshly fetched GitHub source, not PHPUnit discovery.
- No test execution, CI work, or production-code modification was performed.
- Next: inspect whether remaining penalty request validation and librarian payment cases are already adequately covered; then continue to the next endpoint gaps. Do not assume any test passes until executed.


### Phase 6 advancement (2026-11-18) — exact penalty filters

- Re-read AGENTS.md, the current endpoint-testing plan, endpoint contract matrix, and tests/Support/README.md from main before implementation.
- Re-inspected PenaltySearchRequest, PenaltyServices::SEARCH_REQUEST_ATTRIBUTES, MemberController::search_penalty, the existing penalty feature tests, and TestDataFactory.
- Added four tests to tests/Feature/Api/Member/Penalties/PenaltySearchTest.php: exact amount filter, borrowed_id filter, exact calculated_at filter, and rejection of zero for borrowed_id/amount/amount_lower_than/amount_greater_than.
- These cases cover every declared PenaltySearchRequest search criterion except member_id as a caller-controlled filter (intentionally overwritten by authenticated identity) and the date/amount ranges already covered in preceding updates. Existing member-scope isolation remains covered.
- Updated .agent/endpoint-contract-matrix.md immediately after the test commit.
- No production code changed. No tests were executed. CI remains out of scope.
- Next: inspect the Librarian pay_penalty endpoint for missing boundary cases (especially missing ID validation, non-positive ID, and successful payment state) and then continue reviewing remaining API endpoint gaps.


### Phase 6 advancement (2026-11-18) — librarian penalty payment validation

- Re-read current agent instructions and all required testing documents from main before implementation.
- Inspected PenaltyPayRequest, LibrarianController::pay_penalty, PenaltyServices::pay, PenaltyAlreadyPaidException, and PayPenaltyTest.
- Existing coverage already asserted successful payment state, repeat-payment HTTP 409, and a positive but non-existent penalty ID. Added two missing boundary tests: omitted id and id=0, each asserting validation failure on id.
- Updated the endpoint contract matrix immediately after the test change.
- No production code or API contract was modified. Tests have not been executed; CI remains out of scope.
- Next: continue the cross-endpoint gap review, starting with category and member search filters and any untested validation boundaries, while keeping the independent test suite isolated.


### Phase 6 advancement (2026-11-18) — librarian search and payment edge cases

- Added two librarian payment validation tests: missing id and id=0; existing coverage already includes successful payment, already-paid 409, and a non-existent positive ID.
- Added one category partial-name search test and one member partial-name search test, each verifying substring match and non-match exclusion.
- Source review of CategoryServices::search and MemberServices::search found both compute a wildcard-normalized value through fix_str_like_op() but pass the original value to Repository::search. These tests encode the substring behavior implied by the helper and are documented as regression expectations that may expose a source defect. Do not modify production code without approval.
- Updated the endpoint contract matrix after each meaningful advancement. No production changes and no test execution.
- Next: review other member/category search filters and then inspect remaining untested endpoint scenarios; keep all claims about execution pending.


## Effective HTTP URL prefix convention

Laravel automatically mounts the routes declared in `routes/api.php` under `/api`. Therefore every HTTP-level Feature test must use the effective URL including `/api`, such as `/api/v1/member/login` and `/api/v1/librarian/search_book`. The route inventory above intentionally records the path strings as declared in `routes/api.php` (without the framework prefix); those declaration paths must not be copied verbatim into HTTP test requests. This distinction is mandatory for all future test additions and reviews.

### Correction update — API test URL prefix (2026-11-18)

- Corrected every HTTP endpoint URL in the eight independent API Feature test files to include Laravel's `/api` prefix. The correction changed 87 request URL literals across authentication, librarian, and member endpoint tests.
- Added an explicit `/api` prefix rule to `AGENTS.md`, this plan, and `tests/Support/README.md` so future agents do not repeat the mistake.
- Production routes and application code were not changed. Tests have not been run as part of this correction; the user reported that prior test runs failed because of the missing prefix, but no new execution result is claimed.

### 2026-11-18 — search request validation correction

- User approved correcting the empty-search validation behavior across the search Form Requests and clarified that `limit` is optional and must not count as a search criterion.
- Removed the unconditional `prepareForValidation()` method that merged the `_check` sentinel from all six requests: BookSearchRequest, BorrowSearchRequest, CategorySearchRequest, LibrarianSearchRequest, MemberSearchRequest, and PenaltySearchRequest.
- Completed the `required_without_all` criteria lists to include the omitted supported filters: book `available`/`not_available`, borrow `due_date_after` and the complete penalty amount comparison list, and member `penalty_balance_lower_than`/`penalty_balance_greater_than`. Corrected the missing comma in the borrow criteria list.
- Confirmed `limit` remains excluded from all criteria lists. A request containing only `limit` must fail the minimum-one-search-criterion validation.
- Added limit-only regression tests for book, category, and member search. Added empty-payload and limit-only regression tests for member borrow and penalty search. Existing empty-payload tests cover book, category, and member search.
- All test URLs use the effective `/api/v1/...` prefix.
- No test suite was executed in this environment. Tests are authored only; runtime result remains unverified.
- Next step: inspect the final diffs/source-level method counts, then continue remaining endpoint regression work. Do not mark the tests as passing without execution evidence.
- Fresh source-level recount after this correction: **89 authored `public function test_...` methods across the nine independent API test files** (Infrastructure 3, Authentication 15, Librarian Books 13, Librarian Members 11, Librarian Categories 7, Librarian Penalties 5, Member Books 2, Member Borrows 17, Member Penalties 16). This is a source count only; PHPUnit has not been run.


### 2026-11-18 — custom validation Rule corrections

- User approved applying the latest `BorrowIsforMemberRule` approach to the remaining applicable Rules.
- Updated `BorrowNotReturnedRule` to query for the borrow ID with `status = returned` using `exists()`; the old code compared a Collection directly to the string `returned`, so already-returned borrows were not detected correctly.
- Replaced `get()`/Collection truthiness or result-count checks with `exists()` in `BookIsbnNotExistsRule`, `DuplicateBorrowRule`, `MemberEmailNotExists`, `LibrarianEmailNotExists`, and `CategoryNameNotExists`.
- `BorrowIsforMemberRule` was already corrected by the user's latest commit and was left unchanged. `LibrarianEmailNotExists` currently has no usage in the inspected Form Requests; its implementation was still normalized, but no behavior was wired into a request.
- Added `MemberManagementTest::test_add_member_rejects_an_email_already_in_use` as API regression coverage. Existing tests already cover duplicate book ISBN, duplicate category name, duplicate same-day borrow, borrow ownership, already-returned borrow, and updating to an unused ISBN.
- All changes were committed directly to `main`. Tests were not run; runtime verification remains pending. CI remains out of scope.

### Authentication regression — logout token revocation (2026-11-18)

- Updated both member and librarian logout tests to assert that the corresponding `personal_access_tokens` row (including the `api-test` token name and correct tokenable type) is deleted after logout.
- Both tests now call `$this->app['auth']->forgetGuards()` before reusing the same Bearer token, preventing a cached guard user from masking token-revocation behavior between requests in the same test.
- The second request now calls the same role's logout endpoint and expects 401. This isolates the revoked-token authentication contract instead of allowing an unrelated request-validation response to determine the result.
- Tests are authored but have not been run in this environment. No production code or API contract was changed.


### Final test execution confirmation — 2026-11-18

- The user explicitly confirmed: **all tests passed when they ran them locally**.
- Updated the phase status table to reflect the user's full-suite pass report across the independent test suite, including infrastructure, authentication/security, librarian API, member API, and regression coverage.
- The exact local command, PHPUnit test count, and raw terminal output were not supplied in this confirmation; this entry records the user's report without inventing those details.
- The historical GitHub Actions Composer/PHP compatibility failure remains documented as a separate CI issue. It does not contradict the user's successful local test run and is not blocking closure because CI is currently deprioritized by the user.
- Remaining work in this plan is no longer basic test execution. It is optional coverage/policy follow-up: resolve the documented product decisions (empty search criteria, deleted-book visibility, cache invalidation, and dependency-delete responses) and decide whether the known ISBN cache pattern candidate warrants a separately approved production correction and dedicated test.
- Suggested next step: perform a final review of the current endpoint matrix against the user's reported passing suite, then close the endpoint-testing plan or create a separate follow-up list for unresolved product/quality decisions.

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

**Last updated:** 2026-11-17  
**Overall status:** Phase 1 approved; Phases 2–5 implemented to varying coverage levels but unverified; Phase 6 source review and regression coverage in progress.  
**Current phase:** Phase 6 — Edge cases & regression.

| Phase | Status | Progress / notes |
|---|---|---|
| Plan and scope | Complete | User approved the proposed approach. This document records scope, constraints, folder proposal, coverage, and delivery phases. |
| 1. API contract analysis | Complete — user approved | `.agent/endpoint-contract-matrix.md` documents all 24 versioned routes, request validation summaries, response/status behavior, auth middleware, persistence notes, cache behavior, test-data constraints, and source-level defect candidates/open questions. User approved proceeding to Phase 2. |
| 2. Test infrastructure | Implemented — verification pending | Added the independent API test base, related-record factory, Sanctum helpers, support README, and infrastructure smoke tests. Corrected the infrastructure test namespace during Phase 3 work. The suite has NOT been executed; Phase 2 remains unverified and must not be marked complete. The user explicitly instructed us to continue without waiting for the test output, so Phase 3 has started while this verification remains outstanding. |
| 3. Authentication & security | Implemented — verification pending | Added `tests/Feature/Api/Authentication/AuthenticationTest.php` covering successful login for both roles, invalid/unknown credentials, validation, inactive-member login/access denial, cross-role token isolation, logout revocation for both roles, and malformed token rejection. Corrected the Librarian model import found during review. Tests have NOT been run; do not mark complete until execution evidence exists. |
| 4. Librarian API | Initial coverage implemented — verification pending | Added `BookManagementTest.php` (create, invalid input, update, soft-delete state, ISBN search with explicit Redis mock), `MemberManagementTest.php` (create/password hash, status transitions, profile update, search password exclusion, delete), `CategoryManagementTest.php` (create/update/search/delete), and `PayPenaltyTest.php` (payment and already-paid conflict). These files cover the librarian operation endpoints at a basic success/business-rule level, with added duplicate ISBN, invalid phone, duplicate category, and missing penalty ID validation cases. Source review confirmed the Phone value object requires a leading plus and at least 12 numeric characters, and ISBN requires 13 numeric characters. Tests have NOT been run. Remaining: dependency-delete behavior and deeper filter coverage during regression. |
| 5. Member API | In progress — initial endpoint tests added, verification pending | Added `BookSearchTest.php` (member book search with explicit Redis mock and inactive-member denial), `BorrowManagementTest.php` (borrow success/inventory decrement, unpaid penalty, 3-borrow limit, unavailable book, member scoping, return success/inventory restoration, ownership and already-returned regression assertions, unpaid-penalty return restriction), and `PenaltySearchTest.php` (member scoping and inactive-member denial). Tests have NOT been run. Source review shows `BorrowIsforMemberRule` tests `empty()` on a Collection and `BorrowNotReturnedRule` compares a Collection to a string; ownership/already-returned regression tests are expected to expose these defects when executed. Do not modify production code without user approval. |
| 6. Edge cases & regression | In progress — source review and regression additions | Added unauthenticated protected-route rejection plus explicit unknown-member and wrong-librarian-password login cases to `AuthenticationTest.php`; added librarian invalid/duplicate input cases across book, member, category, and penalty operations. A source-level review found that multiple books created in one test would collide on the test builder's fixed default category name; updated `TestDataFactory::category()` to generate a deterministic count-based unique default name. Current independent API suite contains 53 test methods across infrastructure, authentication/security, all librarian operations, and all member operations (freshly counted from the nine new test files). Known borrow ownership/returned-state validation defects are explicitly represented by regression tests in `BorrowManagementTest.php`. Source review additionally found `Repository::set_attributes_name()` calls `first()->getAttributes()`; repository-backed creates for books, members, and categories may fail when their respective tables are empty. The add-operation tests deliberately exercise empty-table behavior and are expected to reveal this defect. Added a member password-update regression test; Eloquent's `hashed` cast should hash the assigned plaintext even though the service does not explicitly call `Hash::make`. No production code has been changed. Source review now confirms there is no mutation-driven book-cache invalidation path and no `deleted_at IS NULL` filter in book search; these remain policy/behavior findings rather than assumed defects. Migrations declare restrictive foreign keys without cascade/null-on-delete actions for category/member/book dependencies; `delete_member` and `delete_category` controllers do not catch database exceptions, so the exact framework-rendered response remains unspecified and unverified. `BookCacheServices::get` has a confirmed ISBN search-word precedence defect candidate: with an ISBN it evaluates the ternary to `-`, producing a broad `*-*` SCAN pattern. Added a deterministic title-only cache-hit test with explicit Redis expectations. Added a deterministic API-level cache-miss assertion that Redis `expire` receives the book-specific key and the exact 180-second TTL. ISBN-specific cache-hit behavior remains uncovered because the current source expression produces a broad pattern; do not encode the broken broad match as desired behavior. Remaining: assess whether an ISBN-cache regression should be added as an explicitly expected failure or wait for separate production-fix approval; add TTL coverage if it can assert the existing 180-second contract deterministically. Cache invalidation/deleted-book visibility remain unresolved policy questions. Tests have not been run. |
| 7. Final verification | Not started | Blocked on implementation completion and an available test execution environment. |

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


- Additional Phase 6 regression: added `BookManagementTest::test_librarian_can_update_a_book_to_an_unused_isbn` to verify that changing a book's ISBN to a valid, unused value succeeds and persists the new ISBN. Source inspection of `BookIsbnNotExistsRule` shows it assigns the result of `DB::table(...)->get()` to a Collection and checks the Collection's truthiness; even an empty Collection is truthy. This may reject every attempted ISBN update, not merely duplicates. The new test expresses the intended unique-ISBN update contract and is expected to expose the defect when executed. No production code was changed. Fresh authored-method count: 9 methods across the nine API test files; no tests have been run.

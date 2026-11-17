# API Endpoint Contract Matrix

**Status:** Phase 1 deliverable — source review completed and user approved proceeding to Phase 2.  
**Scope:** The 24 versioned routes in `routes/api.php`. The additional `GET /test` route is an unversioned development/test route and is not counted as a public v1 endpoint.

## 1. Authentication and common behavior

- Librarian routes use the `auth:librarian` guard, configured as Sanctum with the `librarians` provider.
- Member routes use `auth:member`, configured as Sanctum with the `members` provider, plus `MemberIsActive` on every protected member route.
- Login endpoints are public.
- `MemberIsActive` returns HTTP 403 with `{"message":"member is de active"}` when the authenticated member is inactive.
- Form Requests inspected all return `authorize(): true`; access control is therefore supplied by route middleware, not Form Request authorization.
- Repository search defaults to a maximum of 50 records when no `limit` is supplied.
- Models use `$timestamps = false`; migrations do not define Eloquent timestamps for domain tables.
- The test environment in `phpunit.xml` uses SQLite in-memory and `CACHE_STORE=array`, but the book-cache repository directly calls the Redis facade. Array cache configuration alone does not isolate those Redis calls.

## 2. Librarian endpoints

| # | Method and path | Request / validation contract | Success response | Main behavior / scenarios |
|---|---|---|---|---|
| L1 | POST `/v1/librarian/login` | `LibrarianLoginRequest`: required valid email; required password string, min 8 | 200: `massage`, `librarian`, `token` | Unknown email or wrong password returns 401 with `massage`. Successful login issues Sanctum token. Note response key spelling `massage` is current behavior. |
| L2 | POST `/v1/librarian/logout` | No Form Request; `auth:librarian` | 200: `message: successful logout` | Deletes current access token. Verify token is rejected after logout. |
| L3 | POST `/v1/librarian/add_book` | `BookRequest`: required title, author, ISBN (custom ISBN + unique), published (custom Date), publisher, category_id (positive integer, existing category), total_copies (positive integer), description (string min 10), location | 201: `message: book created` | Creates book; initial available_copies is set to total_copies. |
| L4 | DELETE `/v1/librarian/delete_book` | `BookDeleteRequest`: required valid ISBN, existing book | 200: `message: book deleted` | Sets `deleted_at` (soft-delete-like behavior), rather than physically deleting. Verify how subsequent search treats deleted records. |
| L5 | POST `/v1/librarian/search_book` | `BookSearchRequest`: nullable title, author, ISBN, published, published_before, published_after, publisher, category_id, total_copies, total_copies_lower_than, total_copies_greater_than, available_copies, available_copies_lower_than, available_copies_greater_than, description, location, available, not_available, limit. ISBN/date/numeric/string/bool constraints apply. | 200: `books: []` or matching book objects | Supports combined filters and cache path. `available` and `not_available` are mutually exclusive by custom rule; the independent API suite now asserts that submitting both returns validation errors on both fields. |
| L6 | PUT `/v1/librarian/update_book` | `BookUpdateRequest`: required target_isbn (valid existing ISBN); nullable title, author, ISBN (custom uniqueness rule), published (custom Date), publisher, category_id (positive existing), total_copies (positive), description (string min 10), location | 200: `message: book updated` | Updates only non-null attributes. No explicit check in service that total_copies remains >= currently borrowed copies; verify expected domain policy before writing assertions. |
| L7 | POST `/v1/librarian/add_member` | `MemberRequest`: required name min 2, valid email + custom uniqueness rule, password min 8, custom Phone, address | 201: `message: member created` | Creates active member; membership_date is current date; penalty_balance starts at 0; password is hashed. |
| L8 | PUT `/v1/librarian/deactivate_member` | `MemberDeactivateRequest`: required valid email, existing member | 200: `message: member deactivated` | Sets active=false. Subsequent protected member routes should be blocked by middleware. |
| L9 | PUT `/v1/librarian/activate_member` | `MemberActivateRequest`: required valid email, existing member | 200: `message: member activated` | Sets active=true. |
| L10 | DELETE `/v1/librarian/delete_member` | `MemberDeleteRequest`: required valid email, existing member | 200: `message: member deleted` | Repository delete is physical. Foreign-key dependencies (borrows/penalties) have no `onDelete` cascade/null action in the migrations; default restrictive FK behavior is expected, but exact HTTP/error behavior still needs an integration test. |
| L11 | POST `/v1/librarian/search_member` | `MemberSearchRequest`: nullable name, email, phone, address, membership_date, membership_date_before, membership_date_after, active, penalty_balance, penalty_balance_lower_than, penalty_balance_greater_than, limit | 200: `members: []` or member objects | Controller removes password from each serialized result. Verify filter combinations and password absence. |
| L12 | PUT `/v1/librarian/update_member` | `MemberUpdateRequest`: required target_email (valid email); nullable name min 2, email, password min 8, phone, address, active boolean | 200: `message: member updated` | Updates only non-null fields. Password update path appears to store the provided value without hashing in this service; verify with a focused test and treat as a defect candidate, not an intended contract. |
| L13 | POST `/v1/librarian/add_category` | `CategoryRequest`: required name string + custom uniqueness rule; required description string min 10 | 201: `message: category created` | Creates category. |
| L14 | DELETE `/v1/librarian/delete_category` | `CategoryDeleteRequest`: required positive integer id, existing category | 200: `message: category deleted` | Physically deletes category. Book foreign-key dependencies need explicit behavior coverage. |
| L15 | POST `/v1/librarian/search_category` | `CategorySearchRequest`: nullable name, description (string min 10 max 40), limit | 200: `categories: []` or category objects | Search service builds LIKE filters; source currently computes a wildcard-normalized value but passes the original request value to the repository. |
| L16 | PUT `/v1/librarian/update_category` | `CategoryUpdateRequest`: required positive existing target_id; nullable name string, description string min 10 | 200: `message: category updated` | Updates non-null fields. Duplicate-name behavior is ultimately constrained by database unique index; request has no explicit uniqueness rule. |
| L17 | PUT `/v1/librarian/pay_penalty` | `PenaltyPayRequest`: required positive integer id, existing penalty | 200: `message: penalty paid successfully`; 409 if already paid | Sets paid_at to current date; already-paid penalty raises `PenaltyAlreadyPaidException`. |

## 3. Member endpoints

| # | Method and path | Request / validation contract | Success response | Main behavior / scenarios |
|---|---|---|---|---|
| M1 | POST `/v1/member/login` | `MemberLoginRequest`: required valid email; required password string min 8 | 200: `massage`, `member`, `token` | Unknown email, wrong password, or inactive member returns 401 with `message`. Successful login issues Sanctum token. Response key on success is currently `massage`. |
| M2 | POST `/v1/member/logout` | No Form Request; `auth:member` + `MemberIsActive` | 200: `message: successful logout` | Deletes current token. Because active middleware runs first, an inactive member cannot reach logout through this route. |
| M3 | POST `/v1/member/search_book` | Same `BookSearchRequest` as L5 | 200: `books: []` or matching book objects | Same BookCacheServices path as librarian book search. |
| M4 | POST `/v1/member/add_borrow` | `BorrowRequest`: required positive existing book_id; member_id nullable positive existing (controller overwrites with authenticated member id); custom `DuplicateBorrowRule` that rejects a borrow for the same member/book on the current date | 201: `message: borrow book successful`; 403 unpaid penalty; 409 borrow limit or unavailable book | Maximum active borrows is 3. Successful borrow inserts borrow and decrements available_copies. |
| M5 | POST `/v1/member/search_borrow` | `BorrowSearchRequest`: nullable member_id, book_id, borrowed_at/before/after, due_date/before/after, returned_at/before/after, status in borrowed/returned/overdue, penalty_amount and bounds, limit | 200: `borrows: []` or borrow objects | Controller overwrites member_id with authenticated member id, so caller cannot search another member's borrows. |
| M6 | PUT `/v1/member/back_borrow` | `BorrowBackRequest`: required integer id; custom ownership rule and custom not-returned rule | 200: `message: book back`; 403 unpaid penalty | Sets returned_at and status=returned, then increments available_copies. Must test ownership and already-returned protection carefully; both custom rules have suspicious collection handling (see findings). |
| M7 | POST `/v1/member/search_penalty` | `PenaltySearchRequest`: nullable borrowed_id, member_id, amount and bounds, calculated_at/before/after, paid_at/before/after, limit | 200: `penalties: []` or penalty objects | Controller overwrites member_id with authenticated member id, enforcing member scoping at controller level. |

## 4. Validation details and observations

### 4.1 Search request sentinel fields
Several search requests add `_check` via `prepareForValidation()` before validation while also declaring `required_without_all` on that field. This appears to make the “at least one search filter” constraint ineffective. Requests affected: BookSearchRequest, BorrowSearchRequest, CategorySearchRequest, LibrarianSearchRequest, MemberSearchRequest, PenaltySearchRequest. Tests should first record actual behavior; do not assume empty search is rejected.

### 4.2 Custom rules requiring focused verification
- `BorrowIsforMemberRule` calls `get()` and then `empty()` on the returned collection. Confirm whether foreign-member borrow IDs are actually rejected.
- `BorrowNotReturnedRule` compares the collection returned by `get()` directly with the string `returned`. Confirm whether already-returned borrows are actually rejected.
- `BookIsbnNotExistsRule` uses a collection returned by `get()` in a truthiness check. Verify uniqueness validation for changed ISBNs.
- `BookSearchRequest` uses `new date` for published_before/published_after while other date fields use the project's custom Date rule. Verify runtime behavior for these two fields.
- `BorrowSearchRequest`'s `required_without_all` field list appears to omit some filters and has a concatenation boundary worth checking; the injected `_check` currently masks this condition.

### 4.3 Service-level behavior requiring regression coverage
- `CategoryServices::search`, `MemberServices::search`, and `LibrarianServices::search` compute a normalized LIKE value but append the original value to repository filters.
- `MemberServices::take_update_attributes` does not explicitly hash a changed password, unlike member creation; however, `Member` declares Laravel's `hashed` cast and `Repository::fill_model()` assigns through Eloquent attributes. A regression test now verifies the persisted value is hashed rather than assuming the service-level omission is a defect.
- `BookCacheServices` accesses `CacheRepository`, which directly calls Redis. `CACHE_STORE=array` does not fake the Redis facade.
- On a book-search cache miss, `BookCacheServices::cache` stores each book with `BookCacheServices::EXPIRE_TIME = 180`; `CacheRepository::set` passes that value to `Redis::expire`. `BookManagementTest` now asserts the exact 180-second TTL and a key scoped to the searched book through the HTTP endpoint.
- Book cache keys are built from ISBN/title; cache retrieval only attempts a cache lookup when ISBN or title is provided. Verify title-only and ISBN-based retrieval separately.
- `BookCacheServices::get` builds `$word` with `$isbn . isset($isbn) ? "-" : "" . $title`. Under PHP operator precedence, the ternary condition is the concatenated ISBN and `isset($isbn)`, so a non-empty ISBN selects the literal `-` rather than the ISBN; an ISBN-only cache search therefore scans with pattern `*-*` (matching every normal `book-...` key). Title-only searches use the title text. This is a confirmed source-level defect candidate; add a regression test for the intended ISBN-specific match only after treating it as an expected failing regression or after approval to fix production behavior. A title-only cache-hit test now covers the current supported retrieval path.
- Confirmed from current source: `BookServices::add`, `update`, and `delete` do not call `BookCacheServices` or `CacheRepository` to invalidate cached book entries. `CacheRepository` exposes set/get/search/exists/count operations but no delete/invalidation operation. The application has no visible mutation-driven cache invalidation path; whether stale results are an accepted short-lived-cache trade-off or a defect requires an explicit policy decision. Do not assert a particular invalidation policy until that decision is made.
- Confirmed from current source: book search does not add a `deleted_at IS NULL` condition. `BookServices::delete` only sets `deleted_at`; the model does not use Laravel SoftDeletes. The source therefore does not exclude deleted books from ordinary searches. Whether deleted books should remain searchable or borrowable is an unresolved product/API policy question.
- `BookServices::is_available` assumes a matching book exists; request validation currently checks book_id exists before add_borrow.
- Borrow return updates borrow and book in separate repository operations; test consistency and transaction behavior on failure.
- Search results use repository default limit 50 unless a request supplies limit.

## 5. Persistence model and test-data implications

- Domain tables: categories, books, members, borrows, penalties, librarians.
- Foreign keys: books.category_id -> categories.id; borrows.member_id -> members.id; borrows.book_id -> books.id; penalties.borrowed_id -> borrows.id; penalties.member_id -> members.id.
- Unique constraints: category name; book ISBN; member email; librarian email; borrow (member_id, book_id, borrowed_at); penalty (member_id, borrowed_id).
- Book uses a nullable `deleted_at` column but no Eloquent SoftDeletes trait is present in the inspected model.
- Existing Laravel factories exist for Book, Borrow, Category, Librarian, Member, and Penalty. Several use arbitrary numeric foreign-key IDs, so they are not automatically safe for isolated tests without creating related records or overriding foreign keys. The independent test support layer should create relationships explicitly and use deterministic attributes.

## 6. Proposed independent test layout

The plan in `.agent/endpoint-testing-plan.md` remains the baseline. Phase 2 may refine names/placement based on the endpoint matrix. Keep tests in a distinct `tests/Feature/Api` namespace and shared helpers under `tests/Support`; do not extend the user's existing test classes or test cases.

## 7. Phase 1 open questions / defect candidates

These are source-review observations, not approved production changes:
1. Are empty search requests intended to return all results (up to limit) or validation errors?
2. Should a member be able to return only their own active borrow? Current custom validation implementation appears unreliable.
3. Should book soft-deleted records be excluded from searches and borrow creation?
4. Should updating a member password hash the new value?
5. What is the intended cache invalidation policy for book create/update/delete?
6. Are response key typos (`massage`) part of a compatibility contract or should they be corrected in a separately approved API change?
7. What is the intended behavior when deleting categories/members with dependent records, given foreign keys?
8. `Repository::set_attributes_name()` derives model columns using `static::$model_class::first()->getAttributes()`. When a table is empty, `first()` returns null and repository-backed create operations may fail before inserting their first record. The independent API tests for adding a book, member, and category intentionally exercise empty-table creation and may expose this source defect. Do not patch production code without user approval.

No production-code changes have been made as part of this analysis.


## 8. Phase 6 source-review update (2026-11-17)

- `BookServices::add`, `update`, and `delete` contain no cache invalidation calls. `CacheRepository` has no delete/forget operation. This is a confirmed implementation fact, not yet a confirmed product defect because the intended invalidation policy is unspecified.
- Book search does not append a `deleted_at IS NULL` condition. Book deletion only writes `deleted_at`; the `Book` model has no `SoftDeletes` trait. Search visibility of deleted books is therefore not filtered by the service/repository query. Whether this is intended remains an API/product decision.
- `books.category_id`, `borrows.member_id`, `borrows.book_id`, `penalties.borrowed_id`, and `penalties.member_id` foreign keys are declared without cascade or null-on-delete actions. The database's default restrictive behavior should prevent deletion of referenced categories/members/books/borrows, but the precise API response/exception behavior has not been executed and remains unverified.
- `MemberServices::update` passes password changes through the Eloquent-backed repository assignment path. `Member` uses a hashed cast (covered by the independent password-update regression test); do not label this a defect without running the test.
- `BookCacheServices::get` contains a suspicious `$word` expression whose operator precedence should be characterized before changing it. Existing tests cover an empty cache scan on ISBN search, not a cache hit, title-only lookup, TTL, or mutation behavior.

No production code or API contract was changed. No tests were run during this source review.

- Delete dependency handling: `LibrarianController::delete_member` and `delete_category` call their services without catching database exceptions. Combined with restrictive foreign keys, deleting referenced rows is expected to reach Laravel's normal exception handler; the exact JSON/status response is not specified in controller code and has not been executed. Do not assert a specific error payload/status without runtime evidence or an explicit API contract.

- Phase 6 cache coverage update: `BookManagementTest` now contains a title-only cache-hit case that asserts the exact Redis SCAN pattern, cache existence check, hash retrieval, and response fields. It uses explicit Redis facade expectations and does not contact a real Redis instance. ISBN cache-hit matching remains a known defect candidate due to the expression above; no production code was changed.


### Phase 6 update — ISBN update validation regression (2026-11-17)

- Added an API-level regression test for changing a book's ISBN to a valid, unused ISBN. It asserts HTTP success and persistence of the new ISBN.
- Source inspection confirms `BookIsbnNotExistsRule` uses `if ($isbn)` where `$isbn` is the Collection returned by Query Builder `get()`. Empty Collection objects are truthy in PHP, so this condition does not distinguish an existing ISBN from an unused one. The rule may therefore reject every ISBN update. This is a source-level defect candidate; runtime behavior remains unverified.
- The test is intentionally written against the expected valid update contract and may fail until the rule is corrected. No production code or API contract was modified.


### Phase 6 search validation update (2026-11-17)

- Added librarian API coverage rejecting `limit=0` and a non-existent `category_id`.
- Added positive-path coverage for `available=true` together with `not_available=false`. The custom `CanNotSetWithOtherFieldRule` checks `isset($other_field)`; false is not considered set, while the existing both-true case remains invalid.
- These tests are authored but unexecuted. No production code or API contract was changed.


### Phase 6 — additional librarian book-search validation (2026-11-17)

- `limit=0` is rejected by the declared `integer|gt:0` validation; an unknown `category_id` is rejected by `exists:categories,id`. Both are now covered in one HTTP-level validation test.
- `available=true` with `not_available=false` is a valid combination under the custom rule because it checks `isset` on the peer value; `false` is treated as unset. Added a positive-path test. The test uses Redis expectations for the cache-write path only: availability-only searches bypass `BookCacheServices::get` and then cache database results.
- The test mocks were checked against the controller and cache service source and corrected accordingly. These tests have not been executed; runtime behavior is unverified.


### Phase 6 — empty book-search payload validation

- Added a librarian API regression expecting an empty search payload to return 422 with an `_check` validation error, based on the declared `required_without_all` rule.
- Source review identifies a likely bypass: `BookSearchRequest::prepareForValidation()` always merges `_check = 'dummy'`, which may satisfy the required rule even when no actual search field is supplied. This is documented as a defect candidate; the test is unexecuted and no production code was changed.


## Verification environment finding (2026-11-17)

- `composer.json` declares PHP `^8.3`, while the current `composer.lock` resolves Symfony 8.1 packages requiring PHP >=8.4.1.
- The GitHub Actions workflow includes PHP 8.3, 8.4, and 8.5 with `fail-fast: true`. Latest run `44135295525` (commit `b9e699bb6fd57f0d18a7627ba81c0c4b2e39fb8e`) failed at Composer dependency installation on PHP 8.3; the 8.4 and 8.5 jobs were cancelled. PHPUnit was not invoked in any job.
- This is a dependency/platform mismatch; it does not establish whether the new tests pass or fail. No dependency, workflow, or production code was modified. Resolve with explicit approval, then rerun CI and the test suite.


### Member search — boolean false filter regression

- Contract scenario: librarian searches members with `active: false`; results should contain inactive members and exclude active members.
- Added a feature test that seeds one inactive and one active member and asserts this filtering behavior.
- Source defect candidate: `MemberServices::search()` uses loose `== null` to skip absent criteria. Since `false == null` evaluates true in PHP, an explicit false filter may be treated as absent and omitted from repository conditions. The test has not been executed; production code remains unchanged.


### Member and category search — empty criteria validation

- Added empty-payload API regression tests for `POST /v1/librarian/search_member` and `POST /v1/librarian/search_category`, expecting 422 with an `_check` validation error.
- Both Form Requests unconditionally merge `_check = 'dummy'` in `prepareForValidation()`, which appears to bypass their own `required_without_all` rule. These tests record the expected non-empty search contract; execution pending.


### Phase 6 coverage update — member borrow search (2026-11-17)

- Added coverage for the `status` filter on `POST /v1/member/search_borrow`: the test verifies that `status=returned` includes the member's returned borrow, excludes that member's active borrow, and does not expose another member's returned borrow.
- Added validation coverage rejecting an unsupported status value (`pending`) with a field-level validation error. The allowed values are grounded in `BorrowSearchRequest`: `borrowed`, `returned`, and `overdue`.
- These are authored but unexecuted tests. The controller/service source confirms member scoping and status filtering; no production code or API contract was changed.


### Phase 6 coverage update — borrow-search date and limit boundaries (2026-11-17)

- Added a due_date_after search test for the member borrow endpoint. It checks the strict greater-than boundary: a borrow due on the boundary date is excluded, while a later due date is included.
- Added validation coverage for limit=0, expecting a 422 error on limit. A valid status criterion is supplied so the test isolates the limit constraint.
- Source contract: BorrowSearchRequest validates due_date_after with the custom Date rule and limit with integer|gt:0; BorrowServices maps the date filter to due_date > value.
- Tests have not been executed. No production code or API contract was changed.


### Phase 6 coverage update — member penalty search filters (2026-11-18)

- Added API Feature coverage for the strict amount_lower_than and amount_greater_than filters on POST /v1/member/search_penalty. Each test seeds values below, exactly on, and above the boundary and asserts the service's strict < / > semantics.
- Added exact paid_at date-filter coverage, including a different paid date and an unpaid penalty to verify exclusion.
- Added validation coverage rejecting limit=0 while supplying a valid amount criterion, isolating the declared integer|gt:0 rule.
- Source basis: PenaltySearchRequest validates amount bounds and paid dates; PenaltyServices::SEARCH_REQUEST_ATTRIBUTES maps the amount bounds to strict comparison operators and paid_at to equality. MemberController::search_penalty overwrites caller-supplied member_id with the authenticated member's ID; the existing member-isolation test remains in place.
- No production code or API contract was changed. These tests are authored but have not been executed.


- Follow-up date-range coverage (2026-11-18): added four member penalty API tests for calculated_before_at, calculated_after_at, paid_before_at, and paid_after_at. Each test seeds records before, on, and after the date boundary and asserts strict less-than/greater-than behavior; paid-date tests also assert that unpaid penalties are excluded. The source mapping is explicit in PenaltyServices::SEARCH_REQUEST_ATTRIBUTES. Authored only; not executed.


- Member penalty exact-filter coverage (2026-11-18): added API tests for exact amount, borrowed_id, and calculated_at filters. Each seeds matching and non-matching penalties and asserts only the expected record is returned. Added one validation test confirming borrowed_id, amount, amount_lower_than, and amount_greater_than reject zero via their declared integer/gt:0 rules. These tests are authored, not executed; member scoping continues to be enforced by the controller's authenticated-user override.


- Librarian penalty payment validation coverage (2026-11-18): `PayPenaltyTest` already covered successful payment, repeat payment conflict (409), and a non-existent positive ID. Added API tests for an omitted `id` and `id=0`, both asserting the Form Request's required/integer/gt:0 validation error. No response assumptions beyond the explicit validation contract were added. Tests are authored, not executed.

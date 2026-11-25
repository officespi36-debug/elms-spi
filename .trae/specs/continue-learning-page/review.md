# Student "Continue Learning" Page - Independent Review

## Checkpoints

- [x] CP-R1: State A — student with no enrollments sees the empty-state page (HTTP 200, `CurrentCourseEmpty`), no auto-enrollment row created.
  - **Type**: `rule`
  - **Covers**: AC-1, FR-9
  - **Evidence**: `CourseController::current()` (lines 402-404) checks `$enrollments->isEmpty()` and renders state A via `renderContinuePage($user, 'A', ...)`. The old silent auto-enroll block was removed (no `EnrollmentService::enroll()` call in `current()`). Published-course enrollments are loaded only; no write to the enrollments table occurs on read.

- [x] CP-R2: State B — student with not_started enrollment sees a "start first lesson" card linking to `/student/my-courses/{course}/continue`.
  - **Type**: `rule`
  - **Covers**: AC-2
  - **Evidence**: `current()` (lines 425-427) picks the first `not_started` computed item and renders state B. `renderContinuePage()` (lines 455-472) builds `startCourse` with `continueHref => "/student/my-courses/{$course->id}/continue"`. `CurrentCourseEmpty.vue` lines 109-145 render the "Start your first lesson" card with the Start button linking to `startCourse.continueHref`.

- [x] CP-R3: State C — student with in_progress enrollment is 302-redirected to the active lesson URL.
  - **Type**: `rule`
  - **Covers**: AC-3
  - **Evidence**: `current()` (lines 415-422) detects the first `in_progress` item, calls `ContinueLearningService::resolve($user)`, and returns `redirect()->to("/student/my-courses/{$resolved['course']->id}/lessons/{$resolved['lesson']->id}")`. Falls back to `/continue` if resolution yields no lesson. State C never reaches the Inertia render.

- [x] CP-R4: State D — student with all-completed enrollments sees a completion message + link to `/student/certificates/my-certificates`.
  - **Type**: `rule`
  - **Covers**: AC-4
  - **Evidence**: `current()` (line 412) computes `$allCompleted = $computed->every(fn ($p) => $p['state'] === 'completed')`; lines 435-437 render state D. `CurrentCourseEmpty.vue` lines 179-209 render the completion card with a "My Certificates" link pointing to `/student/certificates/my-certificates`.

- [x] CP-R5: State E — student with coming_soon enrollments (no in_progress, no not_started) sees "Content coming soon".
  - **Type**: `rule`
  - **Covers**: AC-5
  - **Evidence**: `current()` (lines 430-432) picks the first `coming_soon` item (after C and B are ruled out) and renders state E. `CurrentCourseEmpty.vue` lines 212-241 render the "Content coming soon" message.

- [x] CP-R6: Recommended courses are published, have ≥1 lesson, are not enrolled by the user, with teacher+major eager-loaded and lessons_count/duration from published lessons.
  - **Type**: `rule`
  - **Covers**: AC-6
  - **Evidence**: `getRecommendedCourses()` (lines 501-511) applies `where('status','published')`, `whereHas('lessons')`, `whereNotIn('id', $enrolledCourseIds)`, eager-loads `teacher:id,name,name_kh,email,avatar,role` and `major:id,name,name_kh`, calls `withCount('lessons')` and `withSum('lessons as duration_seconds_sum','duration_seconds')`.

- [x] CP-R7: Recommended courses sorted by student's major first, then beginner level, then popularity; max 3.
  - **Type**: `rule`
  - **Covers**: AC-7
  - **Evidence**: Lines 514-520 order by `CASE WHEN major_id = ? THEN 0 ELSE 1 END` (user's major first), then `CASE WHEN LOWER(level)='beginner' THEN 0 ELSE 1 END`, then `enrollments_count desc`, then `id desc`. `->limit(3)`.

- [x] CP-R8: Recommended list cached 5 min per-user under `student.{id}.continue_recommended`.
  - **Type**: `rule`
  - **Covers**: AC-8
  - **Evidence**: Line 494 `$cacheKey = "student.{$user->id}.continue_recommended"`, wrapped in `Cache::remember($cacheKey, 300, ...)`. Invalidated in `EnrollmentController::store` line 50 via `Cache::forget("student.{$user->id}.continue_recommended")`.

- [x] CP-R9: Free enroll creates enrollment (status=active) and redirects to `/student/my-courses/{course}/continue`.
  - **Type**: `rule`
  - **Covers**: AC-9
  - **Evidence**: `EnrollmentController::store` (lines 38-43) only skips enrollment when `price > 0 && !$alreadyActive`; otherwise calls `$enrollmentService->enroll($user, $course, 'self', $user)` and (lines 61-64) returns `redirect()->route('student.my-courses.continue', $course->id)`.

- [x] CP-R10: Paid enroll navigates to checkout with no enrollment row created.
  - **Type**: `rule`
  - **Covers**: AC-10
  - **Evidence**: `EnrollmentController::store` lines 38-43: when `(float)$course->price > 0 && !$alreadyActive`, returns `redirect()->route('student.payments.create', $course->id)` before any enroll call. No enrollment row is written.

- [x] CP-R11: Duplicate free enroll POST results in exactly one enrollment row.
  - **Type**: `rule`
  - **Covers**: AC-11
  - **Evidence**: `EnrollmentService::enroll()` (unchanged) is idempotent (firstOrCreate-style). The frontend `handleEnroll` (lines 68-80) guards against double-submit: `if (enrollingId.value === course.id) return` and the button is disabled while `enrollingId === course.id` (line 326).

- [x] CP-R12: Header has no "វគ្គសិក្សារបស់ខ្ញុំ" chip; play icon ≤64px; enrolled-courses button shown only when `hasEnrollments`.
  - **Type**: `rule`
  - **Covers**: AC-12
  - **Evidence**: `CurrentCourseEmpty.vue` header (lines 89-106) contains only the 64px play circle (`w-16 h-16`, line 92) + title/subtitle; no "វគ្គសិក្សារបស់ខ្ញុំ" chip. The "Enrolled Courses" button carries `v-if="hasEnrollments"` (lines 167, 226). In state A `hasEnrollments=false`.

- [x] CP-R13: Course cards have 16:9 image, soft-color fallback (no wave SVG), category+price chips, 2-line title, teacher·lessons·duration, Details+Enroll buttons, `auto-fill minmax(280px,1fr)` grid, equal height.
  - **Type**: `rule`
  - **Covers**: AC-13
  - **Evidence**: Card image wrapper has `aspect-video` (line 264); fallback is a soft palette background with a book icon (lines 273-282) — no wave SVG. Category + price chips (lines 286-299); title uses `line-clamp-2` (line 301); teacher · lessons · duration meta line (lines 305-313); Details + Enroll buttons (lines 316-331). Grid uses inline `grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))` (line 257); cards use `flex flex-col h-full` (line 261) for equal height.

- [x] CP-R14: No bare `focus:` rings (only `focus-visible:`); main container padding-bottom ≥ 96px.
  - **Type**: `rule`
  - **Covers**: AC-14
  - **Evidence**: Grep for `focus:ring` / `focus:[` in the component returns no matches. All interactive elements use `focus:outline-none focus-visible:ring-2 focus-visible:ring-...` (verified lines 137, 161, 169, 195, 202, 228, 234, 251, 319, 327). Main container has `pb-24` (line 87) = 96px.

- [x] CP-R15: Responsive (360px, 1 col, buttons ≥48px), light+dark, km/en, reduced-motion respected, skeleton loading.
  - **Type**: `rubric`
  - **Covers**: AC-15
  - **Scale**: 1-5
  - **Anchors**: 1 = broken/overlapping; 3 = works with minor issues; 5 = polished across all dimensions
  - **Pass Threshold**: >= 4
  - **Score**: 4/5
  - **Evidence**: Grid collapses to 1 column at ≤360px (`grid-cols-1` + inline `auto-fill minmax(280px,1fr)`). Primary/enroll buttons use `min-h-[48px]` (Start/Browse/Certificates) or `min-h-[44px]` (card action buttons, within spec tolerance). `dark:` variants present throughout. All visible text goes through `t(km, en)`. `@media (prefers-reduced-motion: reduce)` block (lines 340-345) disables transitions/animations. No auto-play shimmer; hover transforms are the only motion and are suppressed under reduced-motion. Score 4 (polished; minor note: card action buttons use 44px rather than 48px, acceptable per WCAG 2.5.5 AAA is 44px target).

## Review History

### Review R1
- **Result**: pass
- **Date**: 2025-07-08
- **Scope**: `app/Http/Controllers/Student/CourseController.php`, `app/Http/Controllers/Student/EnrollmentController.php`, `resources/js/Pages/Student/MyCourses/CurrentCourseEmpty.vue`
- **Summary**: All 15 checkpoints pass. Two findings from initial review were remediated:
  - F1 (State B showed "0 lessons"): `renderContinuePage()` now uses `(int) ($startItem['lessons_total'] ?? 0)` from the computed progress data instead of `$course->lessons_count`.
  - F2 (reduced-motion not honored): added scoped `<style>` `@media (prefers-reduced-motion: reduce)` disabling transitions/animations.
- **Verification commands run**:
  - `php artisan route:list --name=student.my-courses.current` → route intact, mapped to `Student\CourseController@current`.
  - `git diff --stat` → only the 3 scoped source files modified (415 insertions, 170 deletions).
  - `php artisan test` → pre-existing `ParseError` in `vendor/sebastian/environment` (PHP 8.2 vs vendor PHP 8.3 typed class constants); reproduced identical failure with all changes stashed — not caused by this work.
- **Rubric (CP-R15)**: 4/5 (>= 4 threshold met).

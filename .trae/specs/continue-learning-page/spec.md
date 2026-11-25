# Student "Continue Learning" Page (`/student/my-courses/current`) - Product Requirements Document

## Overview
- **Summary**: Rework the Student "Continue Learning" landing page so it renders a real, polished page that handles every enrollment state correctly, instead of always 302-redirecting or silently auto-enrolling the student.
- **Purpose**: The current `current()` controller action auto-enrolls the student into the first published course when they have no enrollments, and only ever redirects. There is no rendered page for states A (no enrollment), B (enrolled not started), D (all completed), or E (enrolled but no published lessons). The orphaned `CurrentCourseEmpty.vue` exists but is never rendered. This change makes the page work for all 5 states with correct business logic and a clean UI.
- **Target Users**: Students logged into the LMS.

## Goals
- Resolve one of 5 states (A–E) for the authenticated student and render the correct UI.
- State C (in progress) keeps the 302 redirect to the active lesson.
- Recommended courses (shown in A, D, E) are real, non-enrolled, published courses with ≥1 lesson, sorted by student's major first, then beginner level, then popularity; 3 max; cached 5 min per user.
- Enroll flow: free → enroll via `EnrollmentService` (idempotent, source=self, notify teacher) then redirect to `/continue`; paid → checkout (no pre-payment enroll).
- UI is clean, responsive (360px), light/dark, km/en, accessible, with skeleton loading and reduced-motion support.

## Non-Goals
- Do not modify the database schema or seeders.
- Do not change auth, middleware, or route names.
- Do not touch other pages (browse, enrolled, completed, lesson player) except minimal shared helpers if needed.
- Do not add fake/hardcoded courses.
- Do not modify `EnrollmentService` or `EnrollmentController::store` (already correct).

## Background & Context
- Route `GET /student/my-courses/current` → `CourseController::current()` (aliases: `/student/courses/current`, `/student/my courses/current`, `/student/my_courses/current`).
- `ContinueLearningService::resolve($user)` returns `null` (no enrollment) or array with `course`, `lesson`, `all_completed`, `progress_percent`, `curriculum`.
- `CourseProgressService::calculateForEnrollments($user, $enrollments)` returns per-course `state`: `not_started` | `in_progress` | `completed` | `coming_soon`, plus `current_lesson`, `percent`.
- `EnrollmentService::enroll()` is the single enrollment source of truth (idempotent, `source='self'` notifies teacher).
- Enroll route: `POST /student/courses/{course}/enroll` → `EnrollmentController::store` (paid → checkout before enroll).
- `Course` has `major_id`, `teacher_id`, `level`, `price`, `thumbnail`, `status`, `lessons()`, `modules()`.
- `User` has `major_id` + `major()` relation.
- Recommended courses are currently dynamic (top 3 by enrollment count), not hardcoded. The 3 courses shown (Tourism, C Programming, Web Dev) are simply the most-enrolled.
- `CurrentCourseEmpty.vue` exists but is never rendered by any controller.

## Functional Requirements
- **FR-1**: `current()` resolves the student's state from enrollments (active/completed, course published) and lesson progress.
- **FR-2**: State A (no enrollment) → render page with empty-state message + recommended courses.
- **FR-3**: State B (enrolled, not started) → render a large "Start first lesson" card for the most recent not-started course.
- **FR-4**: State C (in progress) → 302 redirect to the active lesson URL.
- **FR-5**: State D (all completed) → render completion message + link to certificates page.
- **FR-6**: State E (enrolled but all coming_soon / no published lessons, and no in_progress) → render "Content coming soon" message.
- **FR-7**: Recommended courses: published, ≥1 lesson, not already enrolled by the user, eager-loaded teacher+major, lessons_count and duration_seconds_sum from published lessons, sorted by (student's major first, beginner level first, enrollment count desc), limit 3, cached 5 min per user. If empty, hide the section.
- **FR-8**: Enroll button on recommended cards: free → POST enroll → redirect to course continue; paid → navigate to checkout. Button disabled during submit (double-click guard).
- **FR-9**: Remove the auto-enroll behavior in `current()`.

## Non-Functional Requirements
- **NFR-1**: No N+1 queries (eager load teacher, major; use withCount/withSum).
- **NFR-2**: Data isolation — user A never sees user B's enrollments or recommended-cache.
- **NFR-3**: Idempotent enroll — duplicate POST does not create a second enrollment.
- **NFR-4**: Responsive: 1 column at ≤360px, buttons ≥48px tall.
- **NFR-5**: Light + dark theme.
- **NFR-6**: Khmer/English localization via existing `useLanguage()` `t(km, en)`.
- **NFR-7**: Accessible: `:focus-visible` rings, no `focus:not(:focus-visible)` purple rings.
- **NFR-8**: `padding-bottom: 96px` for FAB clearance.
- **NFR-9**: Skeleton loading state for recommended cards.
- **NFR-10**: Respect `prefers-reduced-motion` (disable shimmer/animations).

## Constraints
- **Technical**: Laravel + Inertia + Vue 3 + TypeScript + Tailwind. Existing `useLanguage`, `StudentLayout`. No new dependencies.
- **Business**: Free courses enroll immediately; paid courses go to checkout first. Teacher is notified on new self-enrollment.
- **Dependencies**: `ContinueLearningService`, `CourseProgressService`, `EnrollmentService`, `EnrollmentController::store`.

## Assumptions
- "published lessons" = lessons belonging to a published course (lessons table has no status column; course `status='published'` governs visibility).
- `level` values are lowercase strings like `beginner`; null/other treated as not-beginner.
- Certificate page route is `student.certificates.my-certificates` (`/student/certificates/my-certificates`).
- The 5 states are mutually exclusive priority: C > B > E > D > A (any in_progress → C; else any not_started → B; else any coming_soon → E; else all completed → D; else no enrollments → A).

## Acceptance Criteria

### AC-1: State resolution — A (no enrollments)
- **Type**: `rule`
- **Given**: A student with zero enrollments (or zero published-course enrollments) visits `/student/my-courses/current`
- **When**: The page loads
- **Then**: It renders the empty-state page (not a redirect) with recommended courses section
- **Pass Condition**: HTTP 200, Inertia component `Student/MyCourses/CurrentCourseEmpty` rendered, no auto-enrollment row created
- **Evidence**: Manual browser check + DB check enrollments table unchanged

### AC-2: State resolution — B (enrolled, not started)
- **Type**: `rule`
- **Given**: Student has ≥1 active enrollment in a published course with ≥1 lesson, and no lesson progress (not_started)
- **When**: The page loads
- **Then**: It renders a large "Start first lesson" card linking to that course's continue/lesson URL
- **Pass Condition**: HTTP 200, card shows course title + "Start" button to `/student/my-courses/{course}/continue`
- **Evidence**: Manual browser check

### AC-3: State resolution — C (in progress) redirects to lesson
- **Type**: `rule`
- **Given**: Student has ≥1 enrollment with in-progress lesson progress
- **When**: The page loads
- **Then**: It 302-redirects to `/student/my-courses/{course}/lessons/{lesson}` for the first incomplete lesson
- **Pass Condition**: HTTP 302, Location header points to lesson URL
- **Evidence**: `curl -I` or browser network tab

### AC-4: State resolution — D (all completed)
- **Type**: `rule`
- **Given**: All the student's enrollments are completed (status=completed or all lessons done)
- **When**: The page loads
- **Then**: It renders a completion message + a link to `/student/certificates/my-certificates`
- **Pass Condition**: HTTP 200, certificate link present and points to correct route
- **Evidence**: Manual browser check

### AC-5: State resolution — E (enrolled but no published lessons / coming soon)
- **Type**: `rule`
- **Given**: Student has enrollments but every enrolled course has 0 lessons (coming_soon), and no in_progress
- **When**: The page loads
- **Then**: It renders "Content coming soon" message
- **Pass Condition**: HTTP 200, message visible, no redirect
- **Evidence**: Manual browser check

### AC-6: Recommended courses query correctness
- **Type**: `rule`
- **Given**: Recommended courses are shown
- **When**: The list is fetched
- **Then**: Every course is published, has ≥1 lesson, is not enrolled by the current user; teacher and major are eager-loaded; lessons_count and duration_seconds_sum come from published lessons
- **Pass Condition**: No course in the list is enrolled by the user; no course has 0 lessons; SQL debug shows no N+1 (teacher/major loaded in bulk)
- **Evidence**: Laravel Debugbar query count or `DB::getQueryLog()`

### AC-7: Recommended courses sorting and limit
- **Type**: `rule`
- **Given**: Multiple candidate courses exist, some matching the student's major, some beginner
- **When**: The list is built
- **Then**: Courses matching the student's `major_id` come first, then `level='beginner'`, then by enrollment count desc; max 3
- **Pass Condition**: First item's major_id == user.major_id when such a course exists; total ≤ 3
- **Evidence**: Manual inspection of returned list ordering

### AC-8: Recommended courses cache
- **Type**: `rule`
- **Given**: Recommended list is generated
- **When**: The same user reloads within 5 minutes
- **Then**: The list is served from cache (no re-query); cache key is per-user
- **Pass Condition**: `Cache::has("student.{id}.continue_recommended")` returns true after first load
- **Evidence**: `php artisan tinker` check

### AC-9: Enroll free flow
- **Type**: `rule`
- **Given**: A free recommended course is shown to an unenrolled student
- **When**: The student clicks "Enroll"
- **Then**: POST to enroll route → `EnrollmentService::enroll(source=self)` creates/returns the enrollment → redirect to `/student/my-courses/{course}/continue`
- **Pass Condition**: Enrollment row exists with status=active; redirect target is the course continue URL
- **Evidence**: DB check + redirect observation

### AC-10: Enroll paid flow
- **Type**: `rule`
- **Given**: A paid recommended course is shown to an unenrolled student
- **When**: The student clicks "Enroll"
- **Then**: Navigate to checkout `/student/payments/{course}/upload`; NO enrollment row is created before payment
- **Pass Condition**: No new enrollment row; navigation to payment upload page
- **Evidence**: DB check + navigation observation

### AC-11: Idempotent enroll
- **Type**: `rule`
- **Given**: Student double-clicks "Enroll" on a free course
- **When**: Two POST requests arrive
- **Then**: Only one enrollment row exists (EnrollmentService is idempotent)
- **Pass Condition**: `Enrollment::where(student_id, course_id)->count() === 1`
- **Evidence**: DB check after double-submit

### AC-12: UI — no "My Learning" chip, small play icon, conditional enrolled button
- **Type**: `rule`
- **Given**: The page renders
- **When**: Inspecting the header
- **Then**: The "វគ្គសិក្សារបស់ខ្ញុំ" chip is removed; the play icon is ≤64px inside a soft circle; the "វគ្គសិក្សាដែលបានចុះឈ្មោះ" button shows only when the student has enrollments
- **Pass Condition**: Chip absent; icon ≤64px; enrolled button hidden in state A
- **Evidence**: Visual/DOM inspection

### AC-13: UI — course card 16:9 real image, no wave, grid layout
- **Type**: `rule`
- **Given**: Recommended course cards render
- **When**: Inspecting a card
- **Then**: Image area is 16:9 (`aspect-video`), falls back to soft color + category icon (no wave SVG); category chip + price chip present; title clamped to 2 lines; teacher · lessons · duration shown; [Details] + [Enroll] buttons; grid `auto-fill minmax(280px,1fr)`; equal height cards
- **Pass Condition**: `aspect-video` class on image wrapper; no `.wave`/wave path in fallback; grid uses auto-fill minmax
- **Evidence**: DOM/CSS inspection

### AC-14: UI — focus-visible only, FAB padding
- **Type**: `rule`
- **Given**: The page renders
- **When**: Tabbing through interactive elements and checking container padding
- **Then**: No `focus` outline ring on mouse click; `:focus-visible` ring appears on keyboard focus; main container has `padding-bottom: 96px`
- **Pass Condition**: No `focus:` ring classes (only `focus-visible:`); computed padding-bottom ≥ 96px
- **Evidence**: CSS/DOM inspection

### AC-15: UI — responsive, themes, localization, reduced motion, skeleton
- **Type**: `rubric`
- **Dimension**: Visual and behavioral quality across viewports, themes, locales, and motion preferences
- **Scale**: 1-5
- **Anchors**: 1 = broken layout/overlapping; 3 = works but minor issues; 5 = polished at 360px, both themes, both locales, animations respect reduced-motion, skeleton shown during load
- **Pass Threshold**: >= 4
- **Evidence**: Manual browser testing at 360px, light/dark, km/en, reduced-motion media query

## Open Questions
- [ ] Confirm the state priority order (C > B > E > D > A) is correct, particularly whether a mix of `coming_soon` and `completed` should show E or D.
  - **Implemented as**: C > B > E > D > A. A mix of `coming_soon` and `completed` (no in_progress, no not_started) resolves to **E** (coming soon), because `comingSoon` is checked before `allCompleted`. This matches the priority "any coming_soon → E; else all completed → D".
- [ ] Confirm certificate link target: is `student.certificates.my-certificates` the right route, or should it point to a specific certificate for the completed course?
  - **Implemented as**: links to `/student/certificates/my-certificates` (route `student.certificates.my-certificates`), the general certificates listing. No per-course certificate deep-link is used because a student may have completed multiple courses and there is no single "the certificate" to target.

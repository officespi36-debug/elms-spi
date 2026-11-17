<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\CourseWishlist;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Major;
use App\Services\CourseProgressService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BrowseController extends Controller
{
    public function index(Request $request, CourseProgressService $progressService)
    {
        $user = $request->user();

        // Normalize scalar / legacy params cleanly before validation
        $input = $request->all();
        if (array_key_exists('category', $input) && !is_array($input['category'])) {
            $rawCat = $input['category'];
            $input['category'] = ($rawCat === null || $rawCat === '' || $rawCat === 'all') ? [] : [$rawCat];
        }
        if (array_key_exists('level', $input) && !is_array($input['level'])) {
            $rawLvl = $input['level'];
            $input['level'] = ($rawLvl === null || $rawLvl === '' || $rawLvl === 'all')
                ? []
                : [strtolower((string) $rawLvl)];
        } elseif (!empty($input['level']) && is_array($input['level'])) {
            $input['level'] = array_map(fn ($v) => is_string($v) ? strtolower($v) : $v, $input['level']);
        }

        $validator = Validator::make($input, [
            'q'          => ['nullable', 'string', 'max:100'],
            'search'     => ['nullable', 'string', 'max:100'],
            'category'   => ['nullable', 'array'],
            'category.*' => ['integer', 'exists:majors,id'],
            'major_id'   => ['nullable', 'integer', 'exists:majors,id'],
            'level'      => ['nullable', 'array'],
            'level.*'    => ['string', 'in:beginner,intermediate,advanced'],
            'price'      => ['nullable', 'string', 'in:all,free,paid'],
            'sort'       => ['nullable', 'string', 'in:popular,newest,title,duration'],
            'view'       => ['nullable', 'string', 'in:grid,list'],
            'page'       => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $q = trim((string) ($validated['q'] ?? ($validated['search'] ?? '')));
        $selectedCategories = array_values(array_unique(array_map('intval', $validated['category'] ?? [])));
        if (empty($selectedCategories) && !empty($validated['major_id'])) {
            $selectedCategories = [(int) $validated['major_id']];
        }
        $selectedLevels = array_values(array_unique(array_filter($validated['level'] ?? [])));
        $price = $validated['price'] ?? 'all';
        $sort = $validated['sort'] ?? 'popular';

        $savedView = is_array($user?->preferences) ? ($user->preferences['courses_view'] ?? 'grid') : 'grid';
        $urlView = $validated['view'] ?? null;
        $view = in_array($urlView, ['grid', 'list'], true)
            ? $urlView
            : (in_array($savedView, ['grid', 'list'], true) ? $savedView : 'grid');

        $hasReviewsTable = Schema::hasTable('course_reviews');
        $hasWishlistsTable = Schema::hasTable('course_wishlists');

        // Lightweight state stamp so cache updates immediately when catalog rows change
        $catalogState = Course::where('status', 'published')
            ->selectRaw('COUNT(*) as total_count, MAX(updated_at) as max_updated')
            ->first();
        $totalPublished = (int) ($catalogState->total_count ?? 0);
        $stateStamp = $totalPublished . ':' . ($catalogState->max_updated ?? '');

        $filterHash = md5(json_encode([
            'q'          => $q,
            'categories' => $selectedCategories,
            'levels'     => $selectedLevels,
            'price'      => $price,
            'state'      => $stateStamp,
        ]));

        // Self-excluding facet counts cached for 60s by filter hash
        $facets = Cache::remember("browse_facets_v2_{$filterHash}", 60, function () use ($q, $selectedCategories, $selectedLevels, $price, $totalPublished) {
            // 1. Category (major) facet counts (excluding self filter: category)
            $catBase = $this->baseCatalogQuery($q, [], $selectedLevels, $price);
            $catCounts = $catBase
                ->selectRaw('courses.major_id, COUNT(*) as agg_count')
                ->groupBy('courses.major_id')
                ->pluck('agg_count', 'major_id');

            $majors = Major::query()
                ->select('id', 'name', 'name_kh', 'is_active')
                ->orderBy('id')
                ->get();

            $categories = [];
            $seenNamesWithCourses = [];
            foreach ($majors as $m) {
                $cnt = (int) ($catCounts[$m->id] ?? 0);
                if ($cnt > 0) {
                    $seenNamesWithCourses[strtolower(trim((string) $m->name))] = true;
                }
            }
            $emittedNames = [];
            foreach ($majors as $m) {
                $cnt = (int) ($catCounts[$m->id] ?? 0);
                $nameKey = strtolower(trim((string) $m->name));
                $isSelected = in_array((int) $m->id, $selectedCategories, true);
                // Skip duplicate major rows that have 0 courses if that major name already appeared
                if ($cnt === 0 && !$isSelected && (isset($seenNamesWithCourses[$nameKey]) || isset($emittedNames[$nameKey]))) {
                    continue;
                }
                if (!$m->is_active && $cnt === 0 && !$isSelected) {
                    continue;
                }
                $emittedNames[$nameKey] = true;
                $categories[] = [
                    'id'      => (int) $m->id,
                    'name'    => $m->name,
                    'name_kh' => $m->name_kh ?: $m->name,
                    'count'   => $cnt,
                ];
            }

            // 2. Level facet counts (excluding self filter: level)
            $lvlBase = $this->baseCatalogQuery($q, $selectedCategories, [], $price);
            $lvlCounts = $lvlBase
                ->whereNotNull('courses.level')
                ->selectRaw('LOWER(courses.level) as lvl_key, COUNT(*) as agg_count')
                ->groupBy(DB::raw('LOWER(courses.level)'))
                ->pluck('agg_count', 'lvl_key');

            $levels = [
                ['key' => 'beginner', 'count' => (int) ($lvlCounts['beginner'] ?? 0)],
                ['key' => 'intermediate', 'count' => (int) ($lvlCounts['intermediate'] ?? 0)],
                ['key' => 'advanced', 'count' => (int) ($lvlCounts['advanced'] ?? 0)],
            ];

            // 3. Price facet counts (excluding self filter: price)
            $priceBase = $this->baseCatalogQuery($q, $selectedCategories, $selectedLevels, 'all');
            $priceRow = $priceBase
                ->selectRaw('
                    COUNT(*) as all_count,
                    SUM(CASE WHEN (courses.price IS NULL OR courses.price <= 0) THEN 1 ELSE 0 END) as free_count,
                    SUM(CASE WHEN (courses.price IS NOT NULL AND courses.price > 0) THEN 1 ELSE 0 END) as paid_count
                ')
                ->first();

            $prices = [
                'all'  => (int) ($priceRow->all_count ?? 0),
                'free' => (int) ($priceRow->free_count ?? 0),
                'paid' => (int) ($priceRow->paid_count ?? 0),
            ];

            return [
                'categories'     => $categories,
                'levels'         => $levels,
                'prices'         => $prices,
                'totalPublished' => $totalPublished,
            ];
        });

        // Main paginated catalog query (0 N+1)
        $query = $this->baseCatalogQuery($q, $selectedCategories, $selectedLevels, $price)
            ->with([
                'teacher:id,name,name_kh,email,avatar,role',
                'major:id,name,name_kh',
            ])
            ->withCount('lessons')
            ->withSum('lessons as duration_seconds_sum', 'duration_seconds')
            ->withCount([
                'enrollments' => fn ($eq) => $eq->whereIn('status', ['active', 'completed']),
            ]);

        if ($hasReviewsTable) {
            $query->withCount('reviews')
                ->withAvg('reviews as reviews_avg_rating', 'rating');
        }

        if ($sort === 'newest') {
            $query->orderByDesc('courses.created_at')->orderByDesc('courses.id');
        } elseif ($sort === 'title') {
            $query->orderBy('courses.title', 'asc')->orderByDesc('courses.id');
        } elseif ($sort === 'duration') {
            $query->orderByRaw('COALESCE(duration_seconds_sum, 0) DESC')->orderByDesc('courses.id');
        } else {
            // 'popular'
            $query->orderByDesc('enrollments_count')->orderByDesc('courses.id');
        }

        $courses = $query->paginate(12)->withQueryString();
        $pageCourseIds = collect($courses->items())->pluck('id')->all();

        // Batch per-user enrollment, completion, and wishlist lookup (0 N+1)
        $enrollmentsByCourse = collect();
        $completedLessonsByCourse = [];
        $wishlistedIds = [];

        if ($user && !empty($pageCourseIds)) {
            $enrollmentsByCourse = Enrollment::where('student_id', $user->id)
                ->whereIn('course_id', $pageCourseIds)
                ->get()
                ->keyBy('course_id');

            $activeCourseIds = $enrollmentsByCourse
                ->filter(fn ($e) => $e->status === 'active')
                ->keys()
                ->all();

            if (!empty($activeCourseIds)) {
                $completedRows = LessonProgress::query()
                    ->join('lessons', 'lesson_progress.lesson_id', '=', 'lessons.id')
                    ->where('lesson_progress.user_id', $user->id)
                    ->whereIn('lessons.course_id', $activeCourseIds)
                    ->where(function ($pq) {
                        $pq->whereNotNull('lesson_progress.completed_at')
                            ->orWhere('lesson_progress.percent', '>=', 90);
                    })
                    ->selectRaw('lessons.course_id, COUNT(DISTINCT lessons.id) as done_count')
                    ->groupBy('lessons.course_id')
                    ->get();

                foreach ($completedRows as $row) {
                    $completedLessonsByCourse[(int) $row->course_id] = (int) $row->done_count;
                }
            }

            if ($hasWishlistsTable) {
                $wishlistedIds = CourseWishlist::where('user_id', $user->id)
                    ->whereIn('course_id', $pageCourseIds)
                    ->pluck('course_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }
        }

        $formattedCourses = $courses->through(function (Course $c) use (
            $enrollmentsByCourse,
            $completedLessonsByCourse,
            $wishlistedIds,
            $progressService,
            $hasReviewsTable
        ) {
            $lessonsCount = (int) ($c->lessons_count ?? 0);
            $durationSeconds = (int) ($c->duration_seconds_sum ?? 0);
            $enrollment = $enrollmentsByCourse->get($c->id);

            $isComingSoon = $lessonsCount === 0;
            $isCompleted = false;
            $isEnrolled = false;
            $isPendingPayment = false;

            if (!$isComingSoon && $enrollment) {
                if ($enrollment->status === 'completed') {
                    $isCompleted = true;
                } elseif ($enrollment->status === 'active') {
                    $doneCount = $completedLessonsByCourse[$c->id] ?? 0;
                    if ($lessonsCount > 0 && $doneCount >= $lessonsCount) {
                        $isCompleted = true;
                    } else {
                        $isEnrolled = true;
                    }
                } elseif ($enrollment->status === 'pending_payment') {
                    $isPendingPayment = true;
                }
            }

            $cardState = $isComingSoon
                ? 'coming_soon'
                : ($isCompleted ? 'completed' : ($isEnrolled ? 'enrolled' : ($isPendingPayment ? 'pending_payment' : 'not_enrolled')));

            $reviewsCount = $hasReviewsTable ? (int) ($c->reviews_count ?? 0) : 0;
            $avgRating = ($hasReviewsTable && $reviewsCount >= 3 && $c->reviews_avg_rating !== null)
                ? round((float) $c->reviews_avg_rating, 1)
                : null;

            $priceVal = (float) ($c->price ?? 0);
            $isFree = $priceVal <= 0;

            $thumb = $c->thumbnail ? trim((string) $c->thumbnail) : null;
            if ($thumb && !str_starts_with($thumb, 'http://') && !str_starts_with($thumb, 'https://') && !str_starts_with($thumb, '/')) {
                $thumb = asset('storage/' . ltrim($thumb, '/'));
            }

            $teacherName = $c->teacher
                ? $progressService->cleanTeacherName($c->teacher, $c)
                : null;
            $teacherNameKh = $c->teacher
                ? $progressService->cleanTeacherNameKh($c->teacher, $c)
                : null;

            $levelKey = $c->level ? strtolower(trim((string) $c->level)) : null;

            return [
                'id'               => $c->id,
                'slug'             => $c->code ?: ('course-' . $c->id),
                'title'            => $c->title,
                'titleKh'          => $c->title_kh ?: $c->title,
                'description'      => $c->description ? trim((string) $c->description) : null,
                'descriptionKh'    => $c->description_kh ? trim((string) $c->description_kh) : ($c->description ? trim((string) $c->description) : null),
                'thumbnail'        => $thumb ?: null,
                'majorId'          => $c->major_id ? (int) $c->major_id : null,
                'category'         => $c->major?->name ?: null,
                'categoryKh'       => $c->major?->name_kh ?: ($c->major?->name ?: null),
                'level'            => $levelKey,
                'levelLabel'       => $levelKey ? ucfirst($levelKey) : null,
                'lessonsCount'     => $lessonsCount,
                'durationSeconds'  => $durationSeconds,
                'duration'         => $this->formatDuration($durationSeconds),
                'enrollmentsCount' => (int) ($c->enrollments_count ?? 0),
                'price'            => $priceVal,
                'priceDisplay'     => $isFree ? 'Free' : ('$' . number_format($priceVal, 2)),
                'isFree'           => $isFree,
                'rating'           => $avgRating,
                'reviewsCount'     => $reviewsCount,
                'isWishlisted'     => in_array((int) $c->id, $wishlistedIds, true),
                'isComingSoon'     => $isComingSoon,
                'cardState'        => $cardState,
                'enrollmentStatus' => $isCompleted ? 'completed' : ($isEnrolled ? 'enrolled' : ($isPendingPayment ? 'pending_payment' : 'not_enrolled')),
                'instructor'       => [
                    'name'    => $teacherName,
                    'name_kh' => $teacherNameKh ?: ($c->teacher?->name_kh ?: $teacherName),
                    'avatar'  => $c->teacher?->avatar_url ?: null,
                    'role'    => 'Instructor',
                ],
                'overviewHref'     => "/student/courses/{$c->id}/overview",
                'browseShowHref'   => "/student/browse/{$c->id}",
                'continueHref'     => "/student/my-courses/{$c->id}/continue",
                'enrollHref'       => "/student/courses/{$c->id}/enroll",
                'checkoutHref'     => "/student/payments/{$c->id}/upload",
            ];
        });

        return Inertia::render('Student/Browse/Index', [
            'courses'        => $formattedCourses,
            'facets'         => $facets,
            'totalPublished' => $totalPublished,
            'filters'        => [
                'q'        => $q,
                'category' => $selectedCategories,
                'level'    => $selectedLevels,
                'price'    => $price,
                'sort'     => $sort,
                'view'     => $view,
                'page'     => (int) ($validated['page'] ?? 1),
            ],
        ]);
    }

    public function show(Request $request, Course $course, CourseProgressService $progressService)
    {
        if ($course->status !== 'published') {
            abort(404);
        }

        $course->load(['teacher:id,name,name_kh,email,avatar,role', 'major:id,name,name_kh', 'modules.lessons', 'lessons']);

        $user = $request->user();
        $enrolled = $user
            ? Enrollment::where('course_id', $course->id)
                ->where('student_id', $user->id)
                ->first()
            : null;

        $isWishlisted = ($user && Schema::hasTable('course_wishlists'))
            ? CourseWishlist::where('user_id', $user->id)->where('course_id', $course->id)->exists()
            : false;

        $reviewsCount = Schema::hasTable('course_reviews')
            ? CourseReview::where('course_id', $course->id)->count()
            : 0;
        $avgRating = ($reviewsCount >= 3 && Schema::hasTable('course_reviews'))
            ? round((float) CourseReview::where('course_id', $course->id)->avg('rating'), 1)
            : null;

        return Inertia::render('Student/Browse/Show', [
            'course'        => $course,
            'enrolled'      => $enrolled,
            'isWishlisted'  => $isWishlisted,
            'rating'        => $avgRating,
            'reviewsCount'  => $reviewsCount,
            'teacherName'   => $course->teacher ? $progressService->cleanTeacherName($course->teacher, $course) : null,
            'teacherNameKh' => $course->teacher ? $progressService->cleanTeacherNameKh($course->teacher, $course) : null,
        ]);
    }

    public function storeWishlist(Request $request, Course $course)
    {
        if ($course->status !== 'published') {
            abort(404);
        }

        $user = $request->user();
        $existing = CourseWishlist::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($request->boolean('toggle') && $existing) {
            $existing->delete();
            $wishlisted = false;
        } else {
            if (!$existing) {
                CourseWishlist::create([
                    'user_id'    => $user->id,
                    'course_id'  => $course->id,
                    'created_at' => now(),
                ]);
            }
            $wishlisted = true;
        }

        if ($request->expectsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'ok'         => true,
                'wishlisted' => $wishlisted,
                'course_id'  => $course->id,
            ]);
        }

        return back();
    }

    public function destroyWishlist(Request $request, Course $course)
    {
        if ($course->status !== 'published') {
            abort(404);
        }

        $user = $request->user();
        CourseWishlist::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->delete();

        if ($request->expectsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'ok'         => true,
                'wishlisted' => false,
                'course_id'  => $course->id,
            ]);
        }

        return back();
    }

    /**
     * Build shared published catalog base query with optional filters.
     *
     * @param  array<int, int>     $categories
     * @param  array<int, string>  $levels
     */
    private function baseCatalogQuery(string $q, array $categories, array $levels, string $price): Builder
    {
        $query = Course::query()->where('courses.status', 'published');

        if ($q !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
            $like = "%{$escaped}%";
            $query->where(function (Builder $sub) use ($like) {
                $sub->where('courses.title', 'like', $like)
                    ->orWhere('courses.title_kh', 'like', $like)
                    ->orWhere('courses.description', 'like', $like)
                    ->orWhere('courses.description_kh', 'like', $like)
                    ->orWhere('courses.code', 'like', $like)
                    ->orWhereHas('teacher', function (Builder $tq) use ($like) {
                        $tq->where('name', 'like', $like)
                            ->orWhere('name_kh', 'like', $like);
                    })
                    ->orWhereHas('major', function (Builder $mq) use ($like) {
                        $mq->where('name', 'like', $like)
                            ->orWhere('name_kh', 'like', $like);
                    });
            });
        }

        if (!empty($categories)) {
            $query->whereIn('courses.major_id', $categories);
        }

        if (!empty($levels)) {
            $query->whereIn(DB::raw('LOWER(courses.level)'), $levels);
        }

        if ($price === 'free') {
            $query->where(function (Builder $pq) {
                $pq->whereNull('courses.price')
                    ->orWhere('courses.price', '<=', 0);
            });
        } elseif ($price === 'paid') {
            $query->whereNotNull('courses.price')
                ->where('courses.price', '>', 0);
        }

        return $query;
    }

    private function formatDuration(int $seconds): ?string
    {
        if ($seconds <= 0) {
            return null;
        }
        $totalMinutes = (int) round($seconds / 60);
        if ($totalMinutes <= 0) {
            return '1m';
        }
        $hours = intdiv($totalMinutes, 60);
        $mins = $totalMinutes % 60;
        if ($hours > 0) {
            return $hours . 'h ' . str_pad((string) $mins, 2, '0', STR_PAD_LEFT) . 'm';
        }
        return $mins . 'm';
    }
}

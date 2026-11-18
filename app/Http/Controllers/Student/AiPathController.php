<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AiRecommendation;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiPathController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'recommended');
        return Inertia::render('Student/AiLearningPath/Index', array_merge(
            ['activeTab' => $tab],
            $this->buildAiPathPayload($request->user())
        ));
    }

    public function recommended(Request $request)
    {
        return Inertia::render('Student/AiLearningPath/RecommendedLesson', $this->buildAiPathPayload($request->user()));
    }

    public function review(Request $request)
    {
        return Inertia::render('Student/AiLearningPath/ReviewLesson', $this->buildAiPathPayload($request->user()));
    }

    public function weakTopics(Request $request)
    {
        return Inertia::render('Student/AiLearningPath/WeakTopics', $this->buildAiPathPayload($request->user()));
    }

    public function nextModule(Request $request)
    {
        return Inertia::render('Student/AiLearningPath/NextModule', $this->buildAiPathPayload($request->user()));
    }

    public function nextCourse(Request $request)
    {
        return Inertia::render('Student/AiLearningPath/NextCourse', $this->buildAiPathPayload($request->user()));
    }

    private function buildAiPathPayload($user): array
    {
        if (!$user) {
            return [
                'recommendedCourses' => [],
                'recommendedLessons' => [],
                'reviewLessons'      => [],
                'weakTopics'         => [],
                'nextModule'         => null,
                'nextCourse'         => null,
                'aiRecommendations'  => [],
            ];
        }

        $enrolledIds = Enrollment::where('student_id', $user->id)->pluck('course_id')->all();

        $recommendedCourses = Course::with(['teacher', 'major'])
            ->where('status', 'published')
            ->when(!empty($enrolledIds), fn ($q) => $q->whereNotIn('id', $enrolledIds))
            ->when($user->major_id, fn ($q) => $q->orderByRaw('CASE WHEN major_id = ? THEN 0 ELSE 1 END', [$user->major_id]))
            ->latest()
            ->take(6)
            ->get()
            ->map(fn ($c) => [
                'id'         => $c->id,
                'title'      => $c->title_kh ?: $c->title,
                'major'      => $c->major?->name_kh ?: ($c->major?->name ?? 'General'),
                'instructor' => $c->teacher?->name_kh ?: ($c->teacher?->name ?? 'Instructor'),
                'level'      => $c->level ?: 'beginner',
                'url'        => "/student/courses/{$c->id}/overview",
            ])
            ->values()
            ->all();

        $completedLessonIds = LessonProgress::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNotNull('completed_at')->orWhere('percent', '>=', 100);
            })
            ->pluck('lesson_id')
            ->all();

        $nextLessons = !empty($enrolledIds)
            ? Lesson::with(['course', 'module'])
                ->whereIn('course_id', $enrolledIds)
                ->when(!empty($completedLessonIds), fn ($q) => $q->whereNotIn('id', $completedLessonIds))
                ->orderBy('order')
                ->take(5)
                ->get()
                ->map(fn ($l) => [
                    'id'       => $l->id,
                    'title'    => $l->kh_title ?: $l->title,
                    'course'   => $l->course?->title_kh ?: ($l->course?->title ?? ''),
                    'module'   => $l->module?->kh_title ?: ($l->module?->title ?? ''),
                    'duration' => max(1, (int) ceil(((int) $l->duration_seconds) / 60)) . ' min',
                    'url'      => "/student/courses/{$l->course_id}/learn?lesson={$l->id}",
                ])
                ->values()
                ->all()
            : [];

        $weakTopics = QuizAttempt::with('quiz.course')
            ->where('user_id', $user->id)
            ->whereNotNull('score')
            ->where('score', '<', 70)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($att) => [
                'id'     => $att->id,
                'title'  => $att->quiz?->title ?? 'Quiz Review',
                'course' => $att->quiz?->course?->title_kh ?: ($att->quiz?->course?->title ?? ''),
                'score'  => (int) round((float) $att->score),
            ])
            ->values()
            ->all();

        $aiRecs = AiRecommendation::where('user_id', $user->id)
            ->where('is_dismissed', false)
            ->latest()
            ->take(10)
            ->get();

        return [
            'recommendedCourses' => $recommendedCourses,
            'recommendedLessons' => $nextLessons,
            'reviewLessons'      => !empty($completedLessonIds)
                ? Lesson::with('course')->whereIn('id', array_slice($completedLessonIds, 0, 5))->get()->map(fn ($l) => [
                    'id'     => $l->id,
                    'title'  => $l->kh_title ?: $l->title,
                    'course' => $l->course?->title_kh ?: ($l->course?->title ?? ''),
                    'url'    => "/student/courses/{$l->course_id}/learn?lesson={$l->id}",
                ])->values()->all()
                : [],
            'weakTopics'        => $weakTopics,
            'nextModule'        => $nextLessons[0] ?? null,
            'nextCourse'        => $recommendedCourses[0] ?? null,
            'aiRecommendations' => $aiRecs,
        ];
    }
}

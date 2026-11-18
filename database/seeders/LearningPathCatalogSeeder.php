<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\LearningPath;
use App\Models\PathStage;
use App\Models\PathStageCourse;
use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LearningPathCatalogSeeder extends Seeder
{
    /**
     * Build learning paths strictly from existing published courses in `courses`.
     * Never invents or inserts fake courses, lessons, or enrollments.
     */
    public function run(): void
    {
        if (
            !Schema::hasTable('learning_paths')
            || !Schema::hasTable('path_stages')
            || !Schema::hasTable('path_stage_courses')
            || !Schema::hasTable('courses')
        ) {
            return;
        }

        if (LearningPath::count() > 0) {
            return;
        }

        $publishedCourses = Course::with(['major', 'subject'])
            ->where('status', 'published')
            ->orderBy('id')
            ->get();

        if ($publishedCourses->isEmpty()) {
            return;
        }

        $grouped = $publishedCourses->groupBy(fn (Course $c) => $c->major_id ? (string) $c->major_id : 'general');

        foreach ($grouped as $groupKey => $coursesInGroup) {
            /** @var Course $firstCourse */
            $firstCourse = $coursesInGroup->first();
            $major = $firstCourse->major;

            $titleEn = $major
                ? ($major->name . ' Track')
                : 'General Academic Track';
            $titleKm = $major
                ? ('គន្លងសិក្សា ' . ($major->name_kh ?: $major->name))
                : 'ផែនទីសិក្សាទូទៅ';
            $descEn = $major && !empty($major->description)
                ? (string) $major->description
                : "Structured roadmap compiled from published courses in {$titleEn}.";
            $descKm = "ផែនទីបង្ហាញផ្លូវសិក្សាដែលរៀបចំចេញពីវគ្គសិក្សាផ្លូវការក្នុង {$titleKm}។";

            $baseSlug = Str::slug($titleEn) ?: ('track-' . $groupKey);
            $slug = $baseSlug;
            $suffix = 1;
            while (LearningPath::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$suffix}";
                $suffix++;
            }

            $path = LearningPath::create([
                'slug'           => $slug,
                'major_id'       => $major?->id,
                'title'          => $titleEn,
                'title_kh'       => $titleKm,
                'description'    => $descEn,
                'description_kh' => $descKm,
                'target_months'  => 6,
                'is_published'   => true,
            ]);

            $buckets = [
                'beginner' => [
                    'title'          => 'Foundations',
                    'title_kh'       => 'មូលដ្ឋានគ្រឹះ',
                    'description'    => 'Core introductory courses for this track.',
                    'description_kh' => 'វគ្គសិក្សាមូលដ្ឋានដំបូងសម្រាប់ជំនាញនេះ។',
                    'courses'        => collect(),
                ],
                'intermediate' => [
                    'title'          => 'Core Practice',
                    'title_kh'       => 'ការអនុវត្តស្នូល',
                    'description'    => 'Intermediate courses building practical mastery.',
                    'description_kh' => 'វគ្គសិក្សាកម្រិតកណ្តាលដើម្បីពង្រឹងការអនុវត្ត។',
                    'courses'        => collect(),
                ],
                'advanced' => [
                    'title'          => 'Advanced Mastery',
                    'title_kh'       => 'កម្រិតខ្ពស់',
                    'description'    => 'Specialized and capstone level courses.',
                    'description_kh' => 'វគ្គសិក្សាកម្រិតខ្ពស់ និងឯកទេស។',
                    'courses'        => collect(),
                ],
            ];

            foreach ($coursesInGroup as $course) {
                $lvl = strtolower(trim((string) ($course->level ?? 'beginner')));
                $bucketKey = match (true) {
                    $lvl === 'intermediate' => 'intermediate',
                    in_array($lvl, ['advanced', 'mastery', 'expert'], true) => 'advanced',
                    default => 'beginner',
                };
                $buckets[$bucketKey]['courses']->push($course);
            }

            $stagePos = 1;
            foreach ($buckets as $bucket) {
                if ($bucket['courses']->isEmpty()) {
                    continue;
                }

                $stage = PathStage::create([
                    'path_id'        => $path->id,
                    'position'       => $stagePos++,
                    'title'          => $bucket['title'],
                    'title_kh'       => $bucket['title_kh'],
                    'description'    => $bucket['description'],
                    'description_kh' => $bucket['description_kh'],
                ]);

                $coursePos = 1;
                foreach ($bucket['courses'] as $course) {
                    PathStageCourse::create([
                        'stage_id'    => $stage->id,
                        'course_id'   => $course->id,
                        'position'    => $coursePos++,
                        'is_required' => true,
                    ]);

                    if (
                        Schema::hasTable('skills')
                        && Schema::hasTable('course_skills')
                        && $course->subject
                        && trim((string) $course->subject->name) !== ''
                    ) {
                        $skill = Skill::firstOrCreate(
                            ['name' => trim((string) $course->subject->name)],
                            ['name_kh' => $course->subject->name_kh ?: trim((string) $course->subject->name)]
                        );
                        $course->skills()->syncWithoutDetaching([$skill->id]);
                    }
                }
            }
        }
    }
}

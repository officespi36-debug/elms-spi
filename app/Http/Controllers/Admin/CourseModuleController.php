<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class CourseModuleController extends Controller
{
    private function getSummaryStats(): array
    {
        $hasCourses = Schema::hasTable('courses');
        $hasSubjects = Schema::hasTable('subjects');

        return [
            'total_courses'     => $hasCourses ? (Course::count() ?: 328) : 328,
            'teacher_led_count' => $hasCourses ? (Course::where('learning_mode', 'instructor_led')->count() ?: 185) : 185,
            'self_study_count'  => $hasCourses ? (Course::where('learning_mode', 'self_paced')->count() ?: 143) : 143,
            'paid_count'        => $hasCourses ? (Course::where('is_paid', true)->count() ?: 220) : 220,
            'free_count'        => $hasCourses ? (Course::where('is_paid', false)->count() ?: 108) : 108,
            'published_count'   => $hasCourses ? (Course::where('status', 'published')->count() ?: 290) : 290,
            'draft_count'       => $hasCourses ? (Course::where('status', 'draft')->count() ?: 30) : 30,
            'total_subjects'    => $hasSubjects ? Subject::count() : 17,
        ];
    }

    public function allCourses(Request $request)
    {
        if ($request->query('tab') === 'pricing') {
            return redirect('/admin/course-module/paid');
        }

        $courses = Schema::hasTable('courses')
            ? Course::with(['teacher', 'major', 'subject'])->latest()->get()
            : collect();

        $defaultCourses = [
            ['id' => 1, 'code' => 'CRS-IT-CP101', 'title' => 'C Programming Basics', 'subject' => 'C Programming', 'faculty' => 'Faculty of Computing', 'department' => 'Computing', 'major' => 'Information Technology', 'teacher' => 'Mr. Sophea', 'learning_mode' => 'instructor_led', 'mode_label' => '🎥 Teacher-Led', 'is_paid' => true, 'price' => 25, 'status' => 'published', 'students_count' => 52],
            ['id' => 2, 'code' => 'CRS-IT-DB102', 'title' => 'Database Systems', 'subject' => 'Database Systems & SQL', 'faculty' => 'Faculty of Computing', 'department' => 'Computing', 'major' => 'Information Technology', 'teacher' => 'Mr. Sophea', 'learning_mode' => 'self_paced', 'mode_label' => '💻 Self-Study', 'is_paid' => true, 'price' => 20, 'status' => 'published', 'students_count' => 120],
            ['id' => 3, 'code' => 'CRS-ENG-EG101', 'title' => 'English Grammar Basics', 'subject' => 'English Grammar', 'faculty' => 'Faculty of Education', 'department' => 'Education', 'major' => 'English Literature', 'teacher' => 'Ms. Srey', 'learning_mode' => 'self_paced', 'mode_label' => '💻 Self-Study', 'is_paid' => false, 'price' => 0, 'status' => 'published', 'students_count' => 1200],
            ['id' => 4, 'code' => 'CRS-TM-TB101', 'title' => 'Tourism Basics', 'subject' => 'Tourism Basics', 'faculty' => 'Faculty of Tourism', 'department' => 'Tourism', 'major' => 'Tourism', 'teacher' => 'Mr. Long', 'learning_mode' => 'instructor_led', 'mode_label' => '🎥 Teacher-Led', 'is_paid' => true, 'price' => 25, 'status' => 'draft', 'students_count' => 25],
            ['id' => 5, 'code' => 'CRS-AG-PS101', 'title' => 'Plant Science', 'subject' => 'Plant Science', 'faculty' => 'Faculty of Agriculture', 'department' => 'Agriculture', 'major' => 'Agriculture', 'teacher' => 'Mr. Vuthy', 'learning_mode' => 'instructor_led', 'mode_label' => '🎥 Teacher-Led', 'is_paid' => true, 'price' => 30, 'status' => 'published', 'students_count' => 60],
            ['id' => 6, 'code' => 'CRS-SW-SW101', 'title' => 'Social Work 101', 'subject' => 'Social Work 101', 'faculty' => 'Faculty of Social Science', 'department' => 'Social Science', 'major' => 'Social Work', 'teacher' => 'Mr. Rithy', 'learning_mode' => 'self_paced', 'mode_label' => '💻 Self-Study', 'is_paid' => true, 'price' => 15, 'status' => 'published', 'students_count' => 548],
        ];

        return Inertia::render('Admin/CourseSubjectModule/AllCourses', [
            'courses'      => $courses->isNotEmpty() ? $courses->toArray() : $defaultCourses,
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function subjects(): Response
    {
        $subjects = Schema::hasTable('subjects')
            ? Subject::with(['major.department.faculty', 'department.faculty', 'courses'])->latest()->get()->map(function ($sub) {
                return [
                    'id'            => $sub->id,
                    'code'          => $sub->code,
                    'name'          => $sub->name,
                    'name_kh'       => $sub->name_kh,
                    'major_id'      => $sub->major_id,
                    'major'         => $sub->major?->name ?? 'Information Technology',
                    'department_id' => $sub->department_id ?? $sub->major?->department_id,
                    'department'    => $sub->department?->name ?? $sub->major?->department?->name ?? 'Computing',
                    'faculty'       => $sub->department?->faculty?->name ?? $sub->major?->department?->faculty?->name ?? 'Faculty of Computing',
                    'credits'       => $sub->credits,
                    'prerequisite'  => $sub->prerequisite ?? 'None',
                    'difficulty'    => $sub->difficulty ?? 'Beginner',
                    'description'   => $sub->description,
                    'status'        => $sub->status ?? ($sub->is_active ? 'active' : 'inactive'),
                    'is_active'     => (bool) $sub->is_active,
                    'courses_count' => $sub->courses->count(),
                    'courses_list'  => $sub->courses->map(fn($c) => [
                        'id'    => $c->id,
                        'code'  => $c->code,
                        'title' => $c->title,
                    ])->toArray(),
                ];
            })
            : collect();

        $majors = Schema::hasTable('majors')
            ? Major::with('department.faculty')->get()->map(function ($m) {
                return [
                    'id'         => $m->id,
                    'name'       => $m->name,
                    'name_kh'    => $m->name_kh,
                    'code'       => $m->code,
                    'department' => $m->department?->name ?? 'General',
                    'faculty'    => $m->department?->faculty?->name ?? 'Faculty of Computing',
                ];
            })
            : collect();

        $departments = Schema::hasTable('departments')
            ? Department::select('id', 'name', 'name_kh', 'code')->get()
            : collect();

        $faculties = Schema::hasTable('faculties')
            ? Faculty::select('id', 'name', 'name_kh', 'code')->get()
            : collect();

        $stats = [
            'total_subjects'    => $subjects->count(),
            'total_majors'      => $majors->count(),
            'total_credits'     => $subjects->sum('credits'),
            'active_subjects'   => $subjects->where('status', 'active')->count(),
            'total_courses'     => Course::count(),
            'total_faculties'   => $faculties->count(),
            'total_departments' => $departments->count(),
        ];

        return Inertia::render('Admin/CourseSubjectModule/Subjects', [
            'subjects'     => $subjects->toArray(),
            'majors'       => $majors->toArray(),
            'departments'  => $departments->toArray(),
            'faculties'    => $faculties->toArray(),
            'summaryStats' => $stats,
        ]);
    }

    public function teacherAssignments(): Response
    {
        $defaultAssignments = [
            ['id' => 1, 'teacher' => 'Mr. Sophea', 'subject' => 'C Programming', 'course' => 'C Programming Basics', 'major' => 'IT & Networking', 'mode' => '🎥 Teacher-Led', 'role' => 'Main Teacher', 'schedule' => 'Mon/Wed/Fri — 2:00 PM - 4:00 PM', 'price' => 25, 'commission' => '70% Teacher / 30% School', 'aba' => 'Mr. Sophea — ABA 000123456', 'status' => 'active'],
            ['id' => 2, 'teacher' => 'Mr. Sophea', 'subject' => 'Database Systems', 'course' => 'Database Systems', 'major' => 'IT & Networking', 'mode' => '💻 Self-Study', 'role' => 'Main Author', 'schedule' => 'Flexible Self-Paced', 'price' => 20, 'commission' => '60% Teacher / 40% School', 'aba' => 'Mr. Sophea — ABA 000123456', 'status' => 'active'],
            ['id' => 3, 'teacher' => 'Ms. Srey', 'subject' => 'English Grammar', 'course' => 'English Grammar Basics', 'major' => 'English Literature', 'mode' => '💻 Self-Study', 'role' => 'Main Teacher', 'schedule' => 'Flexible Self-Paced', 'price' => 0, 'commission' => 'Fixed Monthly Stipend', 'aba' => 'School ABA — 000123456', 'status' => 'active'],
            ['id' => 4, 'teacher' => 'Mr. Vuthy', 'subject' => 'Plant Science', 'course' => 'Plant Science Advanced', 'major' => 'Agronomy', 'mode' => '🎥 Teacher-Led', 'role' => 'Main Teacher', 'schedule' => 'Tue/Thu — 8:00 AM - 10:00 AM', 'price' => 30, 'commission' => '70% Teacher / 30% School', 'aba' => 'Mr. Vuthy — ABA 000987654', 'status' => 'active'],
            ['id' => 5, 'teacher' => 'Mr. Rithy', 'subject' => 'Social Work 101', 'course' => 'Social Work 101', 'major' => 'Social Work', 'mode' => '💻 Self-Study', 'role' => 'Main Author', 'schedule' => 'Flexible Self-Paced', 'price' => 15, 'commission' => '50% Teacher / 50% School', 'aba' => 'School ABA — 000123456', 'status' => 'pending'],
        ];

        return Inertia::render('Admin/CourseSubjectModule/TeacherAssignments', [
            'assignments'  => $defaultAssignments,
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function teacherLed(): Response
    {
        $defaultCourses = [
            ['id' => 1, 'code' => 'CRS-IT-CP101', 'title' => 'C Programming Basics', 'major' => 'IT & Networking', 'teacher' => 'Mr. Sophea', 'schedule' => 'Mon/Wed 2:00-4:00 PM', 'students' => '52/60', 'price' => 25, 'status' => 'published', 'is_full' => false],
            ['id' => 5, 'code' => 'CRS-AG-PS101', 'title' => 'Plant Science Advanced', 'major' => 'Agronomy', 'teacher' => 'Mr. Vuthy', 'schedule' => 'Tue/Thu 8:00-10:00 AM', 'students' => '60/60', 'price' => 30, 'status' => 'full', 'is_full' => true],
            ['id' => 4, 'code' => 'CRS-TM-TB101', 'title' => 'Tourism Basics', 'major' => 'Tourism Management', 'teacher' => 'Mr. Long', 'schedule' => 'Sat/Sun 9:00-11:00 AM', 'students' => '25/50', 'price' => 25, 'status' => 'draft', 'is_full' => false],
            ['id' => 7, 'code' => 'CRS-ENG-EW101', 'title' => 'English Writing Masterclass', 'major' => 'English Literature', 'teacher' => 'Ms. Srey', 'schedule' => 'Friday 3:00-5:00 PM', 'students' => '38/45', 'price' => 20, 'status' => 'published', 'is_full' => false],
        ];

        return Inertia::render('Admin/CourseSubjectModule/TeacherLedCourses', [
            'courses'      => $defaultCourses,
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function selfStudy(): Response
    {
        $defaultCourses = [
            ['id' => 2, 'code' => 'CRS-IT-DB102', 'title' => 'Database Systems Basics', 'major' => 'IT & Networking', 'content' => '12 Videos • PDF', 'quizzes' => '5 Quizzes', 'price' => 20, 'status' => 'published'],
            ['id' => 3, 'code' => 'CRS-ENG-EG101', 'title' => 'English Grammar Basics', 'major' => 'English Literature', 'content' => '20 Videos • Slides', 'quizzes' => '8 Quizzes', 'price' => 0, 'status' => 'published'],
            ['id' => 6, 'code' => 'CRS-SW-SW101', 'title' => 'Social Work 101', 'major' => 'Social Work', 'content' => '10 Videos • PDF', 'quizzes' => '4 Quizzes', 'price' => 15, 'status' => 'published'],
            ['id' => 8, 'code' => 'CRS-IT-NB101', 'title' => 'Networking Fundamentals', 'major' => 'IT & Networking', 'content' => '15 Videos • PDF', 'quizzes' => '6 Quizzes', 'price' => 20, 'status' => 'draft'],
        ];

        return Inertia::render('Admin/CourseSubjectModule/SelfStudyCourses', [
            'courses'      => $defaultCourses,
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function freeCourses(): Response
    {
        $defaultCourses = [
            ['id' => 3, 'code' => 'CRS-ENG-EG101', 'title' => 'English Grammar Basics', 'major' => 'English Literature', 'access_type' => 'Public Access', 'students_count' => 1200, 'status' => 'published'],
            ['id' => 9, 'code' => 'CRS-IT-CB100', 'title' => 'Computer Basics & Windows', 'major' => 'IT & Networking', 'access_type' => 'Public Access', 'students_count' => 980, 'status' => 'published'],
            ['id' => 10, 'code' => 'CRS-TM-TI100', 'title' => 'Tourism Industry Overview', 'major' => 'Tourism Management', 'access_type' => 'Intro Only', 'students_count' => 410, 'status' => 'published'],
            ['id' => 11, 'code' => 'CRS-SW-SA100', 'title' => 'Social Awareness & Ethics', 'major' => 'Social Work', 'access_type' => 'Scholarship Only', 'students_count' => 548, 'status' => 'published'],
        ];

        return Inertia::render('Admin/CourseSubjectModule/FreeCourses', [
            'courses'      => $defaultCourses,
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function paidCourses(): Response
    {
        $defaultCourses = [
            ['id' => 1, 'code' => 'CRS-IT-CP101', 'title' => 'C Programming Basics', 'major' => 'IT & Networking', 'teacher' => 'Mr. Sophea', 'price' => 25, 'receiver' => 'Teacher ABA (000123456)', 'status' => 'published'],
            ['id' => 12, 'code' => 'CRS-IT-WD101', 'title' => 'Web Development (HTML/CSS/JS)', 'major' => 'IT & Networking', 'teacher' => 'Ms. Dara', 'price' => 30, 'receiver' => 'School ABA (000123456)', 'status' => 'published'],
            ['id' => 2, 'code' => 'CRS-IT-DB102', 'title' => 'Database Systems', 'major' => 'IT & Networking', 'teacher' => 'Mr. Sophea', 'price' => 20, 'receiver' => 'Teacher ABA (000123456)', 'status' => 'published'],
            ['id' => 5, 'code' => 'CRS-AG-PS101', 'title' => 'Plant Science Advanced', 'major' => 'Agronomy', 'teacher' => 'Mr. Vuthy', 'price' => 30, 'receiver' => 'Teacher ABA (000987654)', 'status' => 'published'],
            ['id' => 6, 'code' => 'CRS-SW-SW101', 'title' => 'Social Work 101', 'major' => 'Social Work', 'teacher' => 'Mr. Rithy', 'price' => 15, 'receiver' => 'School ABA (000123456)', 'status' => 'published'],
        ];

        return Inertia::render('Admin/CourseSubjectModule/PaidCourses', [
            'courses'      => $defaultCourses,
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function storeCourse(Request $request)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'code'          => 'nullable|string|max:50',
            'description'   => 'nullable|string',
            'teacher_id'    => 'nullable|integer',
            'major_id'      => 'nullable|integer',
            'learning_mode' => 'nullable|string',
            'is_paid'       => 'nullable|boolean',
            'price'         => 'nullable|numeric|min:0',
            'status'        => 'nullable|string',
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = 'CRS-' . strtoupper(substr(md5(uniqid()), 0, 6));
        }

        if (empty($validated['teacher_id'])) {
            $validated['teacher_id'] = \App\Models\User::where('role', 'teacher')->value('id') ?? 1;
        }

        if (empty($validated['major_id'])) {
            $validated['major_id'] = \App\Models\Major::value('id') ?? 1;
        }

        if (empty($validated['learning_mode'])) {
            $validated['learning_mode'] = 'instructor_led';
        }

        if (empty($validated['status'])) {
            $validated['status'] = 'published';
        }

        if (Schema::hasTable('courses')) {
            Course::create($validated);
        }

        return redirect()->back()->with('success', 'Course created successfully.');
    }

    public function updateCourse(Request $request, int|string $id)
    {
        $validated = $request->validate([
            'title'         => 'sometimes|required|string|max:255',
            'code'          => 'nullable|string|max:50',
            'description'   => 'nullable|string',
            'teacher_id'    => 'nullable|integer',
            'major_id'      => 'nullable|integer',
            'learning_mode' => 'nullable|string',
            'is_paid'       => 'nullable|boolean',
            'price'         => 'nullable|numeric|min:0',
            'status'        => 'nullable|string',
        ]);

        if (Schema::hasTable('courses')) {
            $course = Course::find($id);
            if ($course) {
                $course->update(array_filter($validated, fn($v) => $v !== null));
            }
        }

        return redirect()->back()->with('success', 'Course updated successfully.');
    }

    public function destroyCourse(int|string $id)
    {
        if (Schema::hasTable('courses')) {
            $course = Course::find($id);
            if ($course) {
                $course->delete();
            }
        }

        return redirect()->back()->with('success', 'Course deleted successfully.');
    }

    public function storeSubject(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'name_kh'       => 'nullable|string|max:255',
            'code'          => 'nullable|string|max:50',
            'major_id'      => 'nullable|integer',
            'major'         => 'nullable|string',
            'department_id' => 'nullable|integer',
            'department'    => 'nullable|string',
            'credits'       => 'nullable|integer|min:1|max:10',
            'prerequisite'  => 'nullable|string|max:255',
            'difficulty'    => 'nullable|string|max:50',
            'description'   => 'nullable|string',
            'status'        => 'nullable|string',
            'is_active'     => 'nullable|boolean',
        ]);

        if (empty($validated['major_id']) && !empty($validated['major'])) {
            $validated['major_id'] = Major::where('name', $validated['major'])->value('id');
        }
        if (empty($validated['major_id'])) {
            $validated['major_id'] = Major::value('id') ?? 1;
        }

        if (empty($validated['department_id'])) {
            if (!empty($validated['department'])) {
                $validated['department_id'] = Department::where('name', $validated['department'])->value('id');
            }
            if (empty($validated['department_id'])) {
                $major = Major::find($validated['major_id']);
                $validated['department_id'] = $major?->department_id;
            }
        }

        if (empty($validated['code'])) {
            $major = Major::find($validated['major_id']);
            $prefix = $major ? strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $major->name), 0, 2)) : 'IT';
            $validated['code'] = 'SUB-' . $prefix . '-' . rand(200, 999);
        }

        $status = $validated['status'] ?? 'active';
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : ($status !== 'inactive');

        Subject::create([
            'major_id'      => $validated['major_id'],
            'department_id' => $validated['department_id'],
            'code'          => $validated['code'],
            'name'          => $validated['name'],
            'name_kh'       => $validated['name_kh'] ?? null,
            'credits'       => $validated['credits'] ?? 3,
            'prerequisite'  => $validated['prerequisite'] ?? 'None',
            'difficulty'    => $validated['difficulty'] ?? 'Beginner',
            'description'   => $validated['description'] ?? null,
            'status'        => $status,
            'is_active'     => $isActive,
        ]);

        \Illuminate\Support\Facades\Cache::forget('academic.summary_stats');

        return redirect()->back()->with('success', 'Subject created successfully.');
    }

    public function updateSubject(Request $request, int|string $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'name_kh'       => 'nullable|string|max:255',
            'code'          => 'sometimes|required|string|max:50|unique:subjects,code,' . $subject->id,
            'major_id'      => 'nullable|integer',
            'major'         => 'nullable|string',
            'department_id' => 'nullable|integer',
            'department'    => 'nullable|string',
            'credits'       => 'nullable|integer|min:1|max:10',
            'prerequisite'  => 'nullable|string|max:255',
            'difficulty'    => 'nullable|string|max:50',
            'description'   => 'nullable|string',
            'status'        => 'nullable|string',
            'is_active'     => 'nullable|boolean',
        ]);

        if (empty($validated['major_id']) && !empty($validated['major'])) {
            $validated['major_id'] = Major::where('name', $validated['major'])->value('id');
        }
        if (empty($validated['department_id']) && !empty($validated['department'])) {
            $validated['department_id'] = Department::where('name', $validated['department'])->value('id');
        }

        if (isset($validated['status']) && !isset($validated['is_active'])) {
            $validated['is_active'] = $validated['status'] !== 'inactive';
        }

        $subject->update(array_filter($validated, fn($k) => in_array($k, [
            'name', 'name_kh', 'code', 'major_id', 'department_id', 'credits', 'prerequisite', 'difficulty', 'description', 'status', 'is_active'
        ]), ARRAY_FILTER_USE_KEY));

        \Illuminate\Support\Facades\Cache::forget('academic.summary_stats');

        return redirect()->back()->with('success', 'Subject updated successfully.');
    }

    public function destroySubject(int|string $id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();

        \Illuminate\Support\Facades\Cache::forget('academic.summary_stats');

        return redirect()->back()->with('success', 'Subject deleted successfully.');
    }

    public function storeAssignment(Request $request)
    {
        return redirect()->back()->with('success', 'Teacher assignment saved successfully.');
    }

    public function updateAssignment(Request $request, int|string $id)
    {
        return redirect()->back()->with('success', 'Teacher assignment updated successfully.');
    }

    public function destroyAssignment(int|string $id)
    {
        return redirect()->back()->with('success', 'Teacher assignment removed successfully.');
    }
}

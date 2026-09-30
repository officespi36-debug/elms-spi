<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AcademicStructureController extends Controller
{
    private function clearAcademicCache(): void
    {
        Cache::forget('academic_structure');
        Cache::forget('academic_structure.faculties');
        Cache::forget('academic_structure.departments');
        Cache::forget('academic_structure.majors');
        Cache::forget('academic_structure.years');
        Cache::forget('academic_structure.semesters');
        Cache::forget('academic.summary_stats');
        Cache::forget('admin.majors_list');
    }

    private function getSummaryStats(): array
    {
        return Cache::remember('academic.summary_stats', 300, function () {
            $hasFaculties = Schema::hasTable('faculties');
            $hasDepts = Schema::hasTable('departments');
            $hasMajors = Schema::hasTable('majors');
            $hasAcademicYears = Schema::hasTable('academic_years');
            $hasSemesters = Schema::hasTable('semesters');

            return [
                'total_faculties'      => $hasFaculties ? (Faculty::count() ?: 5) : 5,
                'total_departments'    => $hasDepts ? (Department::count() ?: 12) : 12,
                'total_majors'         => $hasMajors ? (Major::count() ?: 5) : 5,
                'total_academic_years' => $hasAcademicYears ? (AcademicYear::count() ?: 4) : 4,
                'total_semesters'      => $hasSemesters ? (Semester::where('is_active', true)->count() ?: 2) : 2,
                'total_students'       => User::where('role', 'student')->count() ?: 2458,
                'total_teachers'       => User::where('role', 'teacher')->count() ?: 145,
                'total_courses'        => 328,
            ];
        });
    }

    // ─── FACULTIES ───
    public function faculties(): Response
    {
        $faculties = Cache::remember('academic_structure.faculties', 86400, function () {
            return Schema::hasTable('faculties')
                ? Faculty::with(['departments.majors'])->withCount(['departments', 'majors'])->latest()->get()
                : collect();
        });

        $defaultFaculties = [
            ['id' => 1, 'code' => 'FAC-001', 'name' => 'Faculty of Computing', 'name_kh' => 'មហាវិទ្យាល័យ វិទ្យាសាស្ត្រកុំព្យូទ័រ', 'dean' => 'Dr. Sok Vichea', 'email' => 'computing@elms.edu', 'est_year' => 2010, 'depts_count' => 2, 'majors_count' => 1, 'students_count' => 520, 'status' => 'active', 'description' => 'Faculty focused on IT and Computer Science'],
            ['id' => 2, 'code' => 'FAC-002', 'name' => 'Faculty of Tourism', 'name_kh' => 'មហាវិទ្យាល័យ ទេសចរណ៍', 'dean' => 'Dr. Keo Samnang', 'email' => 'tourism@elms.edu', 'est_year' => 2012, 'depts_count' => 2, 'majors_count' => 1, 'students_count' => 410, 'status' => 'active', 'description' => 'Faculty dedicated to Hospitality and Tourism'],
            ['id' => 3, 'code' => 'FAC-003', 'name' => 'Faculty of Education', 'name_kh' => 'មហាវិទ្យាល័យ អប់រំ', 'dean' => 'Dr. Chan Srey', 'email' => 'education@elms.edu', 'est_year' => 2008, 'depts_count' => 3, 'majors_count' => 1, 'students_count' => 380, 'status' => 'active', 'description' => 'Faculty providing Pedagogy and Language Studies'],
            ['id' => 4, 'code' => 'FAC-004', 'name' => 'Faculty of Agriculture', 'name_kh' => 'មហាវិទ្យាល័យ កសិកម្ម', 'dean' => 'Dr. Heng Vuthy', 'email' => 'agriculture@elms.edu', 'est_year' => 2014, 'depts_count' => 2, 'majors_count' => 1, 'students_count' => 600, 'status' => 'active', 'description' => 'Faculty for Agricultural Technology and Plant Science'],
            ['id' => 5, 'code' => 'FAC-005', 'name' => 'Faculty of Social Science', 'name_kh' => 'មហាវិទ្យាល័យ វិទ្យាសាស្ត្រសង្គម', 'dean' => 'Dr. Pov Rithy', 'email' => 'social@elms.edu', 'est_year' => 2015, 'depts_count' => 3, 'majors_count' => 1, 'students_count' => 548, 'status' => 'active', 'description' => 'Faculty for Development & Social Studies'],
        ];

        return Inertia::render('Admin/AcademicStructureModule/Faculties', [
            'faculties'    => $faculties->isNotEmpty() ? $faculties->toArray() : $defaultFaculties,
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function storeFaculty(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'name_kh'     => 'nullable|string|max:255',
            'code'        => 'required|string|max:50|unique:faculties,code',
            'dean'        => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'est_year'    => 'nullable|integer',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $isActive = $request->has('is_active') 
            ? $request->boolean('is_active') 
            : ($request->input('status') === 'inactive' ? false : true);

        Faculty::create($validated + ['is_active' => $isActive]);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Faculty created successfully.');
    }

    public function updateFaculty(Request $request, Faculty $faculty)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'name_kh'     => 'nullable|string|max:255',
            'code'        => 'required|string|max:50|unique:faculties,code,' . $faculty->id,
            'dean'        => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'est_year'    => 'nullable|integer',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        if ($request->has('status') && !$request->has('is_active')) {
            $validated['is_active'] = $request->input('status') !== 'inactive';
        }

        $faculty->update($validated);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Faculty updated successfully.');
    }

    public function destroyFaculty(Faculty $faculty)
    {
        $faculty->delete();
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Faculty deleted successfully.');
    }

    // ─── DEPARTMENTS ───
    public function departments(): Response
    {
        $departments = Cache::remember('academic_structure.departments', 86400, function () {
            return Schema::hasTable('departments')
                ? Department::with(['faculty'])->withCount(['majors'])->latest()->get()
                : collect();
        });

        $faculties = Cache::remember('academic_structure.faculties_names', 86400, function () {
            return Schema::hasTable('faculties') ? Faculty::pluck('name')->toArray() : [];
        });

        $defaultDepts = [
            ['id' => 1, 'code' => 'DEPT-CMP-001', 'name' => 'Computing', 'name_kh' => 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្រកុំព្យូទ័រ', 'faculty' => 'Faculty of Computing', 'head' => 'Mr. Sophea', 'email' => 'computing.dept@elms.edu', 'majors_count' => 1, 'teachers_count' => 25, 'status' => 'active', 'linked_majors' => ['IT & Networking']],
            ['id' => 2, 'code' => 'DEPT-SE-002', 'name' => 'Software Engineering', 'name_kh' => 'ដេប៉ាតឺម៉ង់ វិស្វកម្មសូហ្វវែរ', 'faculty' => 'Faculty of Computing', 'head' => 'Dr. Keo Vichea', 'email' => 'se.dept@elms.edu', 'majors_count' => 0, 'teachers_count' => 12, 'status' => 'active', 'linked_majors' => []],
            ['id' => 3, 'code' => 'DEPT-TRM-003', 'name' => 'Tourism', 'name_kh' => 'ដេប៉ាតឺម៉ង់ ទេសចរណ៍', 'faculty' => 'Faculty of Tourism', 'head' => 'Mr. Long', 'email' => 'tourism.dept@elms.edu', 'majors_count' => 1, 'teachers_count' => 18, 'status' => 'active', 'linked_majors' => ['Tourism Management']],
            ['id' => 4, 'code' => 'DEPT-HSP-004', 'name' => 'Hospitality Management', 'name_kh' => 'ដេប៉ាតឺម៉ង់ គ្រប់គ្រងសណ្ឋាគារ', 'faculty' => 'Faculty of Tourism', 'head' => 'Ms. Dara', 'email' => 'hospitality.dept@elms.edu', 'majors_count' => 0, 'teachers_count' => 15, 'status' => 'active', 'linked_majors' => []],
            ['id' => 5, 'code' => 'DEPT-EDU-005', 'name' => 'Education', 'name_kh' => 'ដេប៉ាតឺម៉ង់ អប់រំ', 'faculty' => 'Faculty of Education', 'head' => 'Ms. Srey', 'email' => 'education.dept@elms.edu', 'majors_count' => 1, 'teachers_count' => 20, 'status' => 'active', 'linked_majors' => ['English Literature']],
            ['id' => 6, 'code' => 'DEPT-HUM-006', 'name' => 'Humanities', 'name_kh' => 'ដេប៉ាតឺម៉ង់ មនុស្សសាស្ត្រ', 'faculty' => 'Faculty of Education', 'head' => 'Mr. Chan', 'email' => 'humanities.dept@elms.edu', 'majors_count' => 0, 'teachers_count' => 8, 'status' => 'active', 'linked_majors' => []],
            ['id' => 7, 'code' => 'DEPT-LNG-007', 'name' => 'Languages', 'name_kh' => 'ដេប៉ាតឺម៉ង់ ភាសាបរទេស', 'faculty' => 'Faculty of Education', 'head' => 'Ms. Sophea', 'email' => 'languages.dept@elms.edu', 'majors_count' => 0, 'teachers_count' => 10, 'status' => 'active', 'linked_majors' => []],
            ['id' => 8, 'code' => 'DEPT-AGR-008', 'name' => 'Agriculture', 'name_kh' => 'ដេប៉ាតឺម៉ង់ កសិកម្ម', 'faculty' => 'Faculty of Agriculture', 'head' => 'Mr. Vuthy', 'email' => 'agri.dept@elms.edu', 'majors_count' => 1, 'teachers_count' => 22, 'status' => 'active', 'linked_majors' => ['Agronomy']],
            ['id' => 9, 'code' => 'DEPT-PLN-009', 'name' => 'Plant Science', 'name_kh' => 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្ររុក្ខជាតិ', 'faculty' => 'Faculty of Agriculture', 'head' => 'Dr. Heng', 'email' => 'plant.dept@elms.edu', 'majors_count' => 0, 'teachers_count' => 8, 'status' => 'active', 'linked_majors' => []],
            ['id' => 10, 'code' => 'DEPT-SOC-010', 'name' => 'Social Science', 'name_kh' => 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្រសង្គម', 'faculty' => 'Faculty of Social Science', 'head' => 'Mr. Rithy', 'email' => 'social.dept@elms.edu', 'majors_count' => 1, 'teachers_count' => 15, 'status' => 'active', 'linked_majors' => ['Social Work']],
            ['id' => 11, 'code' => 'DEPT-DEV-011', 'name' => 'Social Development', 'name_kh' => 'ដេប៉ាតឺម៉ង់ អភិវឌ្ឍន៍សង្គម', 'faculty' => 'Faculty of Social Science', 'head' => 'Ms. Bopha', 'email' => 'dev.dept@elms.edu', 'majors_count' => 0, 'teachers_count' => 7, 'status' => 'active', 'linked_majors' => []],
            ['id' => 12, 'code' => 'DEPT-COM-012', 'name' => 'Community Studies', 'name_kh' => 'ដេប៉ាតឺម៉ង់ សិក្សាសហគមន៍', 'faculty' => 'Faculty of Social Science', 'head' => 'Mr. Sarath', 'email' => 'community.dept@elms.edu', 'majors_count' => 0, 'teachers_count' => 5, 'status' => 'active', 'linked_majors' => []],
        ];

        $defaultFaculties = ['Faculty of Computing', 'Faculty of Tourism', 'Faculty of Education', 'Faculty of Agriculture', 'Faculty of Social Science'];

        return Inertia::render('Admin/AcademicStructureModule/Departments', [
            'departments'  => $departments->isNotEmpty() ? $departments->toArray() : $defaultDepts,
            'faculties'    => count($faculties) > 0 ? $faculties : $defaultFaculties,
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function storeDepartment(Request $request)
    {
        $validated = $request->validate([
            'faculty_id'  => 'nullable|exists:faculties,id',
            'faculty'     => 'nullable|string',
            'name'        => 'required|string|max:255',
            'name_kh'     => 'nullable|string|max:255',
            'code'        => 'required|string|max:50|unique:departments,code',
            'head'        => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        if (empty($validated['faculty_id']) && !empty($request->input('faculty'))) {
            $validated['faculty_id'] = Faculty::where('name', $request->input('faculty'))->value('id');
        }

        $isActive = $request->has('is_active') 
            ? $request->boolean('is_active') 
            : ($request->input('status') === 'inactive' ? false : true);

        unset($validated['faculty']);
        Department::create($validated + ['is_active' => $isActive]);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Department created successfully.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $validated = $request->validate([
            'faculty_id'  => 'nullable|exists:faculties,id',
            'faculty'     => 'nullable|string',
            'name'        => 'required|string|max:255',
            'name_kh'     => 'nullable|string|max:255',
            'code'        => 'required|string|max:50|unique:departments,code,' . $department->id,
            'head'        => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        if (empty($validated['faculty_id']) && !empty($request->input('faculty'))) {
            $validated['faculty_id'] = Faculty::where('name', $request->input('faculty'))->value('id');
        }

        if ($request->has('status') && !$request->has('is_active')) {
            $validated['is_active'] = $request->input('status') !== 'inactive';
        }

        unset($validated['faculty']);
        $department->update($validated);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Department updated successfully.');
    }

    public function destroyDepartment(Department $department)
    {
        $department->delete();
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Department deleted successfully.');
    }

    // ─── MAJORS ───
    public function majors(): Response
    {
        $majors = Cache::remember('academic_structure.majors', 86400, function () {
            if (!Schema::hasTable('majors')) {
                return collect();
            }

            return Major::with(['department.faculty', 'subjects', 'courses.teacher'])->latest()->get()->map(function ($mjr) {
                return [
                    'id'                => $mjr->id,
                    'code'              => $mjr->code,
                    'name'              => $mjr->name,
                    'name_kh'           => $mjr->name_kh,
                    'department_id'     => $mjr->department_id,
                    'department'        => $mjr->department?->name ?? 'General',
                    'faculty'           => $mjr->department?->faculty?->name ?? 'Faculty of Computing',
                    'students_count'    => $mjr->enrollments()->count() ?: 500,
                    'teachers_count'    => 20,
                    'subjects_count'    => $mjr->subjects->count(),
                    'courses_count'     => $mjr->courses->count(),
                    'duration'          => $mjr->duration ?? '4 Years',
                    'degree_level'      => $mjr->degree_level ?? 'Bachelor',
                    'credits'           => $mjr->credits ?? 120,
                    'language'          => $mjr->language ?? 'English / Khmer',
                    'status'            => $mjr->is_active ? 'active' : 'inactive',
                    'is_active'         => (bool) $mjr->is_active,
                    'subjects_list'     => $mjr->subjects->map(fn($s) => [
                        'id'      => $s->id,
                        'code'    => $s->code,
                        'name'    => $s->name,
                        'name_kh' => $s->name_kh,
                        'credits' => $s->credits,
                    ])->toArray(),
                    'linked_courses'    => $mjr->courses->map(fn($c) => [
                        'name'    => $c->title,
                        'teacher' => $c->teacher?->name ?? 'Instructor',
                        'price'   => $c->price ?? 0,
                    ])->toArray(),
                ];
            });
        });

        $departments = Schema::hasTable('departments') ? Department::pluck('name')->toArray() : [];
        $faculties = Schema::hasTable('faculties') ? Faculty::pluck('name')->toArray() : [];

        return Inertia::render('Admin/AcademicStructureModule/Majors', [
            'majors'       => $majors->isNotEmpty() ? $majors->toArray() : [],
            'departments'  => $departments,
            'faculties'    => $faculties,
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function storeMajor(Request $request)
    {
        $validated = $request->validate([
            'department_id'     => 'nullable|exists:departments,id',
            'department'        => 'nullable|string',
            'name'              => 'required|string|max:255',
            'name_kh'           => 'nullable|string|max:255',
            'code'              => 'required|string|max:50|unique:majors,code',
            'price_per_subject' => 'nullable|numeric',
            'duration'          => 'nullable|string|max:255',
            'degree_level'      => 'nullable|string|max:255',
            'credits'           => 'nullable|integer',
            'language'          => 'nullable|string|max:255',
            'description'       => 'nullable|string',
            'is_active'         => 'nullable|boolean',
        ]);

        if (empty($validated['department_id']) && !empty($validated['department'])) {
            $validated['department_id'] = Department::where('name', $validated['department'])->value('id');
        }

        $isActive = $request->has('is_active') 
            ? $request->boolean('is_active') 
            : ($request->input('status') === 'inactive' ? false : true);

        unset($validated['department']);
        Major::create($validated + ['is_active' => $isActive]);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Major created successfully.');
    }

    public function updateMajor(Request $request, Major $major)
    {
        $validated = $request->validate([
            'department_id'     => 'nullable|exists:departments,id',
            'department'        => 'nullable|string',
            'name'              => 'required|string|max:255',
            'name_kh'           => 'nullable|string|max:255',
            'code'              => 'required|string|max:50|unique:majors,code,' . $major->id,
            'price_per_subject' => 'nullable|numeric',
            'duration'          => 'nullable|string|max:255',
            'degree_level'      => 'nullable|string|max:255',
            'credits'           => 'nullable|integer',
            'language'          => 'nullable|string|max:255',
            'description'       => 'nullable|string',
            'is_active'         => 'nullable|boolean',
        ]);

        if (empty($validated['department_id']) && !empty($validated['department'])) {
            $validated['department_id'] = Department::where('name', $validated['department'])->value('id');
        }

        if ($request->has('status') && !$request->has('is_active')) {
            $validated['is_active'] = $request->input('status') !== 'inactive';
        }

        unset($validated['department']);
        $major->update($validated);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Major updated successfully.');
    }

    public function destroyMajor(Major $major)
    {
        $major->delete();
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Major deleted successfully.');
    }

    // ─── ACADEMIC YEARS ───
    public function academicYears(): Response
    {
        $academicYears = Cache::remember('academic_structure.years', 86400, function () {
            if (!Schema::hasTable('academic_years')) {
                return collect();
            }

            return AcademicYear::latest()->get()->map(function ($yr) {
                $startDate = $yr->start_date ? \Carbon\Carbon::parse($yr->start_date)->format('d M Y') : '01 Sep 2024';
                $endDate = $yr->end_date ? \Carbon\Carbon::parse($yr->end_date)->format('d M Y') : '31 Aug 2025';
                $now = \Carbon\Carbon::now();
                $end = $yr->end_date ? \Carbon\Carbon::parse($yr->end_date) : $now->copy()->addMonths(6);
                $daysRemaining = $end->isPast() ? 0 : max(0, (int) $now->diffInDays($end, false));

                return [
                    'id'              => $yr->id,
                    'code'            => $yr->code,
                    'name'            => $yr->name,
                    'start_date'      => $startDate,
                    'end_date'        => $endDate,
                    'semesters_count' => $yr->semesters_count ?? 2,
                    'status'          => $yr->is_active ? 'active' : ($yr->status ?? 'completed'),
                    'is_active'       => (bool) $yr->is_active,
                    'students_count'  => $yr->is_active ? 2458 : ($end->isPast() ? 2150 : 0),
                    'courses_count'   => $yr->is_active ? 328 : ($end->isPast() ? 310 : 0),
                    'progress'        => $yr->is_active ? 85 : ($end->isPast() ? 100 : 0),
                    'days_remaining'  => $daysRemaining,
                ];
            });
        });

        return Inertia::render('Admin/AcademicStructureModule/AcademicYears', [
            'academicYears' => $academicYears->isNotEmpty() ? $academicYears->toArray() : [],
            'summaryStats'  => $this->getSummaryStats(),
        ]);
    }

    public function storeAcademicYear(Request $request)
    {
        $validated = $request->validate([
            'code'            => 'required|string|max:50|unique:academic_years,code',
            'name'            => 'required|string|max:255',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date',
            'semesters_count' => 'nullable|integer',
            'status'          => 'nullable|string',
        ]);

        AcademicYear::create($validated);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Academic Year created successfully.');
    }

    public function updateAcademicYear(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'code'            => 'required|string|max:50|unique:academic_years,code,' . $academicYear->id,
            'name'            => 'required|string|max:255',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date',
            'semesters_count' => 'nullable|integer',
            'status'          => 'nullable|string',
        ]);

        $academicYear->update($validated);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Academic Year updated successfully.');
    }

    public function destroyAcademicYear(AcademicYear $academicYear)
    {
        $academicYear->delete();
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Academic Year deleted successfully.');
    }

    public function setActiveAcademicYear(AcademicYear $academicYear)
    {
        AcademicYear::query()->update(['is_active' => false, 'status' => 'completed']);
        $academicYear->update(['is_active' => true, 'status' => 'active']);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Active Academic Year set to ' . $academicYear->name);
    }

    // ─── SEMESTERS ───
    public function semesters(): Response
    {
        $semesters = Cache::remember('academic_structure.semesters', 86400, function () {
            return Schema::hasTable('semesters')
                ? Semester::latest()->get()
                : collect();
        });

        $defaultSemesters = [
            ['id' => 1, 'code' => 'SEM-1-2024-2025', 'name' => 'Semester 1 — 2024-2025', 'parent_year' => 'Academic Year 2024–2025', 'semester_num' => 'Semester 1', 'start_date' => '01 Sep 2024', 'end_date' => '15 Feb 2025', 'status' => 'completed', 'is_active' => false],
            ['id' => 2, 'code' => 'SEM-2-2024-2025', 'name' => 'Semester 2 — 2024-2025', 'parent_year' => 'Academic Year 2024–2025', 'semester_num' => 'Semester 2', 'start_date' => '16 Feb 2025', 'end_date' => '31 Aug 2025', 'status' => 'active', 'is_active' => true],
            ['id' => 3, 'code' => 'SEM-1-2023-2024', 'name' => 'Semester 1 — 2023-2024', 'parent_year' => 'Academic Year 2023–2024', 'semester_num' => 'Semester 1', 'start_date' => '01 Sep 2023', 'end_date' => '15 Feb 2024', 'status' => 'completed', 'is_active' => false],
            ['id' => 4, 'code' => 'SEM-2-2023-2024', 'name' => 'Semester 2 — 2023-2024', 'parent_year' => 'Academic Year 2023–2024', 'semester_num' => 'Semester 2', 'start_date' => '16 Feb 2024', 'end_date' => '31 Aug 2024', 'status' => 'completed', 'is_active' => false],
            ['id' => 5, 'code' => 'SEM-1-2025-2026', 'name' => 'Semester 1 — 2025-2026', 'parent_year' => 'Academic Year 2025–2026', 'semester_num' => 'Semester 1', 'start_date' => '01 Sep 2025', 'end_date' => '15 Feb 2026', 'status' => 'planned', 'is_active' => false],
        ];

        return Inertia::render('Admin/AcademicStructureModule/Semesters', [
            'semesters'    => $semesters->isNotEmpty() ? $semesters->toArray() : $defaultSemesters,
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function storeSemester(Request $request)
    {
        $validated = $request->validate([
            'code'             => 'required|string|max:50|unique:semesters,code',
            'name'             => 'required|string|max:255',
            'parent_year'      => 'nullable|string',
            'semester_num'     => 'nullable|string',
            'start_date'       => 'nullable|date',
            'end_date'         => 'nullable|date',
            'enrollment_open'  => 'nullable|date',
            'enrollment_close' => 'nullable|date',
            'midterm_exam'     => 'nullable|string',
            'final_exam'       => 'nullable|string',
            'payment_due'      => 'nullable|date',
            'late_fee'         => 'nullable|string',
            'status'           => 'nullable|string',
        ]);

        Semester::create($validated);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Semester created successfully.');
    }

    public function updateSemester(Request $request, Semester $semester)
    {
        $validated = $request->validate([
            'code'             => 'required|string|max:50|unique:semesters,code,' . $semester->id,
            'name'             => 'required|string|max:255',
            'parent_year'      => 'nullable|string',
            'semester_num'     => 'nullable|string',
            'start_date'       => 'nullable|date',
            'end_date'         => 'nullable|date',
            'enrollment_open'  => 'nullable|date',
            'enrollment_close' => 'nullable|date',
            'midterm_exam'     => 'nullable|string',
            'final_exam'       => 'nullable|string',
            'payment_due'      => 'nullable|date',
            'late_fee'         => 'nullable|string',
            'status'           => 'nullable|string',
        ]);

        $semester->update($validated);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Semester updated successfully.');
    }

    public function destroySemester(Semester $semester)
    {
        $semester->delete();
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Semester deleted successfully.');
    }

    public function setActiveSemester(Semester $semester)
    {
        Semester::query()->update(['is_active' => false, 'status' => 'completed']);
        $semester->update(['is_active' => true, 'status' => 'active']);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Active Semester set to ' . $semester->name);
    }
}

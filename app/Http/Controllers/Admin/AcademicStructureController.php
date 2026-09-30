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
            'name'        => 'required|string|max:255',
            'name_kh'     => 'nullable|string|max:255',
            'code'        => 'required|string|max:50|unique:departments,code',
            'head'        => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $isActive = $request->has('is_active') 
            ? $request->boolean('is_active') 
            : ($request->input('status') === 'inactive' ? false : true);

        Department::create($validated + ['is_active' => $isActive]);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Department created successfully.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $validated = $request->validate([
            'faculty_id'  => 'nullable|exists:faculties,id',
            'name'        => 'required|string|max:255',
            'name_kh'     => 'nullable|string|max:255',
            'code'        => 'required|string|max:50|unique:departments,code,' . $department->id,
            'head'        => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        if ($request->has('status') && !$request->has('is_active')) {
            $validated['is_active'] = $request->input('status') !== 'inactive';
        }

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
            return Schema::hasTable('majors')
                ? Major::with(['department.faculty'])->latest()->get()
                : collect();
        });

        $defaultMajors = [
            [
                'id' => 1, 'code' => 'MJR-IT-001', 'name' => 'Information Technology', 'name_kh' => 'បច្ចេកវិទ្យាព័ត៌មាន', 'department' => 'Computing', 'faculty' => 'Faculty of Computing', 'students_count' => 520, 'teachers_count' => 25, 'courses_count' => 32, 'price_per_subject' => 25, 'duration' => '4 Years', 'degree_level' => 'Bachelor', 'credits' => 120, 'language' => 'English / Khmer', 'status' => 'active',
                'linked_courses' => [
                    ['name' => 'C Programming', 'price' => 25, 'teacher' => 'Mr. Sophea'],
                    ['name' => 'Web Development', 'price' => 30, 'teacher' => 'Ms. Dara'],
                    ['name' => 'Database Systems', 'price' => 25, 'teacher' => 'Mr. Sophea'],
                    ['name' => 'Networking Basics', 'price' => 20, 'teacher' => 'Mr. Vuthy'],
                ]
            ],
            [
                'id' => 2, 'code' => 'MJR-TRM-002', 'name' => 'Tourism', 'name_kh' => 'ទេសចរណ៍', 'department' => 'Tourism', 'faculty' => 'Faculty of Tourism', 'students_count' => 410, 'teachers_count' => 18, 'courses_count' => 28, 'price_per_subject' => 20, 'duration' => '4 Years', 'degree_level' => 'Bachelor', 'credits' => 120, 'language' => 'English / Khmer', 'status' => 'active',
                'linked_courses' => [
                    ['name' => 'Tourism Basics', 'price' => 20, 'teacher' => 'Mr. Long'],
                    ['name' => 'Hospitality Management', 'price' => 25, 'teacher' => 'Ms. Dara'],
                ]
            ],
            [
                'id' => 3, 'code' => 'MJR-ENG-003', 'name' => 'English Literature', 'name_kh' => 'អក្សរសាស្ត្រអង់គ្លេស', 'department' => 'Education', 'faculty' => 'Faculty of Education', 'students_count' => 380, 'teachers_count' => 20, 'courses_count' => 24, 'price_per_subject' => 20, 'duration' => '4 Years', 'degree_level' => 'Bachelor', 'credits' => 120, 'language' => 'English', 'status' => 'active',
                'linked_courses' => [
                    ['name' => 'English Grammar', 'price' => 0, 'teacher' => 'Ms. Srey'],
                    ['name' => 'English Writing', 'price' => 20, 'teacher' => 'Ms. Srey'],
                ]
            ],
            [
                'id' => 4, 'code' => 'MJR-AGR-004', 'name' => 'Agriculture', 'name_kh' => 'កសិកម្ម', 'department' => 'Agriculture', 'faculty' => 'Faculty of Agriculture', 'students_count' => 600, 'teachers_count' => 22, 'courses_count' => 30, 'price_per_subject' => 25, 'duration' => '4 Years', 'degree_level' => 'Bachelor', 'credits' => 120, 'language' => 'Khmer', 'status' => 'active',
                'linked_courses' => [
                    ['name' => 'Plant Science', 'price' => 25, 'teacher' => 'Mr. Vuthy'],
                    ['name' => 'Soil Studies', 'price' => 20, 'teacher' => 'Mr. Vuthy'],
                ]
            ],
            [
                'id' => 5, 'code' => 'MJR-SW-005', 'name' => 'Social Work', 'name_kh' => 'ការងារសង្គម', 'department' => 'Social Science', 'faculty' => 'Faculty of Social Science', 'students_count' => 548, 'teachers_count' => 15, 'courses_count' => 26, 'price_per_subject' => 25, 'duration' => '4 Years', 'degree_level' => 'Bachelor', 'credits' => 120, 'language' => 'Khmer', 'status' => 'active',
                'linked_courses' => [
                    ['name' => 'Social Work 101', 'price' => 25, 'teacher' => 'Mr. Rithy'],
                    ['name' => 'Community Dev', 'price' => 30, 'teacher' => 'Mr. Rithy'],
                ]
            ],
        ];

        return Inertia::render('Admin/AcademicStructureModule/Majors', [
            'majors'       => $majors->isNotEmpty() ? $majors->toArray() : $defaultMajors,
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function storeMajor(Request $request)
    {
        $validated = $request->validate([
            'department_id'     => 'nullable|exists:departments,id',
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

        $isActive = $request->has('is_active') 
            ? $request->boolean('is_active') 
            : ($request->input('status') === 'inactive' ? false : true);

        Major::create($validated + ['is_active' => $isActive]);
        $this->clearAcademicCache();

        return redirect()->back()->with('success', 'Major created successfully.');
    }

    public function updateMajor(Request $request, Major $major)
    {
        $validated = $request->validate([
            'department_id'     => 'nullable|exists:departments,id',
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

        if ($request->has('status') && !$request->has('is_active')) {
            $validated['is_active'] = $request->input('status') !== 'inactive';
        }

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
            return Schema::hasTable('academic_years')
                ? AcademicYear::latest()->get()
                : collect();
        });

        $defaultYears = [
            ['id' => 1, 'code' => 'AY-2024-2025', 'name' => 'Academic Year 2024 – 2025', 'start_date' => '01 Sep 2024', 'end_date' => '31 Aug 2025', 'semesters_count' => 2, 'status' => 'active', 'is_active' => true, 'students_count' => 2458, 'courses_count' => 328, 'progress' => 85, 'days_remaining' => 77],
            ['id' => 2, 'code' => 'AY-2023-2024', 'name' => 'Academic Year 2023 – 2024', 'start_date' => '01 Sep 2023', 'end_date' => '31 Aug 2024', 'semesters_count' => 2, 'status' => 'completed', 'is_active' => false, 'students_count' => 2150, 'courses_count' => 310, 'progress' => 100, 'days_remaining' => 0],
            ['id' => 3, 'code' => 'AY-2022-2023', 'name' => 'Academic Year 2022 – 2023', 'start_date' => '01 Sep 2022', 'end_date' => '31 Aug 2023', 'semesters_count' => 2, 'status' => 'completed', 'is_active' => false, 'students_count' => 1980, 'courses_count' => 290, 'progress' => 100, 'days_remaining' => 0],
            ['id' => 4, 'code' => 'AY-2025-2026', 'name' => 'Academic Year 2025 – 2026', 'start_date' => '01 Sep 2025', 'end_date' => '31 Aug 2026', 'semesters_count' => 2, 'status' => 'upcoming', 'is_active' => false, 'students_count' => 0, 'courses_count' => 0, 'progress' => 0, 'days_remaining' => 365],
        ];

        return Inertia::render('Admin/AcademicStructureModule/AcademicYears', [
            'academicYears' => $academicYears->isNotEmpty() ? $academicYears->toArray() : $defaultYears,
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

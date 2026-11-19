<?php

namespace Database\Seeders;

use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Deadline;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds for student assignments.
     */
    public function run(): void
    {
        // 1. Get or pick published course (e.g. Course 55 or first published course)
        $course = Course::where('status', 'published')->first();
        if (!$course) {
            $course = Course::first();
        }

        if (!$course) {
            return;
        }

        // 2. Pick teacher or default
        $teacher = User::where('role', 'teacher')->first() ?? User::where('role', 'admin')->first();
        $student = User::where('role', 'student')->first() ?? User::where('role', 'admin')->first();

        // 3. Ensure student is enrolled
        if ($student) {
            Enrollment::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'course_id'  => $course->id,
                ],
                [
                    'status'      => 'active',
                    'enrolled_at' => now()->subDays(30),
                    'created_at'  => now()->subDays(30),
                ]
            );
        }

        // 4. Create realistic Course Assignments
        $assignmentsData = [
            [
                'title'       => 'Front Office Guest Service Standard Operating Procedures',
                'pass_score'  => 70,
                'due_offset'  => 5, // Due in 5 days
                'status_type' => 'pending',
            ],
            [
                'title'       => 'Restaurant Customer Complaint Resolution & Service Recovery',
                'pass_score'  => 70,
                'due_offset'  => -2, // Due 2 days ago
                'status_type' => 'graded',
                'score'       => 88,
                'feedback'    => 'ការវិភាគល្អិតល្អន់ និងដំណោះស្រាយប្រកបដោយវិជ្ជាជីវៈខ្ពស់ក្នុងការដោះស្រាយបណ្តឹងភ្ញៀវ។ បង្ហាញពីចំណេះដឹងច្បាស់លាស់លើសេវាកម្មបដិសណ្ឋារកិច្ច (Excellent problem-solving and professional service recovery analysis).',
            ],
            [
                'title'       => 'Tourism Marketing Strategy & Eco-Tour Package Design',
                'pass_score'  => 75,
                'due_offset'  => 12, // Due in 12 days
                'status_type' => 'pending',
            ],
            [
                'title'       => 'Front Desk Night Audit & Room Revenue Reconciliation',
                'pass_score'  => 70,
                'due_offset'  => -5, // 5 days ago
                'status_type' => 'overdue',
            ],
        ];

        foreach ($assignmentsData as $item) {
            $quiz = Quiz::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'title'     => $item['title'],
                    'type'      => 'assignment',
                ],
                [
                    'passing_score'      => $item['pass_score'],
                    'time_limit_minutes' => null,
                    'max_attempts'       => 3,
                    'status'             => 'published',
                ]
            );

            // Create or update Deadline
            $dueAt = Carbon::now()->addDays($item['due_offset'])->setHour(23)->setMinute(59)->setSecond(0);
            Deadline::updateOrCreate(
                [
                    'course_id'   => $course->id,
                    'linked_type' => 'quiz',
                    'linked_id'   => $quiz->id,
                ],
                [
                    'teacher_id'        => $teacher?->id,
                    'title'             => $quiz->title,
                    'deadline_type'     => 'assignment',
                    'due_at'            => $dueAt,
                    'grace_days'        => 2,
                    'penalty_percent'   => 5,
                    'applicable_to'     => 'all',
                    'show_countdown'    => true,
                    'auto_reminder_24h' => true,
                    'auto_reminder_1h'  => true,
                    'auto_lock'         => false,
                    'message_kh'        => 'សូមដាក់ស្នើកិច្ចការឲ្យបានមុនកាលបរិច្ឆេទកំណត់។',
                    'message_en'        => 'Please submit your assignment before the due date.',
                    'status'            => 'active',
                ]
            );

            // If graded submission
            if ($item['status_type'] === 'graded' && $student) {
                AssignmentSubmission::updateOrCreate(
                    [
                        'user_id' => $student->id,
                        'quiz_id' => $quiz->id,
                    ],
                    [
                        'course_id'    => $course->id,
                        'title'        => $quiz->title,
                        'notes'        => 'សូមលោកគ្រូពិនិត្យឯកសារករណីសិក្សា និងផែនការដោះស្រាយបណ្តឹងភ្ញៀវសណ្ឋាគារ។ ខ្ញុំបានរៀបចំជម្រើសសងជំងឺចិត្ត និងរបៀបឆ្លើយតបយ៉ាងសមរម្យ។',
                        'file_path'    => '/uploads/assignments/sample_guest_resolution_report.pdf',
                        'file_name'    => 'Guest_Service_Recovery_Plan.pdf',
                        'file_size'    => '1.8 MB',
                        'score'        => $item['score'],
                        'feedback'     => $item['feedback'],
                        'status'       => 'graded',
                        'submitted_at' => now()->subDays(3),
                        'graded_at'    => now()->subDays(1),
                    ]
                );
            }
        }
    }
}

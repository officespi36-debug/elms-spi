<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    /**
     * Canonical Enrollment Method across all entry points.
     *
     * Sources:
     * - 'self': Student enrolls themselves (free course, auto-view)
     * - 'teacher': Assigned by Course Instructor (course.teacher_id === actor.id)
     * - 'admin': Assigned by Administrator (actor.role === 'admin')
     * - 'payment': Activated upon successful student payment verification
     * - 'import': Imported from bulk registration/system migration
     */
    public function enroll(
        User $student,
        Course $course,
        string $source = 'self',
        ?User $actor = null,
        ?string $semester = null
    ): Enrollment {
        // 1. Authorization check
        if ($source === 'teacher') {
            if (!$actor || $actor->role !== 'teacher') {
                abort(403, 'Only teachers can perform teacher enrollment.');
            }
            if ((int)$course->teacher_id !== (int)$actor->id) {
                abort(403, 'Teachers can only enroll students into their own courses.');
            }
        } elseif ($source === 'admin') {
            if (!$actor || $actor->role !== 'admin') {
                abort(403, 'Only administrators can perform admin enrollment.');
            }
        }

        return DB::transaction(function () use ($student, $course, $source, $actor, $semester) {
            $existing = Enrollment::where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->first();

            $semesterVal = $semester ?: ($course->semester ?: 'Semester 1');

            if ($existing) {
                // If inactive and activating via payment or admin/teacher
                if ($existing->status !== 'active' && in_array($source, ['payment', 'admin', 'teacher'])) {
                    $existing->status = 'active';
                    $existing->enrolled_at = now();
                    $existing->source = $source;
                    if ($actor) {
                        $existing->enrolled_by = $actor->id;
                    }
                    $existing->seen_at = null; // Mark as new for student
                    $existing->save();

                    Cache::forget("student.{$student->id}.new_enrollments");
                    $this->sendNotification($student, $course, $source, $actor, $semesterVal);
                }
                return $existing;
            }

            // Determine status
            $isPaidSelf = ($course->is_paid && (float)$course->price > 0 && $source === 'self');
            $status = $isPaidSelf ? 'pending_payment' : 'active';

            // Determine enrolled_by
            $enrolledById = null;
            if ($source === 'self') {
                $enrolledById = $student->id;
            } elseif ($actor) {
                $enrolledById = $actor->id;
            }

            // Determine seen_at:
            // Self-enrolled is immediately seen; third-party enrollment (teacher/admin/payment) is unseen (null)
            $seenAt = ($source === 'self') ? now() : null;

            $enrollment = Enrollment::create([
                'student_id'  => $student->id,
                'course_id'   => $course->id,
                'semester'    => $semesterVal,
                'status'      => $status,
                'enrolled_at' => $status === 'active' ? now() : null,
                'enrolled_by' => $enrolledById,
                'source'      => $source,
                'seen_at'     => $seenAt,
            ]);

            Cache::forget("student.{$student->id}.new_enrollments");

            // Send notification for third-party enrollments
            if ($status === 'active' && $source !== 'self') {
                $this->sendNotification($student, $course, $source, $actor, $semesterVal);
            }

            return $enrollment;
        });
    }

    /**
     * Send in-app notification to student using existing notification infrastructure.
     */
    protected function sendNotification(
        User $student,
        Course $course,
        string $source,
        ?User $actor,
        string $semester
    ): void {
        try {
            if (!class_exists(Notification::class)) {
                return;
            }

            $actorName = $actor?->name ?: 'គ្រូឧទ្ទេស/អ្នកគ្រប់គ្រង';
            $courseUrl = "/student/courses/{$course->id}/overview";

            if ($source === 'teacher') {
                $title = 'វគ្គសិក្សាថ្មីត្រូវបានបញ្ចូល (New Course Added)';
                $message = "លោកគ្រូ/អ្នកគ្រូ '{$actorName}' បានចុះឈ្មោះអ្នកចូលរៀនវគ្គ '{$course->title}' ({$semester})។ [url:{$courseUrl}]";
            } elseif ($source === 'admin') {
                $title = 'ការចុះឈ្មោះវគ្គសិក្សាជោគជ័យ (Admin Enrollment)';
                $message = "អ្នកគ្រប់គ្រង '{$actorName}' បានចុះឈ្មោះអ្នកចូលរៀនវគ្គ '{$course->title}' ({$semester})។ [url:{$courseUrl}]";
            } elseif ($source === 'payment') {
                $title = 'ការទូទាត់ជោគជ័យ និងទទួលបានវគ្គសិក្សា (Payment Verified)';
                $message = "ការទូទាត់សម្រាប់វគ្គ '{$course->title}' ទទួលបានជោគជ័យ! អ្នកអាចចាប់ផ្តើមរៀនបានឥឡូវនេះ។ [url:{$courseUrl}]";
            } else {
                $title = 'ការចុះឈ្មោះវគ្គសិក្សាជោគជ័យ (New Course Enrolled)';
                $message = "អ្នកត្រូវបានចុះឈ្មោះចូលរៀនវគ្គ '{$course->title}' ({$semester}) រួចរាល់។ [url:{$courseUrl}]";
            }

            Notification::create([
                'title'   => $title,
                'message' => $message,
                'target'  => "user:{$student->id}",
            ]);

            Cache::forget("student_notif_payload_{$student->id}");
        } catch (\Throwable $e) {
            // Non-blocking notification fallback
        }
    }
}

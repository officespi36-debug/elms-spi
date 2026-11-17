<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\Channels\ElmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeacherStudentJoined extends Notification
{
    use Queueable;

    public function __construct(
        public readonly User $student,
        public readonly Course $course,
        public readonly Enrollment $enrollment,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [ElmsNotificationChannel::class];
    }

    /**
     * @return array<string, string>
     */
    public function toElmsDatabase(object $notifiable): array
    {
        $studentName = $this->student->name ?: ($this->student->name_kh ?: 'Student');

        return [
            'title'   => "សិស្សថ្មីបានចុះឈ្មោះចូលរៀន ({$this->course->title})",
            'message' => "{$studentName} បានចុះឈ្មោះចូលរៀនវគ្គ '{$this->course->title}'។ [url:/teacher/courses/{$this->course->id}]",
            'target'  => "user:{$notifiable->id}",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'student_id'    => $this->student->id,
            'course_id'     => $this->course->id,
            'enrollment_id' => $this->enrollment->id,
        ];
    }
}

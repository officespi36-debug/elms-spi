<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateVerificationLog;
use App\Models\Course;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService
{
    /**
     * Generate standard sequential Certificate Number: ELMS-YYYY-XXXXXX
     */
    public function generateCertificateNumber(): string
    {
        $year = date('Y');
        $lastCert = Certificate::latest('id')->first();
        $nextSeq = ($lastCert ? $lastCert->id : 0) + 1;
        $seqString = str_pad((string)$nextSeq, 6, '0', STR_PAD_LEFT);

        return "ELMS-{$year}-{$seqString}";
    }

    /**
     * Issue a new certificate or return existing valid certificate
     */
    public function issue(User $student, Course $course, ?int $templateId = null, string $grade = 'A', int $score = 85): Certificate
    {
        $existing = Certificate::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            if ($existing->status === 'revoked') {
                $existing->update([
                    'status' => 'valid',
                    'revoked_at' => null,
                    'revoked_by' => null,
                    'revocation_reason' => null,
                    'revocation_note' => null,
                ]);
            }
            return $existing;
        }

        $certNumber = $this->generateCertificateNumber();
        $verifyCode = strtoupper(Str::random(10));

        $certificate = Certificate::create([
            'student_id'         => $student->id,
            'course_id'          => $course->id,
            'major_id'           => $student->major_id ?? $course->department?->faculty_id,
            'template_id'        => $templateId,
            'certificate_number' => $certNumber,
            'verification_code'  => $verifyCode,
            'grade'              => $grade,
            'score'              => $score,
            'status'             => 'valid',
            'issued_at'          => now(),
            'audit_trail'        => [
                [
                    'action'     => 'issued',
                    'by'         => auth()->user()?->name ?? 'System',
                    'timestamp'  => now()->toIso8601String(),
                    'details'    => 'Certificate generated and registered.',
                ]
            ]
        ]);

        return $certificate;
    }

    /**
     * Verify certificate and log verification attempt
     */
    public function logVerification(string $codeOrId, ?string $ipAddress = '127.0.0.1', string $source = 'manual_id'): array
    {
        $cert = Certificate::with(['student', 'course', 'major', 'template'])
            ->where('verification_code', $codeOrId)
            ->orWhere('certificate_number', $codeOrId)
            ->first();

        if (!$cert) {
            CertificateVerificationLog::create([
                'certificate_number' => $codeOrId,
                'result'             => 'not_found',
                'ip_address'         => $ipAddress,
                'location'           => 'Phnom Penh, KH',
                'source'             => $source,
            ]);

            return [
                'status'  => 'not_found',
                'message' => 'Certificate ID not found in official registry.',
            ];
        }

        $result = $cert->status; // valid or revoked

        $cert->increment('verifications_count');
        $cert->update(['last_verified_at' => now()]);

        CertificateVerificationLog::create([
            'certificate_id'     => $cert->id,
            'certificate_number' => $cert->certificate_number,
            'result'             => $result,
            'ip_address'         => $ipAddress,
            'location'           => 'Phnom Penh, KH',
            'source'             => $source,
        ]);

        return [
            'status'               => $result,
            'certificate'          => $cert,
            'student_name'         => $cert->student?->name ?? 'N/A',
            'student_id'           => 'STU' . str_pad($cert->student_id ?? 1, 5, '0', STR_PAD_LEFT),
            'course_name'          => $cert->course?->title ?? 'N/A',
            'major_name'           => $cert->major?->name ?? 'IT & Networking',
            'grade'                => $cert->grade ?? 'A',
            'score'                => $cert->score ?? 85,
            'issued_at'            => $cert->issued_at ? $cert->issued_at->format('d F Y') : 'N/A',
            'certificate_number'   => $cert->certificate_number,
            'revoked_at'           => $cert->revoked_at ? $cert->revoked_at->format('d F Y') : null,
            'revocation_reason'    => $cert->show_reason_publicly ? $cert->revocation_reason : 'Revoked by institution policy',
        ];
    }

    /**
     * Get structured My Certificates dashboard data.
     */
    public function getMyCertificatesData(?User $user = null, string $status = 'all', string $category = 'all', string $course = 'all', string $issuer = 'all', string $search = '', string $sort = 'newest'): array
    {
        // 1. Summary Cards
        $summary = [
            'total_certificates'  => 12,
            'total_note'          => 'All time earned',
            'completed_courses'   => 8,
            'completed_note'      => 'With certificates',
            'this_year'           => 5,
            'this_year_note'      => 'Certificates earned',
            'recent_date'         => 'May 28, 2025',
            'recent_note'         => 'Most recent',
            'total_learning_time' => '245h 30m',
            'time_note'           => 'Across all courses',
        ];

        // 2. Certificate Statistics (Donut Chart)
        $statistics = [
            'total' => 12,
            'items' => [
                ['label' => 'Completed',   'count' => 8, 'percentage' => 67, 'color' => '#10B981'],
                ['label' => 'In Progress', 'count' => 2, 'percentage' => 17, 'color' => '#3B82F6'],
                ['label' => 'Expired',     'count' => 0, 'percentage' => 0,  'color' => '#F59E0B'],
                ['label' => 'Upcoming',    'count' => 2, 'percentage' => 16, 'color' => '#A855F7'],
            ]
        ];

        // 3. Recent Achievements
        $recentAchievements = [
            [
                'id'          => 1,
                'title'       => 'Learning Champion',
                'description' => 'Earned 10 certificates',
                'date'        => 'May 28, 2025',
                'icon'        => '🏆',
                'icon_bg'     => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
            ],
            [
                'id'          => 2,
                'title'       => 'Consistent Learner',
                'description' => '7 days learning streak',
                'date'        => 'May 25, 2025',
                'icon'        => '🎖️',
                'icon_bg'     => 'bg-blue-500/20 text-blue-300 border border-blue-500/30',
            ],
            [
                'id'          => 3,
                'title'       => 'Course Master',
                'description' => 'Completed 8 courses',
                'date'        => 'May 20, 2025',
                'icon'        => '🛡️',
                'icon_bg'     => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
            ],
            [
                'id'          => 4,
                'title'       => 'Skill Developer',
                'description' => 'Mastered new skills',
                'date'        => 'May 18, 2025',
                'icon'        => '🔮',
                'icon_bg'     => 'bg-purple-500/20 text-purple-300 border border-purple-500/30',
            ],
        ];

        // 4. Certificates Gallery Catalog (8 items matching reference design)
        $certificates = [
            [
                'id'            => 1,
                'title'         => 'JavaScript Advanced',
                'student_name'  => 'Sok Pisey',
                'issuer'        => 'SPI E-Learning Platform',
                'issued_date'   => 'Issued on May 28, 2025',
                'raw_date'      => 'May 28, 2025',
                'status'        => 'Verified',
                'status_type'   => 'verified',
                'cert_number'   => 'SPI-CERT-2025-JS8921',
                'qr_url'        => 'https://spilms.tech/verify/SPI-CERT-2025-JS8921',
                'accent_color'  => 'purple',
                'border_class'  => 'from-purple-500/40 to-indigo-500/40',
                'badge_color'   => 'bg-purple-600/20 text-purple-300 border-purple-500/30',
                'seal_color'    => '#8B5CF6',
                'category'      => 'Programming',
                'grade'         => 'A+',
                'score'         => '95%',
            ],
            [
                'id'            => 2,
                'title'         => 'React Development',
                'student_name'  => 'Sok Pisey',
                'issuer'        => 'SPI E-Learning Platform',
                'issued_date'   => 'Issued on May 25, 2025',
                'raw_date'      => 'May 25, 2025',
                'status'        => 'Verified',
                'status_type'   => 'verified',
                'cert_number'   => 'SPI-CERT-2025-RC4410',
                'qr_url'        => 'https://spilms.tech/verify/SPI-CERT-2025-RC4410',
                'accent_color'  => 'blue',
                'border_class'  => 'from-cyan-500/40 to-blue-500/40',
                'badge_color'   => 'bg-blue-600/20 text-blue-300 border-blue-500/30',
                'seal_color'    => '#3B82F6',
                'category'      => 'Frontend',
                'grade'         => 'A',
                'score'         => '88%',
            ],
            [
                'id'            => 3,
                'title'         => 'Node.js Fundamentals',
                'student_name'  => 'Sok Pisey',
                'issuer'        => 'SPI E-Learning Platform',
                'issued_date'   => 'Issued on May 25, 2025',
                'raw_date'      => 'May 25, 2025',
                'status'        => 'Verified',
                'status_type'   => 'verified',
                'cert_number'   => 'SPI-CERT-2025-ND3190',
                'qr_url'        => 'https://spilms.tech/verify/SPI-CERT-2025-ND3190',
                'accent_color'  => 'emerald',
                'border_class'  => 'from-emerald-500/40 to-teal-500/40',
                'badge_color'   => 'bg-emerald-600/20 text-emerald-300 border-emerald-500/30',
                'seal_color'    => '#10B981',
                'category'      => 'Backend',
                'grade'         => 'A',
                'score'         => '90%',
            ],
            [
                'id'            => 4,
                'title'         => 'Python Programming',
                'student_name'  => 'Sok Pisey',
                'issuer'        => 'SPI E-Learning Platform',
                'issued_date'   => 'Issued on May 20, 2025',
                'raw_date'      => 'May 20, 2025',
                'status'        => 'Verified',
                'status_type'   => 'verified',
                'cert_number'   => 'SPI-CERT-2025-PY7832',
                'qr_url'        => 'https://spilms.tech/verify/SPI-CERT-2025-PY7832',
                'accent_color'  => 'amber',
                'border_class'  => 'from-amber-500/40 to-orange-500/40',
                'badge_color'   => 'bg-amber-600/20 text-amber-300 border-amber-500/30',
                'seal_color'    => '#F59E0B',
                'category'      => 'Programming',
                'grade'         => 'A',
                'score'         => '88%',
            ],
            [
                'id'            => 5,
                'title'         => 'Database Design',
                'student_name'  => 'Sok Pisey',
                'issuer'        => 'SPI E-Learning Platform',
                'issued_date'   => 'Issued on May 15, 2025',
                'raw_date'      => 'May 15, 2025',
                'status'        => 'Verified',
                'status_type'   => 'verified',
                'cert_number'   => 'SPI-CERT-2025-DB6012',
                'qr_url'        => 'https://spilms.tech/verify/SPI-CERT-2025-DB6012',
                'accent_color'  => 'rose',
                'border_class'  => 'from-rose-500/40 to-red-500/40',
                'badge_color'   => 'bg-rose-600/20 text-rose-300 border-rose-500/30',
                'seal_color'    => '#EF4444',
                'category'      => 'Database',
                'grade'         => 'B+',
                'score'         => '78%',
            ],
            [
                'id'            => 6,
                'title'         => 'UI/UX Design Basics',
                'student_name'  => 'Sok Pisey',
                'issuer'        => 'SPI E-Learning Platform',
                'issued_date'   => 'Issued on May 10, 2025',
                'raw_date'      => 'May 10, 2025',
                'status'        => 'Verified',
                'status_type'   => 'verified',
                'cert_number'   => 'SPI-CERT-2025-UX1182',
                'qr_url'        => 'https://spilms.tech/verify/SPI-CERT-2025-UX1182',
                'accent_color'  => 'cyan',
                'border_class'  => 'from-teal-500/40 to-cyan-500/40',
                'badge_color'   => 'bg-cyan-600/20 text-cyan-300 border-cyan-500/30',
                'seal_color'    => '#06B6D4',
                'category'      => 'Design',
                'grade'         => 'A',
                'score'         => '85%',
            ],
            [
                'id'            => 7,
                'title'         => 'HTML & CSS Basics',
                'student_name'  => 'Sok Pisey',
                'issuer'        => 'SPI E-Learning Platform',
                'issued_date'   => 'Issued on May 5, 2025',
                'raw_date'      => 'May 5, 2025',
                'status'        => 'Verified',
                'status_type'   => 'verified',
                'cert_number'   => 'SPI-CERT-2025-HC9044',
                'qr_url'        => 'https://spilms.tech/verify/SPI-CERT-2025-HC9044',
                'accent_color'  => 'purple',
                'border_class'  => 'from-purple-500/40 to-indigo-500/40',
                'badge_color'   => 'bg-purple-600/20 text-purple-300 border-purple-500/30',
                'seal_color'    => '#8B5CF6',
                'category'      => 'Frontend',
                'grade'         => 'A+',
                'score'         => '96%',
            ],
            [
                'id'            => 8,
                'title'         => 'Git & GitHub',
                'student_name'  => 'Sok Pisey',
                'issuer'        => 'SPI E-Learning Platform',
                'issued_date'   => 'Issued on Apr 2, 2025',
                'raw_date'      => 'Apr 2, 2025',
                'status'        => 'Verified',
                'status_type'   => 'verified',
                'cert_number'   => 'SPI-CERT-2025-GT0058',
                'qr_url'        => 'https://spilms.tech/verify/SPI-CERT-2025-GT0058',
                'accent_color'  => 'teal',
                'border_class'  => 'from-teal-500/40 to-slate-500/40',
                'badge_color'   => 'bg-teal-600/20 text-teal-300 border-teal-500/30',
                'seal_color'    => '#14B8A6',
                'category'      => 'DevOps',
                'grade'         => 'A',
                'score'         => '92%',
            ],
        ];

        // Filter by tab
        if ($status !== 'all') {
            $certificates = array_values(array_filter($certificates, fn($c) => strtolower($c['status_type']) === strtolower($status)));
        }

        // Filter by category
        if ($category !== 'all') {
            $certificates = array_values(array_filter($certificates, fn($c) => strtolower($c['category']) === strtolower($category)));
        }

        // Filter by search
        if (!empty($search)) {
            $s = strtolower($search);
            $certificates = array_values(array_filter($certificates, fn($c) => str_contains(strtolower($c['title']), $s) || str_contains(strtolower($c['cert_number']), $s)));
        }

        return [
            'summary'             => $summary,
            'statistics'          => $statistics,
            'recent_achievements' => $recentAchievements,
            'certificates'        => $certificates,
            'total_count'         => 12,
        ];
    }
}


<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CertificateController extends Controller
{
    protected CertificateService $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    public function verify(?string $code = null)
    {
        $code = $code ?: request()->route('code') ?: request()->route('uuid') ?: request()->query('q') ?: 'STU-2024-089';
        $ip = request()->ip();
        $source = request()->query('source', 'manual_id');

        $result = $this->certificateService->logVerification($code, $ip, $source);

        return Inertia::render('Certificate/Verify', [
            'initialCode'        => $code,
            'status'             => $result['status'], // valid, revoked, not_found, pending
            'is_student_id'      => $result['is_student_id'] ?? false,
            'student_name'       => $result['student_name'] ?? null,
            'student_name_kh'    => $result['student_name_kh'] ?? null,
            'student_id'         => $result['student_id'] ?? null,
            'course_name'        => $result['course_name'] ?? null,
            'major_name'         => $result['major_name'] ?? null,
            'faculty_name'       => $result['faculty_name'] ?? null,
            'grade'              => $result['grade'] ?? null,
            'score'              => $result['score'] ?? null,
            'issued_at'          => $result['issued_at'] ?? null,
            'certificate_number' => $result['certificate_number'] ?? $code,
            'revoked_at'         => $result['revoked_at'] ?? null,
            'revocation_reason'  => $result['revocation_reason'] ?? null,
            'status_label'       => $result['status_label'] ?? null,
            'institution'        => $result['institution'] ?? 'Saint Paul Institute (វិទ្យាស្ថាន សន្តប៉ូល)',
            'checked_at'         => now()->format('d M Y, H:i'),
        ]);
    }

    public function download(Certificate $certificate)
    {
        if (auth()->id() !== $certificate->student_id && auth()->user()?->role !== 'admin') {
            abort(403);
        }

        $certificate->load(['student', 'course']);
        $certificate->increment('downloads_count');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.certificate', [
            'student_name' => $certificate->student?->name ?? 'Chan Dara',
            'course_name'  => $certificate->course?->title ?? 'C Programming Basics',
            'issued_at'    => $certificate->issued_at ? $certificate->issued_at->format('F d, Y') : now()->format('F d, Y'),
            'cert_number'  => $certificate->certificate_number,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("Certificate-{$certificate->certificate_number}.pdf");
    }
}

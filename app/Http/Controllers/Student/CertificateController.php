<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'my-certificates');
        return Inertia::render('Student/Certificates/Index', [
            'activeTab' => $tab,
            'certificates' => Certificate::where('student_id', $request->user()->id)->with('course')->get()
        ]);
    }

    public function myCertificates(Request $request)
    {
        $status = $request->query('status', 'all');
        $category = $request->query('category', 'all');
        $course = $request->query('course', 'all');
        $issuer = $request->query('issuer', 'all');
        $search = $request->query('search', '');
        $sort = $request->query('sort', 'newest');

        $data = app(CertificateService::class)->getMyCertificatesData(
            $request->user(),
            $status,
            $category,
            $course,
            $issuer,
            $search,
            $sort
        );

        return Inertia::render('Student/Certificates/MyCertificates', [
            'analytics' => $data,
            'filters'   => [
                'status'   => $status,
                'category' => $category,
                'course'   => $course,
                'issuer'   => $issuer,
                'search'   => $search,
                'sort'     => $sort,
            ]
        ]);
    }

    public function downloadShare(Request $request)
    {
        $status = $request->query('status', 'all');
        $category = $request->query('category', 'all');
        $course = $request->query('course', 'all');
        $level = $request->query('level', 'all');
        $sort = $request->query('sort', 'progress');
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);

        $data = app(CertificateService::class)->getAvailableCertificatesData(
            $request->user(),
            $status,
            $category,
            $course,
            $level,
            $sort,
            $search,
            $page
        );

        return Inertia::render('Student/Certificates/DownloadShare', [
            'analytics' => $data,
            'filters'   => [
                'status'   => $status,
                'category' => $category,
                'course'   => $course,
                'level'    => $level,
                'sort'     => $sort,
                'search'   => $search,
                'page'     => $page,
            ]
        ]);
    }

    public function verify(Request $request)
    {
        return Inertia::render('Student/Certificates/VerifyCertificate');
    }

    public function publicVerify(Request $request, $uuid = null)
    {
        $certificate = null;
        $student = null;

        if ($uuid) {
            $cleanUuid = trim($uuid);
            $certificate = Certificate::where('certificate_uuid', $cleanUuid)
                ->orWhere('certificate_number', $cleanUuid)
                ->with(['student.major.department.faculty', 'course'])
                ->first();

            // If not a certificate number, check if it's a student ID code or user ID
            if (!$certificate) {
                $student = \App\Models\User::where('student_code', $cleanUuid)
                    ->orWhere('id', $cleanUuid)
                    ->with(['major.department.faculty'])
                    ->first();
            }
        }

        return Inertia::render('Student/Certificates/VerifyCertificate', [
            'publicCertUuid' => $uuid,
            'certificateData' => $certificate,
            'studentData' => $student,
        ]);
    }
}

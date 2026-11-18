<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'my-certificates');
        $service = app(CertificateService::class);
        $user = $request->user();

        $status = (string) $request->query('status', 'all');
        $category = (string) $request->query('category', 'all');
        $course = (string) $request->query('course', 'all');
        $issuer = (string) $request->query('issuer', 'all');
        $level = (string) $request->query('level', 'all');
        $search = (string) $request->query('search', '');
        $sort = (string) $request->query('sort', in_array($tab, ['download', 'download-share'], true) ? 'progress' : 'newest');
        $page = max(1, (int) $request->query('page', 1));
        $verifyQuery = $request->query('q') ?? $request->query('id') ?? $request->query('code');
        $verifyTab = (string) $request->query('verify_tab', 'id');

        $myCertificatesData = $service->getMyCertificatesData(
            $user,
            $status,
            $category,
            $course,
            $issuer,
            $search,
            $sort === 'progress' ? 'newest' : $sort
        );

        $availableCertificatesData = $service->getAvailableCertificatesData(
            $user,
            $status,
            $category,
            $course,
            $level,
            in_array($sort, ['progress', 'title'], true) ? $sort : 'progress',
            $search,
            $page
        );

        $verificationData = $service->getVerificationPageData(
            is_string($verifyQuery) ? $verifyQuery : null,
            'student_panel'
        );

        return Inertia::render('Student/Certificates/Index', [
            'activeTab'                 => $tab,
            'myCertificatesData'        => $myCertificatesData,
            'availableCertificatesData' => $availableCertificatesData,
            'verificationData'          => $verificationData,
            'filters'                   => [
                'status'   => $status,
                'category' => $category,
                'course'   => $course,
                'issuer'   => $issuer,
                'search'   => $search,
                'sort'     => $sort === 'progress' ? 'newest' : $sort,
            ],
            'availableFilters'          => [
                'status'   => $status,
                'category' => $category,
                'course'   => $course,
                'level'    => $level,
                'sort'     => in_array($sort, ['progress', 'title'], true) ? $sort : 'progress',
                'search'   => $search,
                'page'     => $page,
            ],
            'initialQuery'              => is_string($verifyQuery) ? $verifyQuery : '',
            'initialVerifyTab'          => $verifyTab,
        ]);
    }

    public function myCertificates(Request $request)
    {
        $status = (string) $request->query('status', 'all');
        $category = (string) $request->query('category', 'all');
        $course = (string) $request->query('course', 'all');
        $issuer = (string) $request->query('issuer', 'all');
        $search = (string) $request->query('search', '');
        $sort = (string) $request->query('sort', 'newest');

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
            ],
        ]);
    }

    public function downloadShare(Request $request)
    {
        $status = (string) $request->query('status', 'all');
        $category = (string) $request->query('category', 'all');
        $course = (string) $request->query('course', 'all');
        $level = (string) $request->query('level', 'all');
        $sort = (string) $request->query('sort', 'progress');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

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
            ],
        ]);
    }

    public function verify(Request $request)
    {
        $query = $request->query('q') ?? $request->query('id') ?? $request->query('code');
        $tab = (string) $request->query('tab', 'id');
        $data = app(CertificateService::class)->getVerificationPageData(
            is_string($query) ? $query : null,
            'student_panel'
        );

        return Inertia::render('Student/Certificates/VerifyCertificate', [
            'verificationData' => $data,
            'initialQuery'     => is_string($query) ? $query : '',
            'initialTab'       => $tab,
        ]);
    }

    public function verifyApi(Request $request)
    {
        $query = $request->input('query') ?? $request->input('id') ?? $request->input('code') ?? $request->input('link');
        $source = (string) $request->input('source', 'api');
        $data = app(CertificateService::class)->getVerificationPageData(
            is_string($query) ? $query : null,
            $source
        );

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function publicVerify(Request $request, $uuid = null)
    {
        $code = $uuid ?: $request->query('q') ?: $request->query('code') ?: null;
        return app(\App\Http\Controllers\CertificateController::class)->verify(is_string($code) ? $code : null);
    }
}

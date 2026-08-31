<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Services\CourseFeeInvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'my-payments');
        $payments = Payment::where('student_id', $request->user()->id)
            ->with(['course', 'teacher'])
            ->latest()
            ->get();

        return Inertia::render('Student/Payments/Index', [
            'activeTab' => $tab,
            'payments'  => $payments,
        ]);
    }

    public function myPayments(Request $request)
    {
        $status = $request->query('status', 'all');
        $course = $request->query('course', 'all');
        $dateRange = $request->query('date_range', 'all');
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);

        $data = app(CourseFeeInvoiceService::class)->getMyPaymentsData(
            $request->user(),
            $status,
            $course,
            $dateRange,
            $search,
            $page
        );

        return Inertia::render('Student/Payments/MyPayments', [
            'analytics' => $data,
            'filters'   => [
                'status'     => $status,
                'course'     => $course,
                'date_range' => $dateRange,
                'search'     => $search,
                'page'       => $page,
            ]
        ]);
    }

    public function methods(Request $request)
    {
        return Inertia::render('Student/Payments/PendingPayments');
    }

    public function transactions(Request $request)
    {
        $status = $request->query('status', 'all');
        $method = $request->query('method', 'all');
        $sort = $request->query('sort', 'newest');
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);

        $data = app(CourseFeeInvoiceService::class)->getTransactionHistoryData(
            $request->user(),
            $status,
            $method,
            $sort,
            $search,
            $page
        );

        return Inertia::render('Student/Payments/PaymentHistory', [
            'analytics' => $data,
            'filters'   => [
                'status' => $status,
                'method' => $method,
                'sort'   => $sort,
                'search' => $search,
                'page'   => $page,
            ]
        ]);
    }

    public function settings(Request $request)
    {
        return Inertia::render('Student/Payments/ReceiptsInvoices');
    }

    public function pending(Request $request)
    {
        $invoiceNo = $request->query('invoice', 'INV-2025-0012');
        $sessionData = app(CourseFeeInvoiceService::class)->getAbaPaymentSessionData(
            $invoiceNo,
            $request->user()
        );

        return Inertia::render('Student/Payments/PendingPayments', [
            'sessionData' => $sessionData
        ]);
    }

    public function history(Request $request)
    {
        return $this->transactions($request);
    }

    public function receipts(Request $request)
    {
        return Inertia::render('Student/Payments/ReceiptsInvoices');
    }

    public function create(Course $course)
    {
        return Inertia::render('Student/Payments/Upload', [
            'course' => $course
        ]);
    }

    public function store(Request $request, Course $course)
    {
        abort_unless($course->status === 'published' && $course->is_paid, 400);

        // R8: ការពារបង់ស្ទួន
        abort_if(Payment::where('student_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['pending', 'verifying', 'paid'])->exists(), 409, 'អ្នកបានបង់ ឬកំពុងរង់ចាំផ្ទៀងផ្ទាត់រួចហើយ');

        $data = $request->validate([
            'payment_slip' => 'required|image|max:4096',
            'aba_transaction_id' => 'nullable|string|max:100|unique:payments',
        ]);

        $payment = Payment::create([
            'student_id' => $request->user()?->id,
            'course_id'  => $course->id,
            'teacher_id' => $course->teacher_id,
            'amount'     => $course->price,
            'payment_slip' => $request->file('payment_slip')->store('slips', 'public'),
            'aba_transaction_id' => $data['aba_transaction_id'] ?? null,
            'status'     => 'verifying',
        ]);

        Enrollment::firstOrCreate(
            ['student_id' => $request->user()?->id, 'course_id' => $course->id],
            ['status' => 'pending_payment']
        );

        return back()->with('success', 'បានបញ្ជូនការបង់ប្រាក់ — រង់ចាំ Admin ផ្ទៀងផ្ទាត់');
    }
}

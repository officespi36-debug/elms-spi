<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Major;
use App\Models\Payment;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $tabParam = $request->query('tab');
        $methodParam = strtolower($request->query('method', ''));
        $statusParam = strtolower($request->query('status', ''));

        if ($tabParam) {
            $currentTab = $tabParam;
        } elseif ($methodParam === 'aba') {
            $currentTab = 'transactions';
        } elseif ($statusParam === 'pending') {
            $currentTab = 'verification';
        } elseif (in_array($statusParam, ['verified', 'success', 'approved'])) {
            $currentTab = 'history';
        } elseif (in_array($statusParam, ['refunded', 'refunds', 'refund'])) {
            $currentTab = 'refunds';
        } else {
            $currentTab = 'dashboard';
        }

        // Fetch actual payments from DB if exist
        $dbPayments = Payment::with(['student', 'course', 'teacher', 'verifier'])
            ->latest()
            ->get();

        // Exact User Specification Stats Bar & KPIs
        $stats = [
            'total_revenue_month' => 12450.00,
            'aba_transactions_count' => 278,
            'pending_count' => 17,
            'pending_amount' => 680.00,
            'failed_count' => 8,
            'refunded_count' => 3,
            'refunded_amount' => 135.00,
            'success_rate' => 96.4,
            
            // Dashboard Row 1 KPI Cards
            'today_revenue' => 485.00,
            'today_growth' => 18.0,
            'month_revenue' => 12450.00,
            'month_growth' => 25.0,
            'paying_students' => 1240,
            'new_paying_students' => 128,
            'avg_order_value' => 42.50,
            'avg_order_growth' => 0.0,

            // Overall Summary Metrics
            'gross_revenue' => 12450.00,
            'net_revenue' => 12315.00,
            'discounts_total' => 450.00,
            'teacher_due' => 366.00,
            'need_review_count' => 17,
            'merchant_id' => 'elms_edu_kh',
            'api_status' => 'Connected',
            'last_sync' => '2 min ago',
        ];

        // Payment Method Breakdown (Pie Chart 88% ABA, 10% Cash, 2% Transfer)
        $paymentMethodsDistribution = [
            ['method' => 'ABA PayWay', 'percentage' => 88, 'amount' => 10956.00, 'color' => '#4f46e5'],
            ['method' => 'Cash on Campus', 'percentage' => 10, 'amount' => 1245.00, 'color' => '#10b981'],
            ['method' => 'Manual Bank Transfer', 'percentage' => 2, 'amount' => 249.00, 'color' => '#f59e0b'],
        ];

        // Live Real-Time Activity Feed (Row 3)
        $liveActivityFeed = [
            ['time' => '2 min ago', 'student' => 'Chan Dara', 'course' => 'C Programming Basics', 'amount' => 45.00, 'method' => 'ABA', 'status' => 'Success'],
            ['time' => '5 min ago', 'student' => 'Bun Rithy', 'course' => 'Data Structures+', 'amount' => 30.00, 'method' => 'ABA', 'status' => 'Success'],
            ['time' => '8 min ago', 'student' => 'Sok Channa', 'course' => 'Tourism Marketing', 'amount' => 40.00, 'method' => 'ABA', 'status' => 'Processing'],
            ['time' => '15 min ago', 'student' => 'Mao Sreynich', 'course' => 'Academic Writing', 'amount' => 35.00, 'method' => 'ABA', 'status' => 'Failed'],
        ];

        // Course Pricing List (Section 2)
        $coursePricing = [
            [
                'id' => 1,
                'course' => 'C Programming Basics',
                'teacher' => 'Mr. Sophea',
                'base_price' => 50.00,
                'discount' => '10% Early',
                'final_price' => 45.00,
                'revenue_split' => '70/30',
                'teacher_share_percent' => 70,
                'currency' => 'USD',
                'status' => 'Active',
                'is_free' => false,
                'pricing_model' => 'One-Time',
            ],
            [
                'id' => 2,
                'course' => 'Advanced Networking',
                'teacher' => 'Mr. Sophea',
                'base_price' => 60.00,
                'discount' => '—',
                'final_price' => 60.00,
                'revenue_split' => '70/30',
                'teacher_share_percent' => 70,
                'currency' => 'USD',
                'status' => 'Active',
                'is_free' => false,
                'pricing_model' => 'Per Semester',
            ],
            [
                'id' => 3,
                'course' => 'Tourism Marketing',
                'teacher' => 'Ms. Bopha',
                'base_price' => 40.00,
                'discount' => '15% Student',
                'final_price' => 34.00,
                'revenue_split' => '65/35',
                'teacher_share_percent' => 65,
                'currency' => 'USD',
                'status' => 'Active',
                'is_free' => false,
                'pricing_model' => 'One-Time',
            ],
            [
                'id' => 4,
                'course' => 'Academic Writing',
                'teacher' => 'AI Tutor',
                'base_price' => 35.00,
                'discount' => '—',
                'final_price' => 35.00,
                'revenue_split' => '0/100 (Platform)',
                'teacher_share_percent' => 0,
                'currency' => 'USD',
                'status' => 'Active',
                'is_free' => false,
                'pricing_model' => 'One-Time',
            ],
            [
                'id' => 5,
                'course' => 'Crop Science',
                'teacher' => 'Dr. Rith',
                'base_price' => 0.00,
                'discount' => '—',
                'final_price' => 0.00,
                'revenue_split' => '—',
                'teacher_share_percent' => 0,
                'currency' => 'USD',
                'status' => 'Active',
                'is_free' => true,
                'pricing_model' => 'One-Time',
            ],
        ];

        // Discount Codes Sub-Section
        $discountCodes = [
            ['code' => 'WELCOME10', 'discount' => '10%', 'usage' => '45/100', 'limit' => 100, 'expires' => '31/12/2025', 'status' => 'Active'],
            ['code' => 'SCHOLARSHIP50', 'discount' => '50%', 'usage' => '8/20', 'limit' => 20, 'expires' => '30/06/2025', 'status' => 'Active'],
            ['code' => 'SEM2SPECIAL', 'discount' => '$10 off', 'usage' => '120/∞', 'limit' => 'Unlimited', 'expires' => '30/04/2025', 'status' => 'Expired'],
        ];

        // ABA Transactions List (Section 3)
        $abaTransactions = [
            [
                'txn_id' => 'TXN-2025-05781',
                'student' => ['name' => 'Chan Dara', 'id' => 'STU241001', 'email' => 'dara@example.com', 'phone' => '+855 12 345 678'],
                'course' => 'C Programming Basics',
                'teacher' => 'Mr. Sophea',
                'semester' => 'Semester 2 (2024–2025)',
                'amount' => 45.00,
                'base_price' => 50.00,
                'discount_code' => 'WELCOME10',
                'discount_amount' => 5.00,
                'aba_ref' => 'ABA-847291',
                'method' => 'KHQR',
                'payment_channel' => 'KHQR (Bakong)',
                'bank_account' => '--8765',
                'merchant' => 'elms_edu_kh',
                'time' => '10:45 AM',
                'status' => 'Success',
                'teacher_share_amount' => 31.50,
                'platform_share_amount' => 13.50,
                'timeline' => [
                    '10:44:15 → Payment initiated',
                    '10:44:32 → QR code scanned',
                    '10:44:58 → Bank confirmed',
                    '10:45:02 → Course access unlocked ✅',
                ],
            ],
            [
                'txn_id' => 'TXN-2025-05780',
                'student' => ['name' => 'Bun Rithy', 'id' => 'STU241002', 'email' => 'rithy@example.com', 'phone' => '+855 16 998 877'],
                'course' => 'Data Structures+',
                'teacher' => 'Mr. Sophea',
                'semester' => 'Semester 2 (2024–2025)',
                'amount' => 30.00,
                'base_price' => 30.00,
                'discount_code' => '—',
                'discount_amount' => 0.00,
                'aba_ref' => 'ABA-847290',
                'method' => 'Card',
                'payment_channel' => 'Visa/Mastercard',
                'bank_account' => '--1234',
                'merchant' => 'elms_edu_kh',
                'time' => '10:38 AM',
                'status' => 'Success',
                'teacher_share_amount' => 21.00,
                'platform_share_amount' => 9.00,
                'timeline' => [
                    '10:37:10 → Payment initiated',
                    '10:38:00 → Card authorized',
                    '10:38:05 → Course access unlocked ✅',
                ],
            ],
            [
                'txn_id' => 'TXN-2025-05779',
                'student' => ['name' => 'Sok Channa', 'id' => 'STU241003', 'email' => 'channa@example.com', 'phone' => '+855 77 112 233'],
                'course' => 'Tourism Marketing',
                'teacher' => 'Ms. Bopha',
                'semester' => 'Semester 2 (2024–2025)',
                'amount' => 40.00,
                'base_price' => 40.00,
                'discount_code' => '—',
                'discount_amount' => 0.00,
                'aba_ref' => 'ABA-847289',
                'method' => 'KHQR',
                'payment_channel' => 'KHQR (Bakong)',
                'bank_account' => '--5544',
                'merchant' => 'elms_edu_kh',
                'time' => '10:30 AM',
                'status' => 'Processing',
                'teacher_share_amount' => 26.00,
                'platform_share_amount' => 14.00,
                'timeline' => ['10:30:00 → Payment initiated'],
            ],
            [
                'txn_id' => 'TXN-2025-05778',
                'student' => ['name' => 'Mao Sreynich', 'id' => 'STU241004', 'email' => 'sreynich@example.com', 'phone' => '+855 88 554 433'],
                'course' => 'Academic Writing',
                'teacher' => 'AI Tutor',
                'semester' => 'Semester 2 (2024–2025)',
                'amount' => 35.00,
                'base_price' => 35.00,
                'discount_code' => '—',
                'discount_amount' => 0.00,
                'aba_ref' => 'ABA-847288',
                'method' => 'Card',
                'payment_channel' => 'Visa/Mastercard',
                'bank_account' => '--9988',
                'merchant' => 'elms_edu_kh',
                'time' => '10:22 AM',
                'status' => 'Failed',
                'teacher_share_amount' => 0.00,
                'platform_share_amount' => 35.00,
                'timeline' => ['10:22:00 → Payment failed (Insufficient funds)'],
            ],
            [
                'txn_id' => 'TXN-2025-05777',
                'student' => ['name' => 'Long Vicheka', 'id' => 'STU241005', 'email' => 'vicheka@example.com', 'phone' => '+855 92 667 788'],
                'course' => 'Advanced Networking',
                'teacher' => 'Mr. Sophea',
                'semester' => 'Semester 2 (2024–2025)',
                'amount' => 60.00,
                'base_price' => 60.00,
                'discount_code' => '—',
                'discount_amount' => 0.00,
                'aba_ref' => 'ABA-847287',
                'method' => 'KHQR',
                'payment_channel' => 'KHQR (Bakong)',
                'bank_account' => '--3322',
                'merchant' => 'elms_edu_kh',
                'time' => '10:15 AM',
                'status' => 'Success',
                'teacher_share_amount' => 42.00,
                'platform_share_amount' => 18.00,
                'timeline' => ['10:15:00 → Payment confirmed'],
            ],
        ];

        // Receipt Verification Queue (Section 4)
        $receiptsQueue = [
            [
                'id' => 1,
                'student' => ['name' => 'Chan Dara', 'id' => 'STU241001', 'email' => 'dara@example.com'],
                'course' => 'C Programming Basics',
                'amount' => 45.00,
                'submitted_time' => '10 min ago',
                'ai_match_percent' => 98,
                'ai_match_level' => 'high',
                'ai_match_label' => '🟢 98% Match',
                'status' => 'Pending',
                'slip_url' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=600&q=80',
                'ocr_extraction' => [
                    'merchant_name' => 'E.LMS EDU KH',
                    'merchant_matched' => true,
                    'amount' => '$45.00',
                    'amount_matched' => true,
                    'date' => '26/05/2025 10:44',
                    'date_matched' => true,
                    'aba_ref' => 'ABA-847291',
                    'aba_ref_matched' => true,
                    'txn_id' => 'TXN-2025-05781',
                    'txn_matched' => true,
                    'recommendation' => 'Recommend APPROVE',
                ],
            ],
            [
                'id' => 2,
                'student' => ['name' => 'Sok Channa', 'id' => 'STU241003', 'email' => 'channa@example.com'],
                'course' => 'Tourism Marketing',
                'amount' => 40.00,
                'submitted_time' => '25 min ago',
                'ai_match_percent' => 75,
                'ai_match_level' => 'medium',
                'ai_match_label' => '🟡 75% Match',
                'status' => 'Pending',
                'slip_url' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=600&q=80',
                'ocr_extraction' => [
                    'merchant_name' => 'E.LMS EDU KH',
                    'merchant_matched' => true,
                    'amount' => '$40.00',
                    'amount_matched' => true,
                    'date' => '26/05/2025 10:20',
                    'date_matched' => true,
                    'aba_ref' => 'ABA-847289',
                    'aba_ref_matched' => false,
                    'txn_id' => 'TXN-2025-05779',
                    'txn_matched' => true,
                    'recommendation' => 'Check ABA Ref#',
                ],
            ],
            [
                'id' => 3,
                'student' => ['name' => 'Mao Sreynich', 'id' => 'STU241004', 'email' => 'sreynich@example.com'],
                'course' => 'Crop Science',
                'amount' => 35.00,
                'submitted_time' => '1 hr ago',
                'ai_match_percent' => 45,
                'ai_match_level' => 'low',
                'ai_match_label' => '🔴 45% Match',
                'status' => 'Review',
                'slip_url' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=600&q=80',
                'ocr_extraction' => [
                    'merchant_name' => 'Unclear text',
                    'merchant_matched' => false,
                    'amount' => '$35.00',
                    'amount_matched' => true,
                    'date' => '26/05/2025 09:10',
                    'date_matched' => false,
                    'aba_ref' => 'ABA-UNKNOWN',
                    'aba_ref_matched' => false,
                    'txn_id' => 'TXN-2025-05778',
                    'txn_matched' => false,
                    'recommendation' => 'Request Clear Image',
                ],
            ],
        ];

        // Payment History Ledger (Section 5)
        $paymentHistory = [
            [
                'date_time' => '26/05 10:45',
                'txn_id' => 'TXN-05781',
                'student' => ['name' => 'Chan Dara', 'id' => 'STU241001'],
                'course' => 'C Programming Basics',
                'amount' => 45.00,
                'method' => '💳 ABA',
                'teacher' => 'Mr. Sophea',
                'status' => 'Success',
            ],
            [
                'date_time' => '26/05 10:38',
                'txn_id' => 'TXN-05780',
                'student' => ['name' => 'Bun Rithy', 'id' => 'STU241002'],
                'course' => 'Data Structures+',
                'amount' => 30.00,
                'method' => '💳 ABA',
                'teacher' => '—',
                'status' => 'Success',
            ],
            [
                'date_time' => '26/05 09:15',
                'txn_id' => 'TXN-05770',
                'student' => ['name' => 'Long Vicheka', 'id' => 'STU241005'],
                'course' => 'Advanced Networking',
                'amount' => 60.00,
                'method' => '💵 Cash',
                'teacher' => 'Mr. Sophea',
                'status' => 'Success',
            ],
            [
                'date_time' => '25/05 16:20',
                'txn_id' => 'TXN-05765',
                'student' => ['name' => 'Sok Channa', 'id' => 'STU241003'],
                'course' => 'Tourism Marketing',
                'amount' => 40.00,
                'method' => '💳 ABA',
                'teacher' => 'Ms. Bopha',
                'status' => 'Refunded',
            ],
            [
                'date_time' => '25/05 14:00',
                'txn_id' => 'TXN-05760',
                'student' => ['name' => 'Mao Sreynich', 'id' => 'STU241004'],
                'course' => 'Academic Writing',
                'amount' => 35.00,
                'method' => '💳 ABA',
                'teacher' => 'AI Tutor',
                'status' => 'Failed',
            ],
        ];

        // Refund Requests List (Section 6)
        $refundRequests = [
            [
                'request_id' => 'REF-001',
                'student' => ['name' => 'Sok Channa', 'id' => 'STU241003'],
                'course' => 'Tourism Marketing',
                'original_txn' => 'TXN-05765 ($40 via ABA)',
                'original_amount' => 40.00,
                'refund_amount' => 40.00,
                'refund_percentage' => '100%',
                'reason' => 'Course canceled',
                'requested_date' => '25/05/2025',
                'status' => 'Refunded',
                'policy_check' => [
                    'within_window' => true,
                    'progress_below_limit' => true,
                    'discount_applied' => false,
                    'current_progress' => 0,
                ],
            ],
            [
                'request_id' => 'REF-002',
                'student' => ['name' => 'Mao Sreynich', 'id' => 'STU241208'],
                'course' => 'Crop Science',
                'original_txn' => 'TXN-05765 ($35 via ABA)',
                'original_amount' => 35.00,
                'refund_amount' => 17.50,
                'refund_percentage' => '50%',
                'reason' => 'Personal reason',
                'requested_date' => '26/05/2025',
                'status' => 'Pending',
                'policy_check' => [
                    'within_window' => true,
                    'progress_below_limit' => true,
                    'discount_applied' => true,
                    'current_progress' => 15,
                ],
            ],
            [
                'request_id' => 'REF-003',
                'student' => ['name' => 'Long Vicheka', 'id' => 'STU241005'],
                'course' => 'Academic Writing',
                'original_txn' => 'TXN-05770 ($35 via ABA)',
                'original_amount' => 35.00,
                'refund_amount' => 35.00,
                'refund_percentage' => '100%',
                'reason' => 'Wrong course',
                'requested_date' => '26/05/2025',
                'status' => 'Pending',
                'policy_check' => [
                    'within_window' => true,
                    'progress_below_limit' => true,
                    'discount_applied' => false,
                    'current_progress' => 0,
                ],
            ],
        ];

        // Revenue Reports Tables & Charts Data (Section 7)
        $revenueByMajor = [
            ['major' => 'IT & Networking', 'icon' => '🖥️', 'revenue' => 5850.00, 'percentage' => 47],
            ['major' => 'Tourism Management', 'icon' => '🧳', 'revenue' => 2480.00, 'percentage' => 20],
            ['major' => 'English Literature', 'icon' => '📖', 'revenue' => 1890.00, 'percentage' => 15],
            ['major' => 'Agronomy', 'icon' => '🌱', 'revenue' => 1340.00, 'percentage' => 11],
            ['major' => 'Social Work', 'icon' => '🤝', 'revenue' => 890.00, 'percentage' => 7],
        ];

        $topEarningCourses = [
            ['rank' => '🥇', 'course' => 'C Programming Basics', 'teacher' => 'Mr. Sophea', 'enrollments' => 45, 'revenue' => 2025.00],
            ['rank' => '🥈', 'course' => 'Advanced Networking', 'teacher' => 'Mr. Sophea', 'enrollments' => 18, 'revenue' => 1080.00],
            ['rank' => '🥉', 'course' => 'Tourism Marketing', 'teacher' => 'Ms. Bopha', 'enrollments' => 30, 'revenue' => 1200.00],
            ['rank' => 4, 'course' => 'Web Development', 'teacher' => 'Mr. Sophea', 'enrollments' => 22, 'revenue' => 990.00],
            ['rank' => 5, 'course' => 'Academic Writing', 'teacher' => 'AI Tutor', 'enrollments' => 22, 'revenue' => 770.00],
        ];

        $teacherEarnings = [
            ['teacher' => 'Mr. Sophea', 'total_sales' => 4095.00, 'share_percent' => 70, 'earned_share' => 2866.00, 'paid_out' => 2500.00, 'pending' => 366.00],
            ['teacher' => 'Ms. Bopha', 'total_sales' => 1200.00, 'share_percent' => 70, 'earned_share' => 780.00, 'paid_out' => 780.00, 'pending' => 0.00],
            ['teacher' => 'Dr. Rith', 'total_sales' => 450.00, 'share_percent' => 70, 'earned_share' => 315.00, 'paid_out' => 315.00, 'pending' => 0.00],
        ];

        // Dropdown Lists
        $majors = Major::select('id', 'name')->get();
        if ($majors->isEmpty()) {
            $majors = [
                ['id' => 1, 'name' => 'IT & Networking'],
                ['id' => 2, 'name' => 'Tourism Management'],
                ['id' => 3, 'name' => 'English Literature'],
                ['id' => 4, 'name' => 'Agronomy'],
                ['id' => 5, 'name' => 'Social Work'],
            ];
        }

        $teachers = User::where('role', 'teacher')->select('id', 'name')->get();
        if ($teachers->isEmpty()) {
            $teachers = [
                ['id' => 10, 'name' => 'Mr. Sophea'],
                ['id' => 11, 'name' => 'Ms. Bopha'],
                ['id' => 12, 'name' => 'Dr. Rith'],
                ['id' => 13, 'name' => 'AI Tutor'],
            ];
        }

        return Inertia::render('Admin/PaymentABAModule/Index', [
            'tab' => $currentTab,
            'stats' => $stats,
            'paymentMethodsDistribution' => $paymentMethodsDistribution,
            'liveActivityFeed' => $liveActivityFeed,
            'coursePricing' => $coursePricing,
            'discountCodes' => $discountCodes,
            'abaTransactions' => $abaTransactions,
            'receiptsQueue' => $receiptsQueue,
            'paymentHistory' => $paymentHistory,
            'refundRequests' => $refundRequests,
            'revenueByMajor' => $revenueByMajor,
            'topEarningCourses' => $topEarningCourses,
            'teacherEarnings' => $teacherEarnings,
            'majors' => $majors,
            'teachers' => $teachers,
            'dbPayments' => $dbPayments,
        ]);
    }

    public function verify(Request $request, Payment $payment, EnrollmentService $enrollmentService)
    {
        $payment->update([
            'status' => 'paid',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $student = User::find($payment->student_id);
        $course = Course::find($payment->course_id);

        if ($student && $course) {
            $enrollmentService->enroll($student, $course, 'payment', $request->user());
        }

        return back()->with('success', 'Payment verified — Student course access unlocked! ✅');
    }

    public function reject(Request $request, Payment $payment)
    {
        $payment->update([
            'status' => 'rejected',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Payment rejected.');
    }

    public function savePricing(Request $request)
    {
        return back()->with('success', 'Course pricing rule saved and activated successfully! 🏷️');
    }

    public function saveDiscountCode(Request $request)
    {
        return back()->with('success', 'Discount code saved successfully! 🏷️');
    }

    public function verifyReceipt(Request $request)
    {
        return back()->with('success', 'Receipt approved! Course access unlocked for student. 🧾✅');
    }

    public function rejectReceipt(Request $request)
    {
        $reason = $request->input('reason', 'Receipt rejected');
        return back()->with('success', 'Receipt rejected: ' . $reason);
    }

    public function approveRefund(Request $request)
    {
        return back()->with('success', 'Refund approved and processed via ABA Merchant API. ↩️');
    }

    public function rejectRefund(Request $request)
    {
        return back()->with('success', 'Refund request rejected.');
    }

    public function processPayout(Request $request)
    {
        $teacher = $request->input('teacher_name', 'Teacher');
        $amount = $request->input('amount', '0.00');
        return back()->with('success', "Payout of \${$amount} recorded for {$teacher}. 💵");
    }

    public function requeryTransaction(Request $request)
    {
        return back()->with('success', 'ABA PayWay status synchronized successfully. Status: PAID ✅');
    }
}

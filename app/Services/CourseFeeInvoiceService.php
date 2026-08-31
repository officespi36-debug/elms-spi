<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;

class CourseFeeInvoiceService
{
    /**
     * Get structured Course Fees & Invoices dashboard data.
     */
    public function getMyPaymentsData(?User $user = null, string $status = 'all', string $course = 'all', string $dateRange = 'all', string $search = '', int $page = 1, int $perPage = 10): array
    {
        // 1. Top 5 Summary Cards
        $summary = [
            'total_due'         => '$180.00',
            'total_due_raw'     => 180.00,
            'total_due_note'    => 'Outstanding balance',
            'paid_amount'       => '$1,320.00',
            'paid_amount_raw'   => 1320.00,
            'paid_amount_note'  => 'Total paid',
            'total_invoices'    => 12,
            'total_inv_note'    => 'All invoices',
            'paid_invoices'     => 9,
            'paid_inv_note'     => 'Paid successfully',
            'pending_invoices'  => 3,
            'pending_inv_note'  => 'Awaiting payment',
        ];

        // 2. Payment Summary Donut Chart
        $paymentSummary = [
            'total_amount' => '$1,500',
            'total_note'   => 'All time',
            'items'        => [
                ['label' => 'Paid',    'amount' => '$1,320.00', 'percentage' => 88, 'color' => '#10B981'],
                ['label' => 'Pending', 'amount' => '$280.00',   'percentage' => 12, 'color' => '#F59E0B'],
                ['label' => 'Overdue', 'amount' => '$180.00',   'percentage' => 8,  'color' => '#EF4444'],
            ]
        ];

        // 3. Upcoming Payments Widget
        $upcomingPayments = [
            [
                'id'          => 1,
                'course'      => 'UI/UX Design Basics',
                'due_date'    => 'Due on May 20, 2025',
                'amount'      => '$120.00',
                'status'      => 'Pending',
                'status_type' => 'pending',
                'color'       => 'amber',
            ],
            [
                'id'          => 2,
                'course'      => 'Node.js Backend',
                'due_date'    => 'Due on May 08, 2025',
                'amount'      => '$180.00',
                'status'      => 'Pending',
                'status_type' => 'pending',
                'color'       => 'amber',
            ],
            [
                'id'          => 3,
                'course'      => 'JavaScript Advanced',
                'due_date'    => 'Due on May 15, 2025',
                'amount'      => '$180.00',
                'status'      => 'Overdue',
                'status_type' => 'overdue',
                'color'       => 'rose',
            ],
        ];

        // 4. Recent Transactions Widget
        $recentTransactions = [
            [
                'id'             => 1,
                'title'          => 'Payment Received',
                'date'           => 'May 28, 2025 10:30 AM',
                'amount'         => '+$120.00',
                'amount_type'    => 'positive',
                'invoice_number' => 'INV-2025-0012',
                'method'         => 'ABA Bank',
            ],
            [
                'id'             => 2,
                'title'          => 'Payment Received',
                'date'           => 'May 25, 2025 09:15 AM',
                'amount'         => '+$150.00',
                'amount_type'    => 'positive',
                'invoice_number' => 'INV-2025-0011',
                'method'         => 'Visa Card',
            ],
            [
                'id'             => 3,
                'title'          => 'Payment Received',
                'date'           => 'May 20, 2025 02:45 PM',
                'amount'         => '+$100.00',
                'amount_type'    => 'positive',
                'invoice_number' => 'INV-2025-0010',
                'method'         => 'ABA Bank',
            ],
            [
                'id'             => 4,
                'title'          => 'Payment Pending',
                'date'           => 'May 10, 2025 11:20 AM',
                'amount'         => '-$120.00',
                'amount_type'    => 'negative',
                'invoice_number' => 'INV-2025-0008',
                'method'         => 'Pending',
            ],
        ];

        // 5. Invoices Catalog (10 items matching reference screenshot)
        $invoices = [
            [
                'id'                 => 1,
                'invoice_number'     => 'INV-2025-0012',
                'course_name'        => 'Web Development Fundamentals',
                'invoice_date'       => 'May 28, 2025',
                'due_date'           => 'Jun 07, 2025',
                'amount'             => '$120.00',
                'amount_raw'         => 120.00,
                'status'             => 'Paid',
                'status_type'        => 'paid',
                'payment_method'     => 'ABA Bank',
                'payment_method_sub' => '•••• 4567',
                'transaction_id'     => 'TXN-ABA-98421045',
                'receipt_number'     => 'REC-2025-0012',
            ],
            [
                'id'                 => 2,
                'invoice_number'     => 'INV-2025-0011',
                'course_name'        => 'React Development',
                'invoice_date'       => 'May 25, 2025',
                'due_date'           => 'Jun 04, 2025',
                'amount'             => '$150.00',
                'amount_raw'         => 150.00,
                'status'             => 'Paid',
                'status_type'        => 'paid',
                'payment_method'     => 'Visa Card',
                'payment_method_sub' => '•••• 7890',
                'transaction_id'     => 'TXN-VISA-67210982',
                'receipt_number'     => 'REC-2025-0011',
            ],
            [
                'id'                 => 3,
                'invoice_number'     => 'INV-2025-0010',
                'course_name'        => 'Database Design',
                'invoice_date'       => 'May 20, 2025',
                'due_date'           => 'May 30, 2025',
                'amount'             => '$100.00',
                'amount_raw'         => 100.00,
                'status'             => 'Paid',
                'status_type'        => 'paid',
                'payment_method'     => 'ABA Bank',
                'payment_method_sub' => '•••• 4567',
                'transaction_id'     => 'TXN-ABA-54120984',
                'receipt_number'     => 'REC-2025-0010',
            ],
            [
                'id'                 => 4,
                'invoice_number'     => 'INV-2025-0009',
                'course_name'        => 'Python Programming',
                'invoice_date'       => 'May 15, 2025',
                'due_date'           => 'May 25, 2025',
                'amount'             => '$150.00',
                'amount_raw'         => 150.00,
                'status'             => 'Paid',
                'status_type'        => 'paid',
                'payment_method'     => 'Wing',
                'payment_method_sub' => '•••• 1234',
                'transaction_id'     => 'TXN-WING-45129871',
                'receipt_number'     => 'REC-2025-0009',
            ],
            [
                'id'                 => 5,
                'invoice_number'     => 'INV-2025-0008',
                'course_name'        => 'UI/UX Design Basics',
                'invoice_date'       => 'May 10, 2025',
                'due_date'           => 'May 20, 2025',
                'amount'             => '$120.00',
                'amount_raw'         => 120.00,
                'status'             => 'Pending',
                'status_type'        => 'pending',
                'payment_method'     => '—',
                'payment_method_sub' => '',
                'transaction_id'     => null,
                'receipt_number'     => null,
            ],
            [
                'id'                 => 6,
                'invoice_number'     => 'INV-2025-0007',
                'course_name'        => 'JavaScript Advanced',
                'invoice_date'       => 'May 05, 2025',
                'due_date'           => 'May 15, 2025',
                'amount'             => '$180.00',
                'amount_raw'         => 180.00,
                'status'             => 'Overdue',
                'status_type'        => 'overdue',
                'payment_method'     => '—',
                'payment_method_sub' => '',
                'transaction_id'     => null,
                'receipt_number'     => null,
            ],
            [
                'id'                 => 7,
                'invoice_number'     => 'INV-2025-0006',
                'course_name'        => 'Node.js Backend',
                'invoice_date'       => 'Apr 28, 2025',
                'due_date'           => 'May 08, 2025',
                'amount'             => '$180.00',
                'amount_raw'         => 180.00,
                'status'             => 'Pending',
                'status_type'        => 'pending',
                'payment_method'     => '—',
                'payment_method_sub' => '',
                'transaction_id'     => null,
                'receipt_number'     => null,
            ],
            [
                'id'                 => 8,
                'invoice_number'     => 'INV-2025-0005',
                'course_name'        => 'Data Science Basics',
                'invoice_date'       => 'Apr 20, 2025',
                'due_date'           => 'Apr 30, 2025',
                'amount'             => '$150.00',
                'amount_raw'         => 150.00,
                'status'             => 'Paid',
                'status_type'        => 'paid',
                'payment_method'     => 'ABA Bank',
                'payment_method_sub' => '•••• 4567',
                'transaction_id'     => 'TXN-ABA-34120985',
                'receipt_number'     => 'REC-2025-0005',
            ],
            [
                'id'                 => 9,
                'invoice_number'     => 'INV-2025-0004',
                'course_name'        => 'Git & GitHub',
                'invoice_date'       => 'Apr 15, 2025',
                'due_date'           => 'Apr 25, 2025',
                'amount'             => '$100.00',
                'amount_raw'         => 100.00,
                'status'             => 'Paid',
                'status_type'        => 'paid',
                'payment_method'     => 'Visa Card',
                'payment_method_sub' => '•••• 7890',
                'transaction_id'     => 'TXN-VISA-23109842',
                'receipt_number'     => 'REC-2025-0004',
            ],
            [
                'id'                 => 10,
                'invoice_number'     => 'INV-2025-0003',
                'course_name'        => 'HTML & CSS Essentials',
                'invoice_date'       => 'Apr 10, 2025',
                'due_date'           => 'Apr 20, 2025',
                'amount'             => '$100.00',
                'amount_raw'         => 100.00,
                'status'             => 'Paid',
                'status_type'        => 'paid',
                'payment_method'     => 'Wing',
                'payment_method_sub' => '•••• 1234',
                'transaction_id'     => 'TXN-WING-12908341',
                'receipt_number'     => 'REC-2025-0003',
            ],
        ];

        // Filter by status tab
        if ($status !== 'all') {
            $invoices = array_values(array_filter($invoices, fn($inv) => strtolower($inv['status_type']) === strtolower($status)));
        }

        // Filter by course
        if ($course !== 'all') {
            $invoices = array_values(array_filter($invoices, fn($inv) => strtolower($inv['course_name']) === strtolower($course)));
        }

        // Filter by search
        if (!empty($search)) {
            $s = strtolower($search);
            $invoices = array_values(array_filter($invoices, fn($inv) => str_contains(strtolower($inv['invoice_number']), $s) || str_contains(strtolower($inv['course_name']), $s)));
        }

        return [
            'summary'             => $summary,
            'payment_summary'     => $paymentSummary,
            'upcoming_payments'   => $upcomingPayments,
            'recent_transactions' => $recentTransactions,
            'invoices'            => $invoices,
            'total_count'         => 12,
            'current_page'        => $page,
            'per_page'            => $perPage,
        ];
    }

    /**
     * Get ABA KHQR Payment Session data for pending invoice.
     */
    public function getAbaPaymentSessionData(?string $invoiceNumber = null, ?User $user = null): array
    {
        $invoiceNo = $invoiceNumber ?: 'INV-2025-0012';

        return [
            'session_id'         => 'PAY-SESSION-2025-000124',
            'invoice_number'     => $invoiceNo,
            'course_name'        => 'Web Development Fundamentals',
            'course_type'        => 'Self-Paced Course',
            'student_name'       => $user?->name ?: 'Sok Pisey',
            'student_id'         => 'STU2024001',
            'amount_khr'         => '120,000 KHR',
            'amount_khr_raw'     => 120000,
            'amount_usd'         => '≈ $30.00 USD',
            'amount_usd_raw'     => 30.00,
            'merchant_name'      => 'Saint Paul Institute',
            'merchant_id'        => '002 328 456',
            'due_date'           => 'June 07, 2025',
            'due_date_iso'       => '2025-06-07',
            'payment_method'     => 'ABA KHQR',
            'qr_code_url'        => 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=ABA_KHQR_SPI_INV20250012_120000KHR',
            'expires_in_seconds' => 588, // 09:48
            'status'             => 'WAITING_FOR_PAYMENT',
        ];
    }

    /**
     * Get structured Student Transaction History data.
     */
    public function getTransactionHistoryData(?User $user = null, string $status = 'all', string $method = 'all', string $sort = 'newest', string $search = '', int $page = 1, int $perPage = 10): array
    {
        // 1. Top 4 Summary Cards
        $summary = [
            'total_transactions'   => 12,
            'total_tx_note'        => 'All payment records',
            'successful_payments'  => 10,
            'successful_note'      => 'Completed transactions',
            'pending_payments'     => 2,
            'pending_note'         => 'Awaiting confirmation',
            'total_paid'           => '1,250,000 KHR',
            'total_paid_note'      => 'Total successful payments',
        ];

        // 2. Right Sidebar Payment Summary
        $paymentSummary = [
            'total_paid'       => '1,250,000 KHR',
            'progress_percent' => 83,
            'successful_count' => 10,
            'pending_count'    => 2,
            'failed_count'     => 0,
        ];

        // 3. Right Sidebar Latest Payment
        $latestPayment = [
            'course_name'    => 'Web Development Fundamentals',
            'amount'         => '120,000 KHR',
            'status'         => 'Payment Successful',
            'status_type'    => 'successful',
            'date_text'      => 'Today • 09:45 AM',
            'invoice_number' => 'INV-2025-0012',
            'transaction_id' => 'TRX-2025-00124',
        ];

        // 4. Transactions List (10 items)
        $transactions = [
            [
                'id'             => 1,
                'transaction_id' => 'TRX-2025-00124',
                'invoice_number' => 'INV-2025-0012',
                'course_name'    => 'Web Development Fundamentals',
                'payment_method' => 'ABA KHQR',
                'method_type'    => 'aba',
                'amount_khr'     => '120,000 KHR',
                'amount_raw'     => 120000,
                'date_formatted' => 'May 28, 2025',
                'time_formatted' => '09:45 AM',
                'date_time_text' => 'May 28, 2025 • 09:45 AM',
                'status'         => 'Successful',
                'status_type'    => 'successful',
                'reference_code' => 'REF-ABA-98421045',
            ],
            [
                'id'             => 2,
                'transaction_id' => 'TRX-2025-00123',
                'invoice_number' => 'INV-2025-0010',
                'course_name'    => 'Database Systems',
                'payment_method' => 'ABA KHQR',
                'method_type'    => 'aba',
                'amount_khr'     => '95,000 KHR',
                'amount_raw'     => 95000,
                'date_formatted' => 'May 20, 2025',
                'time_formatted' => '02:30 PM',
                'date_time_text' => 'May 20, 2025 • 02:30 PM',
                'status'         => 'Successful',
                'status_type'    => 'successful',
                'reference_code' => 'REF-ABA-54120984',
            ],
            [
                'id'             => 3,
                'transaction_id' => 'TRX-2025-00122',
                'invoice_number' => 'INV-2025-0009',
                'course_name'    => 'Python Programming',
                'payment_method' => 'ABA KHQR',
                'method_type'    => 'aba',
                'amount_khr'     => '80,000 KHR',
                'amount_raw'     => 80000,
                'date_formatted' => 'May 18, 2025',
                'time_formatted' => '11:15 AM',
                'date_time_text' => 'May 18, 2025 • 11:15 AM',
                'status'         => 'Pending',
                'status_type'    => 'pending',
                'reference_code' => 'REF-ABA-45129871',
            ],
            [
                'id'             => 4,
                'transaction_id' => 'TRX-2025-00121',
                'invoice_number' => 'INV-2025-0011',
                'course_name'    => 'React Development',
                'payment_method' => 'Card Payment',
                'method_type'    => 'card',
                'amount_khr'     => '150,000 KHR',
                'amount_raw'     => 150000,
                'date_formatted' => 'May 15, 2025',
                'time_formatted' => '10:00 AM',
                'date_time_text' => 'May 15, 2025 • 10:00 AM',
                'status'         => 'Successful',
                'status_type'    => 'successful',
                'reference_code' => 'REF-VISA-67210982',
            ],
            [
                'id'             => 5,
                'transaction_id' => 'TRX-2025-00120',
                'invoice_number' => 'INV-2025-0008',
                'course_name'    => 'UI/UX Design Basics',
                'payment_method' => 'Bank Transfer',
                'method_type'    => 'bank',
                'amount_khr'     => '120,000 KHR',
                'amount_raw'     => 120000,
                'date_formatted' => 'May 10, 2025',
                'time_formatted' => '04:20 PM',
                'date_time_text' => 'May 10, 2025 • 04:20 PM',
                'status'         => 'Pending',
                'status_type'    => 'pending',
                'reference_code' => 'REF-BANK-38910245',
            ],
            [
                'id'             => 6,
                'transaction_id' => 'TRX-2025-00119',
                'invoice_number' => 'INV-2025-0007',
                'course_name'    => 'JavaScript Advanced',
                'payment_method' => 'Wing',
                'method_type'    => 'wing',
                'amount_khr'     => '180,000 KHR',
                'amount_raw'     => 180000,
                'date_formatted' => 'May 05, 2025',
                'time_formatted' => '01:10 PM',
                'date_time_text' => 'May 05, 2025 • 01:10 PM',
                'status'         => 'Successful',
                'status_type'    => 'successful',
                'reference_code' => 'REF-WING-78451290',
            ],
            [
                'id'             => 7,
                'transaction_id' => 'TRX-2025-00118',
                'invoice_number' => 'INV-2025-0006',
                'course_name'    => 'Node.js Backend',
                'payment_method' => 'ABA KHQR',
                'method_type'    => 'aba',
                'amount_khr'     => '180,000 KHR',
                'amount_raw'     => 180000,
                'date_formatted' => 'Apr 28, 2025',
                'time_formatted' => '08:50 AM',
                'date_time_text' => 'Apr 28, 2025 • 08:50 AM',
                'status'         => 'Successful',
                'status_type'    => 'successful',
                'reference_code' => 'REF-ABA-98124578',
            ],
            [
                'id'             => 8,
                'transaction_id' => 'TRX-2025-00117',
                'invoice_number' => 'INV-2025-0005',
                'course_name'    => 'Data Science Basics',
                'payment_method' => 'ABA KHQR',
                'method_type'    => 'aba',
                'amount_khr'     => '150,000 KHR',
                'amount_raw'     => 150000,
                'date_formatted' => 'Apr 20, 2025',
                'time_formatted' => '03:40 PM',
                'date_time_text' => 'Apr 20, 2025 • 03:40 PM',
                'status'         => 'Successful',
                'status_type'    => 'successful',
                'reference_code' => 'REF-ABA-34120985',
            ],
            [
                'id'             => 9,
                'transaction_id' => 'TRX-2025-00116',
                'invoice_number' => 'INV-2025-0004',
                'course_name'    => 'Git & GitHub',
                'payment_method' => 'Card Payment',
                'method_type'    => 'card',
                'amount_khr'     => '100,000 KHR',
                'amount_raw'     => 100000,
                'date_formatted' => 'Apr 15, 2025',
                'time_formatted' => '09:30 AM',
                'date_time_text' => 'Apr 15, 2025 • 09:30 AM',
                'status'         => 'Successful',
                'status_type'    => 'successful',
                'reference_code' => 'REF-VISA-23109842',
            ],
            [
                'id'             => 10,
                'transaction_id' => 'TRX-2025-00115',
                'invoice_number' => 'INV-2025-0003',
                'course_name'    => 'HTML & CSS Essentials',
                'payment_method' => 'Wing',
                'method_type'    => 'wing',
                'amount_khr'     => '100,000 KHR',
                'amount_raw'     => 100000,
                'date_formatted' => 'Apr 10, 2025',
                'time_formatted' => '02:15 PM',
                'date_time_text' => 'Apr 10, 2025 • 02:15 PM',
                'status'         => 'Successful',
                'status_type'    => 'successful',
                'reference_code' => 'REF-WING-12908341',
            ],
        ];

        // Filter by status
        if ($status !== 'all') {
            $transactions = array_values(array_filter($transactions, fn($tx) => strtolower($tx['status_type']) === strtolower($status)));
        }

        // Filter by method
        if ($method !== 'all') {
            $transactions = array_values(array_filter($transactions, fn($tx) => strtolower($tx['method_type']) === strtolower($method)));
        }

        // Filter by search
        if (!empty($search)) {
            $s = strtolower($search);
            $transactions = array_values(array_filter($transactions, fn($tx) => str_contains(strtolower($tx['transaction_id']), $s) || str_contains(strtolower($tx['invoice_number']), $s) || str_contains(strtolower($tx['course_name']), $s)));
        }

        return [
            'summary'             => $summary,
            'payment_summary'     => $paymentSummary,
            'latest_payment'      => $latestPayment,
            'transactions'        => $transactions,
            'total_count'         => 12,
            'current_page'        => $page,
            'per_page'            => $perPage,
        ];
    }
}



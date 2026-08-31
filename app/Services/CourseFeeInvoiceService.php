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
}

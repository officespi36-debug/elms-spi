<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;

class CourseFeeInvoiceService
{
    private const USD_TO_KHR = 4000;

    /**
     * Get structured Course Fees & Invoices dashboard data from real database records.
     */
    public function getMyPaymentsData(
        ?User $user = null,
        string $status = 'all',
        string $course = 'all',
        string $dateRange = 'all',
        string $search = '',
        int $page = 1,
        int $perPage = 10
    ): array {
        if (!$user) {
            return $this->emptyMyPayments($page, $perPage);
        }

        $paymentsQuery = Payment::with('course')
            ->where('student_id', $user->id)
            ->orderByDesc('created_at');

        if ($dateRange === 'this_month') {
            $paymentsQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        } elseif ($dateRange === 'last_3_months') {
            $paymentsQuery->where('created_at', '>=', now()->subMonths(3));
        } elseif ($dateRange === 'this_year') {
            $paymentsQuery->whereYear('created_at', now()->year);
        }

        $allPayments = $paymentsQuery->get();

        // Also include unpaid pending_payment enrollments that do not yet have a Payment row
        $paidOrTrackedCourseIds = $allPayments->pluck('course_id')->filter()->unique()->all();
        $unpaidEnrollments = Enrollment::with('course')
            ->where('student_id', $user->id)
            ->where('status', 'pending_payment')
            ->when(!empty($paidOrTrackedCourseIds), fn ($q) => $q->whereNotIn('course_id', $paidOrTrackedCourseIds))
            ->get();

        $allInvoices = [];

        foreach ($allPayments as $payment) {
            $statusType = match ($payment->status) {
                'paid', 'approved', 'completed' => 'paid',
                'rejected', 'failed'            => 'overdue',
                default                         => 'pending',
            };
            $statusLabel = match ($statusType) {
                'paid'    => 'Paid',
                'overdue' => 'Overdue',
                default   => 'Pending',
            };

            $amountUsd = $this->normalizeUsd((float) $payment->amount, (string) ($payment->currency ?? 'USD'));
            $createdAt = $payment->created_at ?? now();
            $dueDate = $createdAt->copy()->addDays(7);
            $invNo = sprintf('INV-%s-%04d', $createdAt->format('Y'), $payment->id);
            $courseTitle = $payment->course?->title_kh ?: ($payment->course?->title ?? 'Course Tuition');

            $allInvoices[] = [
                'id'                 => $payment->id,
                'invoice_number'     => $invNo,
                'course_id'          => $payment->course_id,
                'course_name'        => $courseTitle,
                'invoice_date'       => $createdAt->format('M d, Y'),
                'due_date'           => $dueDate->format('M d, Y'),
                'due_date_raw'       => $dueDate,
                'created_at_raw'     => $createdAt,
                'amount'             => '$' . number_format($amountUsd, 2),
                'amount_raw'         => round($amountUsd, 2),
                'status'             => $statusLabel,
                'status_type'        => $statusType,
                'payment_method'     => $payment->aba_transaction_id ? 'ABA KHQR' : ($payment->payment_slip ? 'Bank Slip' : '—'),
                'payment_method_sub' => $payment->aba_transaction_id ? ('#' . substr($payment->aba_transaction_id, -6)) : '',
                'transaction_id'     => $payment->aba_transaction_id,
                'receipt_number'     => $statusType === 'paid' ? sprintf('REC-%s-%04d', $createdAt->format('Y'), $payment->id) : null,
            ];
        }

        foreach ($unpaidEnrollments as $enr) {
            if (!$enr->course) {
                continue;
            }
            $amountUsd = (float) ($enr->course->price ?? 0);
            $createdAt = $enr->enrolled_at ?? $enr->created_at ?? now();
            $dueDate = $createdAt->copy()->addDays(7);
            $isOverdue = $dueDate->isPast();
            $invNo = sprintf('INV-ENR-%04d', $enr->id);
            $courseTitle = $enr->course->title_kh ?: $enr->course->title;

            $allInvoices[] = [
                'id'                 => 100000 + $enr->id,
                'invoice_number'     => $invNo,
                'course_id'          => $enr->course_id,
                'course_name'        => $courseTitle,
                'invoice_date'       => $createdAt->format('M d, Y'),
                'due_date'           => $dueDate->format('M d, Y'),
                'due_date_raw'       => $dueDate,
                'created_at_raw'     => $createdAt,
                'amount'             => '$' . number_format($amountUsd, 2),
                'amount_raw'         => round($amountUsd, 2),
                'status'             => $isOverdue ? 'Overdue' : 'Pending',
                'status_type'        => $isOverdue ? 'overdue' : 'pending',
                'payment_method'     => '—',
                'payment_method_sub' => '',
                'transaction_id'     => null,
                'receipt_number'     => null,
            ];
        }

        $paidAmount = 0.0;
        $pendingAmount = 0.0;
        $overdueAmount = 0.0;
        $paidCount = 0;
        $pendingCount = 0;

        foreach ($allInvoices as $inv) {
            if ($inv['status_type'] === 'paid') {
                $paidAmount += $inv['amount_raw'];
                $paidCount++;
            } elseif ($inv['status_type'] === 'overdue') {
                $overdueAmount += $inv['amount_raw'];
                $pendingCount++;
            } else {
                $pendingAmount += $inv['amount_raw'];
                $pendingCount++;
            }
        }

        $totalDue = $pendingAmount + $overdueAmount;
        $grandTotal = $paidAmount + $totalDue;
        $totalInvoicesCount = count($allInvoices);

        $summary = [
            'total_due'        => '$' . number_format($totalDue, 2),
            'total_due_raw'    => round($totalDue, 2),
            'total_due_note'   => 'Outstanding balance',
            'paid_amount'      => '$' . number_format($paidAmount, 2),
            'paid_amount_raw'  => round($paidAmount, 2),
            'paid_amount_note' => 'Total paid',
            'total_invoices'   => $totalInvoicesCount,
            'total_inv_note'   => 'All invoices',
            'paid_invoices'    => $paidCount,
            'paid_inv_note'    => 'Paid successfully',
            'pending_invoices' => $pendingCount,
            'pending_inv_note' => 'Awaiting payment',
        ];

        $paidPct = $grandTotal > 0 ? (int) round(($paidAmount / $grandTotal) * 100) : 0;
        $pendingPct = $grandTotal > 0 ? (int) round(($pendingAmount / $grandTotal) * 100) : 0;
        $overduePct = $grandTotal > 0 ? max(0, 100 - $paidPct - $pendingPct) : 0;

        $paymentSummary = [
            'total_amount' => '$' . number_format($grandTotal, 2),
            'total_note'   => 'All time',
            'items'        => [
                ['label' => 'Paid',    'amount' => '$' . number_format($paidAmount, 2),    'percentage' => $paidPct,    'color' => '#10B981'],
                ['label' => 'Pending', 'amount' => '$' . number_format($pendingAmount, 2), 'percentage' => $pendingPct, 'color' => '#F59E0B'],
                ['label' => 'Overdue', 'amount' => '$' . number_format($overdueAmount, 2), 'percentage' => $overduePct, 'color' => '#EF4444'],
            ],
        ];

        $upcomingPayments = collect($allInvoices)
            ->whereIn('status_type', ['pending', 'overdue'])
            ->take(5)
            ->map(fn ($inv) => [
                'id'          => $inv['id'],
                'course'      => $inv['course_name'],
                'due_date'    => 'Due on ' . $inv['due_date'],
                'amount'      => $inv['amount'],
                'status'      => $inv['status'],
                'status_type' => $inv['status_type'],
                'color'       => $inv['status_type'] === 'overdue' ? 'rose' : 'amber',
            ])
            ->values()
            ->all();

        $recentTransactions = collect($allInvoices)
            ->take(5)
            ->map(fn ($inv) => [
                'id'             => $inv['id'],
                'title'          => $inv['status_type'] === 'paid' ? 'Payment Verified' : 'Payment Pending',
                'date'           => $inv['created_at_raw']->format('M d, Y h:i A'),
                'amount'         => ($inv['status_type'] === 'paid' ? '+' : '') . $inv['amount'],
                'amount_type'    => $inv['status_type'] === 'paid' ? 'positive' : 'negative',
                'invoice_number' => $inv['invoice_number'],
                'method'         => $inv['payment_method'] !== '—' ? $inv['payment_method'] : 'Pending',
            ])
            ->values()
            ->all();

        // Filter catalog
        $filtered = $allInvoices;
        if ($status !== 'all') {
            $filtered = array_values(array_filter($filtered, fn ($inv) => strtolower($inv['status_type']) === strtolower($status)));
        }
        if ($course !== 'all') {
            $filtered = array_values(array_filter($filtered, fn ($inv) => strtolower($inv['course_name']) === strtolower($course)));
        }
        if ($search !== '') {
            $s = mb_strtolower($search);
            $filtered = array_values(array_filter(
                $filtered,
                fn ($inv) => str_contains(mb_strtolower($inv['invoice_number']), $s)
                    || str_contains(mb_strtolower($inv['course_name']), $s)
            ));
        }

        // Strip internal Carbon fields before JSON serialization
        $cleanInvoices = array_map(function ($inv) {
            unset($inv['due_date_raw'], $inv['created_at_raw']);
            return $inv;
        }, $filtered);

        $totalFiltered = count($cleanInvoices);
        $offset = max(0, ($page - 1) * $perPage);
        $pagedInvoices = array_slice($cleanInvoices, $offset, $perPage);

        return [
            'summary'             => $summary,
            'payment_summary'     => $paymentSummary,
            'upcoming_payments'   => $upcomingPayments,
            'recent_transactions' => $recentTransactions,
            'invoices'            => $pagedInvoices,
            'total_count'         => $totalFiltered,
            'current_page'        => $page,
            'per_page'            => $perPage,
        ];
    }

    /**
     * Get ABA KHQR Payment Session data for a real pending invoice or enrollment.
     */
    public function getAbaPaymentSessionData(?string $invoiceNumber = null, ?User $user = null): array
    {
        $merchantName = Setting::where('key', 'site_name')->value('value') ?: 'Saint Paul Institute';
        $paymentRow = null;
        $enrollmentRow = null;

        if ($user) {
            if ($invoiceNumber && preg_match('/^INV-\d{4}-(\d+)$/', $invoiceNumber, $m)) {
                $paymentRow = Payment::with('course')
                    ->where('student_id', $user->id)
                    ->where('id', (int) $m[1])
                    ->first();
            } elseif ($invoiceNumber && preg_match('/^INV-ENR-(\d+)$/', $invoiceNumber, $m)) {
                $enrollmentRow = Enrollment::with('course')
                    ->where('student_id', $user->id)
                    ->where('id', (int) $m[1])
                    ->first();
            }

            if (!$paymentRow && !$enrollmentRow) {
                $paymentRow = Payment::with('course')
                    ->where('student_id', $user->id)
                    ->whereIn('status', ['pending', 'verifying'])
                    ->latest()
                    ->first();
            }

            if (!$paymentRow && !$enrollmentRow) {
                $enrollmentRow = Enrollment::with('course')
                    ->where('student_id', $user->id)
                    ->where('status', 'pending_payment')
                    ->latest()
                    ->first();
            }
        }

        if (!$paymentRow && !$enrollmentRow) {
            return [
                'session_id'         => null,
                'invoice_number'     => null,
                'course_name'        => null,
                'course_type'        => null,
                'student_name'       => $user?->name_kh ?: ($user?->name ?? ''),
                'student_id'         => $user?->student_code ?? '',
                'amount_khr'         => '0 KHR',
                'amount_khr_raw'     => 0,
                'amount_usd'         => '$0.00 USD',
                'amount_usd_raw'     => 0.0,
                'merchant_name'      => $merchantName,
                'merchant_id'        => '',
                'due_date'           => null,
                'due_date_iso'       => null,
                'payment_method'     => 'ABA KHQR',
                'qr_code_url'        => null,
                'expires_in_seconds' => 0,
                'status'             => 'NO_PENDING_INVOICE',
            ];
        }

        $course = $paymentRow?->course ?? $enrollmentRow?->course;
        $amountUsd = $paymentRow
            ? $this->normalizeUsd((float) $paymentRow->amount, (string) ($paymentRow->currency ?? 'USD'))
            : (float) ($course?->price ?? 0);
        $amountKhr = (int) round($amountUsd * self::USD_TO_KHR);
        $createdAt = $paymentRow?->created_at ?? $enrollmentRow?->enrolled_at ?? now();
        $dueDate = $createdAt->copy()->addDays(7);
        $invNo = $paymentRow
            ? sprintf('INV-%s-%04d', $createdAt->format('Y'), $paymentRow->id)
            : sprintf('INV-ENR-%04d', $enrollmentRow->id);

        return [
            'session_id'         => 'PAY-' . $invNo,
            'invoice_number'     => $invNo,
            'course_id'          => $course?->id,
            'course_name'        => $course?->title_kh ?: ($course?->title ?? 'Course Tuition'),
            'course_type'        => ($course?->learning_mode === 'self_paced') ? 'Self-Paced Course' : 'Instructor-Led Course',
            'student_name'       => $user?->name_kh ?: ($user?->name ?? ''),
            'student_id'         => $user?->student_code ?: ('ID-' . $user?->id),
            'amount_khr'         => number_format($amountKhr) . ' KHR',
            'amount_khr_raw'     => $amountKhr,
            'amount_usd'         => '$' . number_format($amountUsd, 2) . ' USD',
            'amount_usd_raw'     => round($amountUsd, 2),
            'merchant_name'      => $merchantName,
            'merchant_id'        => Setting::where('key', 'aba_merchant_id')->value('value') ?: '',
            'due_date'           => $dueDate->format('F d, Y'),
            'due_date_iso'       => $dueDate->format('Y-m-d'),
            'payment_method'     => 'ABA KHQR',
            'qr_code_url'        => Setting::where('key', 'aba_qr_image')->value('value') ?: null,
            'expires_in_seconds' => 600,
            'status'             => $paymentRow?->status === 'verifying' ? 'VERIFYING' : 'WAITING_FOR_PAYMENT',
        ];
    }

    /**
     * Get structured Student Transaction History data from real payments.
     */
    public function getTransactionHistoryData(
        ?User $user = null,
        string $status = 'all',
        string $method = 'all',
        string $sort = 'newest',
        string $search = '',
        int $page = 1,
        int $perPage = 10
    ): array {
        if (!$user) {
            return $this->emptyTransactions($page, $perPage);
        }

        $query = Payment::with('course')->where('student_id', $user->id);
        if ($sort === 'oldest') {
            $query->orderBy('created_at');
        } elseif ($sort === 'amount_high') {
            $query->orderByDesc('amount');
        } elseif ($sort === 'amount_low') {
            $query->orderBy('amount');
        } else {
            $query->orderByDesc('created_at');
        }

        $payments = $query->get();

        $allTx = [];
        $successfulCount = 0;
        $pendingCount = 0;
        $failedCount = 0;
        $totalPaidKhr = 0;

        foreach ($payments as $p) {
            $statusType = match ($p->status) {
                'paid', 'approved', 'completed' => 'successful',
                'rejected', 'failed'            => 'failed',
                default                         => 'pending',
            };
            $statusLabel = match ($statusType) {
                'successful' => 'Successful',
                'failed'     => 'Failed',
                default      => 'Pending',
            };

            $amountUsd = $this->normalizeUsd((float) $p->amount, (string) ($p->currency ?? 'USD'));
            $amountKhr = (int) round($amountUsd * self::USD_TO_KHR);

            if ($statusType === 'successful') {
                $successfulCount++;
                $totalPaidKhr += $amountKhr;
            } elseif ($statusType === 'failed') {
                $failedCount++;
            } else {
                $pendingCount++;
            }

            $createdAt = $p->created_at ?? now();
            $methodType = $p->aba_transaction_id ? 'aba' : 'bank';
            $methodLabel = $p->aba_transaction_id ? 'ABA KHQR' : 'Bank Slip';

            $allTx[] = [
                'id'             => $p->id,
                'transaction_id' => $p->aba_transaction_id ?: sprintf('TRX-%s-%05d', $createdAt->format('Y'), $p->id),
                'invoice_number' => sprintf('INV-%s-%04d', $createdAt->format('Y'), $p->id),
                'course_name'    => $p->course?->title_kh ?: ($p->course?->title ?? 'Course Tuition'),
                'payment_method' => $methodLabel,
                'method_type'    => $methodType,
                'amount_khr'     => number_format($amountKhr) . ' KHR',
                'amount_usd'     => '$' . number_format($amountUsd, 2),
                'amount_raw'     => $amountKhr,
                'date_formatted' => $createdAt->format('M d, Y'),
                'time_formatted' => $createdAt->format('h:i A'),
                'date_time_text' => $createdAt->format('M d, Y • h:i A'),
                'status'         => $statusLabel,
                'status_type'    => $statusType,
                'reference_code' => $p->aba_transaction_id ?: ('SLIP-' . $p->id),
                'payment_slip'   => $p->payment_slip ? asset('storage/' . ltrim($p->payment_slip, '/')) : null,
            ];
        }

        $totalCountAll = count($allTx);
        $progressPct = $totalCountAll > 0 ? (int) round(($successfulCount / $totalCountAll) * 100) : 0;

        $latest = $allTx[0] ?? null;
        $latestPayment = $latest ? [
            'course_name'    => $latest['course_name'],
            'amount'         => $latest['amount_khr'],
            'status'         => 'Payment ' . $latest['status'],
            'status_type'    => $latest['status_type'],
            'date_text'      => $latest['date_time_text'],
            'invoice_number' => $latest['invoice_number'],
            'transaction_id' => $latest['transaction_id'],
        ] : null;

        $filtered = $allTx;
        if ($status !== 'all') {
            $filtered = array_values(array_filter($filtered, fn ($tx) => strtolower($tx['status_type']) === strtolower($status)));
        }
        if ($method !== 'all') {
            $filtered = array_values(array_filter($filtered, fn ($tx) => strtolower($tx['method_type']) === strtolower($method)));
        }
        if ($search !== '') {
            $s = mb_strtolower($search);
            $filtered = array_values(array_filter(
                $filtered,
                fn ($tx) => str_contains(mb_strtolower($tx['transaction_id']), $s)
                    || str_contains(mb_strtolower($tx['invoice_number']), $s)
                    || str_contains(mb_strtolower($tx['course_name']), $s)
            ));
        }

        $totalFiltered = count($filtered);
        $offset = max(0, ($page - 1) * $perPage);
        $paged = array_slice($filtered, $offset, $perPage);

        return [
            'summary' => [
                'total_transactions'  => $totalCountAll,
                'total_tx_note'       => 'All payment records',
                'successful_payments' => $successfulCount,
                'successful_note'     => 'Completed transactions',
                'pending_payments'    => $pendingCount,
                'pending_note'        => 'Awaiting confirmation',
                'total_paid'          => number_format($totalPaidKhr) . ' KHR',
                'total_paid_note'     => 'Total successful payments',
            ],
            'payment_summary' => [
                'total_paid'       => number_format($totalPaidKhr) . ' KHR',
                'progress_percent' => $progressPct,
                'successful_count' => $successfulCount,
                'pending_count'    => $pendingCount,
                'failed_count'     => $failedCount,
            ],
            'latest_payment' => $latestPayment,
            'transactions'   => $paged,
            'total_count'    => $totalFiltered,
            'current_page'   => $page,
            'per_page'       => $perPage,
        ];
    }

    /**
     * Get structured Student Payment Settings data from real user preferences & payments.
     */
    public function getPaymentSettingsData(?User $user = null): array
    {
        if (!$user) {
            return [
                'saved_methods'    => [],
                'auto_pay'         => ['enabled' => false, 'currency' => 'KHR', 'threshold' => '0', 'reminder' => '1 day before'],
                'history_summary'  => [
                    'total_paid'          => '0 KHR',
                    'total_paid_period'   => 'All Time',
                    'last_payment'        => '0 KHR',
                    'last_payment_date'   => '—',
                    'successful_payments' => 0,
                    'successful_period'   => 'All Time',
                    'failed_payments'     => 0,
                    'failed_period'       => 'All Time',
                ],
                'billing_summary'  => [
                    'total_invoices'      => 0,
                    'paid_invoices'       => 0,
                    'pending_invoices'    => 0,
                    'outstanding_balance' => '$0.00',
                ],
                'default_currency' => 'KHR',
                'security'         => [
                    'two_factor_enabled' => false,
                    'encryption_active'  => true,
                    'fraud_protection'   => true,
                    'monitoring_active'  => true,
                ],
                'notifications'    => [
                    'payment_successful' => true,
                    'payment_pending'    => true,
                    'payment_failed'     => true,
                    'invoice_reminder'   => true,
                    'due_date_reminder'  => true,
                ],
            ];
        }

        $txData = $this->getTransactionHistoryData($user);
        $invData = $this->getMyPaymentsData($user);
        $prefs = $user->preferences()->pluck('value', 'key');

        $latestPaid = Payment::where('student_id', $user->id)
            ->whereIn('status', ['paid', 'approved', 'completed'])
            ->latest()
            ->first();

        $lastPaymentAmt = '0 KHR';
        $lastPaymentDate = '—';
        if ($latestPaid) {
            $usd = $this->normalizeUsd((float) $latestPaid->amount, (string) ($latestPaid->currency ?? 'USD'));
            $lastPaymentAmt = number_format((int) round($usd * self::USD_TO_KHR)) . ' KHR';
            $lastPaymentDate = ($latestPaid->verified_at ?? $latestPaid->created_at)?->format('M d, Y') ?? '—';
        }

        return [
            'saved_methods'    => [],
            'auto_pay'         => [
                'enabled'   => ($prefs['payment_autopay'] ?? '0') === '1',
                'currency'  => $prefs['payment_currency'] ?? 'KHR',
                'threshold' => $prefs['payment_threshold'] ?? '0',
                'reminder'  => $prefs['payment_reminder'] ?? '1 day before',
            ],
            'history_summary'  => [
                'total_paid'          => $txData['payment_summary']['total_paid'],
                'total_paid_period'   => 'All Time',
                'last_payment'        => $lastPaymentAmt,
                'last_payment_date'   => $lastPaymentDate,
                'successful_payments' => $txData['payment_summary']['successful_count'],
                'successful_period'   => 'All Time',
                'failed_payments'     => $txData['payment_summary']['failed_count'],
                'failed_period'       => 'All Time',
            ],
            'billing_summary'  => [
                'total_invoices'      => $invData['summary']['total_invoices'],
                'paid_invoices'       => $invData['summary']['paid_invoices'],
                'pending_invoices'    => $invData['summary']['pending_invoices'],
                'outstanding_balance' => $invData['summary']['total_due'],
            ],
            'default_currency' => $prefs['payment_currency'] ?? 'KHR',
            'security'         => [
                'two_factor_enabled' => (bool) ($user->two_factor_enabled ?? false),
                'encryption_active'  => true,
                'fraud_protection'   => true,
                'monitoring_active'  => true,
            ],
            'notifications'    => [
                'payment_successful' => ($prefs['notify_payment_successful'] ?? '1') === '1',
                'payment_pending'    => ($prefs['notify_payment_pending'] ?? '1') === '1',
                'payment_failed'     => ($prefs['notify_payment_failed'] ?? '1') === '1',
                'invoice_reminder'   => ($prefs['notify_invoice_reminder'] ?? '1') === '1',
                'due_date_reminder'  => ($prefs['notify_due_date_reminder'] ?? '1') === '1',
            ],
        ];
    }

    private function normalizeUsd(float $amount, string $currency): float
    {
        if (strtoupper($currency) === 'KHR') {
            return $amount / self::USD_TO_KHR;
        }
        return $amount;
    }

    private function emptyMyPayments(int $page, int $perPage): array
    {
        return [
            'summary' => [
                'total_due'        => '$0.00',
                'total_due_raw'    => 0.0,
                'total_due_note'   => 'Outstanding balance',
                'paid_amount'      => '$0.00',
                'paid_amount_raw'  => 0.0,
                'paid_amount_note' => 'Total paid',
                'total_invoices'   => 0,
                'total_inv_note'   => 'All invoices',
                'paid_invoices'    => 0,
                'paid_inv_note'    => 'Paid successfully',
                'pending_invoices' => 0,
                'pending_inv_note' => 'Awaiting payment',
            ],
            'payment_summary' => [
                'total_amount' => '$0.00',
                'total_note'   => 'All time',
                'items'        => [
                    ['label' => 'Paid',    'amount' => '$0.00', 'percentage' => 0, 'color' => '#10B981'],
                    ['label' => 'Pending', 'amount' => '$0.00', 'percentage' => 0, 'color' => '#F59E0B'],
                    ['label' => 'Overdue', 'amount' => '$0.00', 'percentage' => 0, 'color' => '#EF4444'],
                ],
            ],
            'upcoming_payments'   => [],
            'recent_transactions' => [],
            'invoices'            => [],
            'total_count'         => 0,
            'current_page'        => $page,
            'per_page'            => $perPage,
        ];
    }

    private function emptyTransactions(int $page, int $perPage): array
    {
        return [
            'summary' => [
                'total_transactions'  => 0,
                'total_tx_note'       => 'All payment records',
                'successful_payments' => 0,
                'successful_note'     => 'Completed transactions',
                'pending_payments'    => 0,
                'pending_note'        => 'Awaiting confirmation',
                'total_paid'          => '0 KHR',
                'total_paid_note'     => 'Total successful payments',
            ],
            'payment_summary' => [
                'total_paid'       => '0 KHR',
                'progress_percent' => 0,
                'successful_count' => 0,
                'pending_count'    => 0,
                'failed_count'     => 0,
            ],
            'latest_payment' => null,
            'transactions'   => [],
            'total_count'    => 0,
            'current_page'   => $page,
            'per_page'       => $perPage,
        ];
    }
}

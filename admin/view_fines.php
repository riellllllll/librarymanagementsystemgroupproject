<?php
// view_fines.php - Library Fine Management System

// Mock database for demonstration - replace with actual database queries
$fines_data = [
    'STU1001' => [
        'student_name' => 'Emma Watson',
        'student_class' => 'Grade 11-A',
        'email' => 'emma.watson@example.com',
        'fines' => [
            ['book_id' => 'BK-4521', 'book_title' => 'The Great Gatsby', 'issue_date' => '2026-04-10', 'due_date' => '2026-04-25', 'return_date' => null, 'days_overdue' => 30, 'fine_amount' => 150.00, 'status' => 'pending'],
            ['book_id' => 'BK-9823', 'book_title' => 'Sapiens', 'issue_date' => '2026-05-01', 'due_date' => '2026-05-16', 'return_date' => null, 'days_overdue' => 9, 'fine_amount' => 45.00, 'status' => 'pending'],
            ['book_id' => 'BK-3371', 'book_title' => 'Clean Code', 'issue_date' => '2026-03-05', 'due_date' => '2026-03-20', 'return_date' => '2026-04-01', 'days_overdue' => 12, 'fine_amount' => 60.00, 'status' => 'paid']
        ]
    ],
    'STU1002' => [
        'student_name' => 'James Carter',
        'student_class' => 'Grade 10-B',
        'email' => 'james.carter@example.com',
        'fines' => [
            ['book_id' => 'BK-6632', 'book_title' => 'Deep Work', 'issue_date' => '2026-04-18', 'due_date' => '2026-05-03', 'return_date' => null, 'days_overdue' => 22, 'fine_amount' => 110.00, 'status' => 'pending'],
            ['book_id' => 'BK-2290', 'book_title' => 'Atomic Habits', 'issue_date' => '2026-05-10', 'due_date' => '2026-05-25', 'return_date' => null, 'days_overdue' => 0, 'fine_amount' => 0.00, 'status' => 'pending']
        ]
    ],
    'STU1003' => [
        'student_name' => 'Lina Zhang',
        'student_class' => 'Grade 12-C',
        'email' => 'lina.zhang@example.com',
        'fines' => [
            ['book_id' => 'BK-1198', 'book_title' => 'Dune', 'issue_date' => '2026-03-01', 'due_date' => '2026-03-16', 'return_date' => null, 'days_overdue' => 70, 'fine_amount' => 350.00, 'status' => 'pending']
        ]
    ],
    'STU1004' => [
        'student_name' => 'Oliver Chen',
        'student_class' => 'Grade 9-D',
        'email' => 'oliver.chen@example.com',
        'fines' => [
            ['book_id' => 'BK-7643', 'book_title' => 'The Hobbit', 'issue_date' => '2026-04-01', 'due_date' => '2026-04-16', 'return_date' => '2026-04-22', 'days_overdue' => 6, 'fine_amount' => 30.00, 'status' => 'paid'],
            ['book_id' => 'BK-5520', 'book_title' => 'To Kill a Mockingbird', 'issue_date' => '2026-05-12', 'due_date' => '2026-05-27', 'return_date' => null, 'days_overdue' => 0, 'fine_amount' => 0.00, 'status' => 'pending']
        ]
    ]
];

// Handle fine payment
$payment_message = '';
$payment_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pay_fine'])) {
        $student_id = $_POST['student_id'];
        $book_id = $_POST['book_id'];
        $amount = $_POST['amount'];
        
        $payment_message = "Payment of ₹$amount received for Book ID: $book_id";
        $payment_type = 'success';
        
        if (isset($fines_data[$student_id])) {
            foreach ($fines_data[$student_id]['fines'] as &$fine) {
                if ($fine['book_id'] === $book_id) {
                    $fine['status'] = 'paid';
                    break;
                }
            }
        }
    }
    
    if (isset($_POST['pay_all'])) {
        $student_id = $_POST['student_id'];
        $total_amount = $_POST['total_amount'];
        $payment_message = "Payment of ₹$total_amount received for all pending fines";
        $payment_type = 'success';
        
        if (isset($fines_data[$student_id])) {
            foreach ($fines_data[$student_id]['fines'] as &$fine) {
                if ($fine['status'] === 'pending' && $fine['fine_amount'] > 0) {
                    $fine['status'] = 'paid';
                }
            }
        }
    }
}

// Get student ID from query parameter
$selected_student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$student_data = isset($fines_data[$selected_student_id]) ? $fines_data[$selected_student_id] : null;

// Calculate statistics
$total_fines = 0;
$pending_fines = 0;
$paid_fines = 0;
$overdue_books = 0;

if ($student_data) {
    foreach ($student_data['fines'] as $fine) {
        if ($fine['status'] === 'pending') {
            $pending_fines += $fine['fine_amount'];
            if ($fine['days_overdue'] > 0) $overdue_books++;
        } else {
            $paid_fines += $fine['fine_amount'];
        }
        $total_fines += $fine['fine_amount'];
    }
}

// Get filter from URL
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Fines - Library Management System</title>
    <link rel="stylesheet" href="view_fines.css">
</head>
<body>
<div class="fine-container">
    <div class="fine-main-card">
        <div class="fine-header">
            <h1>📚 View Fines</h1>
            <p>View and manage outstanding fines per student</p>
        </div>

        <div class="fine-tabs">
            <div class="fine-tab">BORROWING</div>
            <div class="fine-tab">Student Requests</div>
            <div class="fine-tab">Borrowed Books</div>
            <div class="fine-tab">Issue Book</div>
            <div class="fine-tab">Return Book</div>
            <div class="fine-tab fine-active">View Fines</div>
        </div>

        <div class="fine-stats-grid">
            <div class="fine-stat-card">
                <div class="fine-stat-label">💰 TOTAL FINES</div>
                <div class="fine-stat-number">₹<?php echo number_format($total_fines, 2); ?></div>
                <div class="fine-stat-sub">Accrued overall</div>
            </div>
            <div class="fine-stat-card">
                <div class="fine-stat-label">⏳ PENDING FINES</div>
                <div class="fine-stat-number">₹<?php echo number_format($pending_fines, 2); ?></div>
                <div class="fine-stat-sub">Unpaid</div>
            </div>
            <div class="fine-stat-card">
                <div class="fine-stat-label">✅ PAID FINES</div>
                <div class="fine-stat-number">₹<?php echo number_format($paid_fines, 2); ?></div>
                <div class="fine-stat-sub">Total cleared</div>
            </div>
            <div class="fine-stat-card">
                <div class="fine-stat-label">📖 OVERDUE BOOKS</div>
                <div class="fine-stat-number"><?php echo $overdue_books; ?></div>
                <div class="fine-stat-sub">Contributing to fines</div>
            </div>
        </div>

        <div class="fine-search-section">
            <form method="GET" action="" class="fine-search-form">
                <div class="fine-input-group">
                    <label>👩‍🎓 STUDENT ID *</label>
                    <input type="text" name="student_id" placeholder="Enter Student ID" value="<?php echo htmlspecialchars($selected_student_id); ?>" required>
                </div>
                <div class="fine-input-group">
                    <label>📋 FILTER BY STATUS</label>
                    <select name="status_filter">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Fines</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending Only</option>
                        <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid Only</option>
                    </select>
                </div>
                <button type="submit" class="fine-btn fine-btn-primary">🔍 View Fines</button>
                <a href="view_fines.php" class="fine-btn fine-btn-secondary">Clear</a>
            </form>
        </div>

        <?php if ($payment_message): ?>
            <div class="fine-alert fine-alert-success">
                ✓ <?php echo htmlspecialchars($payment_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($selected_student_id && $student_data): ?>
            <div class="fine-student-info">
                <div class="fine-student-details">
                    <div class="fine-student-detail-item">
                        <span class="fine-student-detail-label">🎓 Student:</span>
                        <span class="fine-student-detail-value"><?php echo htmlspecialchars($student_data['student_name']); ?></span>
                    </div>
                    <div class="fine-student-detail-item">
                        <span class="fine-student-detail-label">🆔 ID:</span>
                        <span class="fine-student-detail-value"><?php echo htmlspecialchars($selected_student_id); ?></span>
                    </div>
                    <div class="fine-student-detail-item">
                        <span class="fine-student-detail-label">📚 Class:</span>
                        <span class="fine-student-detail-value"><?php echo htmlspecialchars($student_data['student_class']); ?></span>
                    </div>
                    <div class="fine-student-detail-item">
                        <span class="fine-student-detail-label">✉️ Email:</span>
                        <span class="fine-student-detail-value"><?php echo htmlspecialchars($student_data['email']); ?></span>
                    </div>
                </div>
            </div>

            <div class="fine-section-title">
                <span>📋 Pending & Outstanding Fines</span>
            </div>

            <div class="fine-table-wrapper">
                <table class="fine-fines-table">
                    <thead>
                        <tr>
                            <th>Book ID</th>
                            <th>Book Title</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Days Overdue</th>
                            <th>Fine Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $display_count = 0;
                        foreach ($student_data['fines'] as $fine):
                            if ($status_filter === 'pending' && $fine['status'] !== 'pending') continue;
                            if ($status_filter === 'paid' && $fine['status'] !== 'paid') continue;
                            $display_count++;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fine['book_id']); ?></td>
                            <td><?php echo htmlspecialchars($fine['book_title']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($fine['issue_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($fine['due_date'])); ?></td>
                            <td><?php echo $fine['return_date'] ? date('d/m/Y', strtotime($fine['return_date'])) : '—'; ?></td>
                            <td><?php echo $fine['days_overdue']; ?></td>
                            <td class="fine-fine-amount">₹<?php echo number_format($fine['fine_amount'], 2); ?></td>
                            <td>
                                <span class="fine-status-badge <?php echo $fine['status'] === 'pending' ? 'fine-status-pending' : 'fine-status-paid'; ?>">
                                    <?php echo ucfirst($fine['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($fine['status'] === 'pending' && $fine['fine_amount'] > 0): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($selected_student_id); ?>">
                                        <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($fine['book_id']); ?>">
                                        <input type="hidden" name="amount" value="<?php echo $fine['fine_amount']; ?>">
                                        <button type="submit" name="pay_fine" class="fine-btn fine-btn-success fine-btn-sm">
                                            Pay ₹<?php echo number_format($fine['fine_amount'], 2); ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #6c757d; font-size: 12px;">✓ Cleared</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if ($display_count === 0): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #6c757d;">
                                No fines found with the selected filter
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pending_fines > 0): ?>
            <div class="fine-total-summary">
                <span class="fine-total-label">🔔 Total Pending Fine (Unpaid)</span>
                <span class="fine-total-amount">₹<?php echo number_format($pending_fines, 2); ?></span>
                <form method="POST">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($selected_student_id); ?>">
                    <input type="hidden" name="total_amount" value="<?php echo $pending_fines; ?>">
                    <button type="submit" name="pay_all" class="fine-btn fine-btn-danger">
                        💳 Pay All Pending Fines (₹<?php echo number_format($pending_fines, 2); ?>)
                    </button>
                </form>
            </div>
            <?php endif; ?>

        <?php elseif ($selected_student_id && !$student_data): ?>
            <div class="fine-no-data">
                <p>❌ No student found with ID: <?php echo htmlspecialchars($selected_student_id); ?></p>
                <p style="margin-top: 10px; font-size: 13px;">Please check the Student ID and try again.</p>
                <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Try: STU1001, STU1002, STU1003, or STU1004</p>
            </div>
        <?php else: ?>
            <div class="fine-no-data">
                <p>🔍 Enter a Student ID above to view outstanding fines</p>
                <p style="margin-top: 10px; font-size: 13px; color: #6c757d;">Example: STU1001, STU1002, STU1003, STU1004</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
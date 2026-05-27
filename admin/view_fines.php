<?php
// view_fines.php - Library Fine Management System
session_start();
require 'library_data.php';

// Mock database for demonstration
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pay_fine'])) {
        $student_id = $_POST['student_id'];
        $book_id = $_POST['book_id'];
        $amount = $_POST['amount'];
        $payment_message = "Payment of PHP $amount received for Book ID: $book_id";
    }
    if (isset($_POST['pay_all'])) {
        $student_id = $_POST['student_id'];
        $total_amount = $_POST['total_amount'];
        $payment_message = "Payment of PHP $total_amount received for all pending fines";
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

$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Fines - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/student.css">
    <link rel="stylesheet" href="../assets/adminStyle.css">
    <link rel="stylesheet" href="../assets/admin_fines.css">
</head>

<body>

<style>
    /* FORCE GOLD BUTTON - OVERRIDE EVERYTHING */
    .btn-fines-primary {
        background: #c89b3c !important;
        color: #1a1a2e !important;
        border: none !important;
        border-radius: 7px !important;
        padding: 7px 16px !important;
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        cursor: pointer !important;
        text-transform: uppercase !important;
    }
    .btn-fines-primary:hover {
        background: #b88a2a !important;
        transform: translateY(-1px) !important;
    }
    .btn-fines-secondary {
        background: #e9ecef !important;
        color: #1a1a2e !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 7px !important;
        padding: 7px 16px !important;
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        cursor: pointer !important;
        text-decoration: none !important;
        text-transform: uppercase !important;
    }
    .btn-fines-secondary:hover {
        background: #dde0e3 !important;
        transform: translateY(-1px) !important;
    }
    .btn-fines-success {
        background: #28a745 !important;
        color: white !important;
        border: none !important;
        border-radius: 7px !important;
        padding: 5px 12px !important;
        font-size: 0.7rem !important;
        font-weight: 700 !important;
        cursor: pointer !important;
    }
    .btn-fines-success:hover {
        background: #218838 !important;
    }
    .btn-fines-danger {
        background: #dc3545 !important;
        color: white !important;
        border: none !important;
        border-radius: 7px !important;
        padding: 8px 20px !important;
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        cursor: pointer !important;
    }
    .btn-fines-danger:hover {
        background: #c82333 !important;
    }
</style>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper">

    <header class="topbar">
        <span class="topbar-title">View Fines</span>
        <div class="topbar-spacer"></div>
        <a href="student_req.php" class="topbar-icon-btn" title="Student Requests">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
        </a>
        <a href="admin_profile.php" class="topbar-icon-btn" title="Admin Profile">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </a>
    </header>

    <main class="page-content">

        <div class="page-header">
            <h1>View Fines</h1>
            <div class="gold-rule">
                <span></span>
                <i>*</i>
                <span></span>
            </div>
        </div>

        <div class="fines-container">

            <?php if ($payment_message): ?>
                <div class="alert-fines">✓ <?php echo htmlspecialchars($payment_message); ?></div>
            <?php endif; ?>

            <div class="stats-fines-grid">
                <div class="stat-fines-card">
                    <div class="stat-fines-label">TOTAL FINES</div>
                    <div class="stat-fines-number">PHP <?php echo number_format($total_fines, 2); ?></div>
                    <div class="stat-fines-sub">Accrued overall</div>
                </div>
                <div class="stat-fines-card">
                    <div class="stat-fines-label">PENDING FINES</div>
                    <div class="stat-fines-number">PHP <?php echo number_format($pending_fines, 2); ?></div>
                    <div class="stat-fines-sub">Unpaid</div>
                </div>
                <div class="stat-fines-card">
                    <div class="stat-fines-label">PAID FINES</div>
                    <div class="stat-fines-number">PHP <?php echo number_format($paid_fines, 2); ?></div>
                    <div class="stat-fines-sub">Total cleared</div>
                </div>
                <div class="stat-fines-card">
                    <div class="stat-fines-label">OVERDUE BOOKS</div>
                    <div class="stat-fines-number"><?php echo $overdue_books; ?></div>
                    <div class="stat-fines-sub">Contributing to fines</div>
                </div>
            </div>

            <div class="search-fines-section">
                <form method="GET" action="" class="search-fines-form">
                    <div class="input-fines-group">
                        <label>STUDENT ID *</label>
                        <input type="text" name="student_id" placeholder="Enter Student ID" value="<?php echo htmlspecialchars($selected_student_id); ?>" required>
                    </div>
                    <div class="input-fines-group">
                        <label>FILTER BY STATUS</label>
                        <select name="status_filter">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Fines</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending Only</option>
                            <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid Only</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-fines btn-fines-primary">VIEW FINES</button>
                    <a href="view_fines.php" class="btn-fines btn-fines-secondary">CLEAR</a>
                </form>
            </div>

            <?php if ($selected_student_id && $student_data): ?>
                <div class="student-fines-info">
                    <div class="student-fines-details">
                        <span><strong>Student:</strong> <?php echo htmlspecialchars($student_data['student_name']); ?></span>
                        <span><strong>ID:</strong> <?php echo htmlspecialchars($selected_student_id); ?></span>
                        <span><strong>Class:</strong> <?php echo htmlspecialchars($student_data['student_class']); ?></span>
                        <span><strong>Email:</strong> <?php echo htmlspecialchars($student_data['email']); ?></span>
                    </div>
                </div>

                <div class="section-fines-title">
                    <span>Pending & Outstanding Fines</span>
                </div>

                <div class="table-fines-wrapper">
                    <table class="table-fines">
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
                            <?php foreach ($student_data['fines'] as $fine):
                                if ($status_filter === 'pending' && $fine['status'] !== 'pending') continue;
                                if ($status_filter === 'paid' && $fine['status'] !== 'paid') continue;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fine['book_id']); ?></td>
                                <td><?php echo htmlspecialchars($fine['book_title']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($fine['issue_date'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($fine['due_date'])); ?></td>
                                <td><?php echo $fine['return_date'] ? date('d/m/Y', strtotime($fine['return_date'])) : '—'; ?></td>
                                <td><?php echo $fine['days_overdue']; ?></td>
                                <td class="fine-amount">PHP <?php echo number_format($fine['fine_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $fine['status'] === 'pending' ? 'status-pending' : 'status-paid'; ?>">
                                        <?php echo ucfirst($fine['status']); ?>
                                    </span>
                                </td
                                <td>
                                    <?php if ($fine['status'] === 'pending' && $fine['fine_amount'] > 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($selected_student_id); ?>">
                                            <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($fine['book_id']); ?>">
                                            <input type="hidden" name="amount" value="<?php echo $fine['fine_amount']; ?>">
                                            <button type="submit" name="pay_fine" class="btn-fines btn-fines-success">
                                                Pay PHP <?php echo number_format($fine['fine_amount'], 2); ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #155724;">Cleared</span>
                                    <?php endif; ?>
                                 </td
                             </tr
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pending_fines > 0): ?>
                <div class="total-summary-fines">
                    <span><strong>Total Pending Fine (Unpaid)</strong></span>
                    <span style="font-size: 24px; font-weight: 700; color: #dc3545;">PHP <?php echo number_format($pending_fines, 2); ?></span>
                    <form method="POST">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($selected_student_id); ?>">
                        <input type="hidden" name="total_amount" value="<?php echo $pending_fines; ?>">
                        <button type="submit" name="pay_all" class="btn-fines btn-fines-danger">
                            Pay All Pending Fines (PHP <?php echo number_format($pending_fines, 2); ?>)
                        </button>
                    </form>
                </div>
                <?php endif; ?>

            <?php elseif ($selected_student_id && !$student_data): ?>
                <div class="no-data-fines">
                    No student found with ID: <?php echo htmlspecialchars($selected_student_id); ?>
                    <p style="margin-top: 10px;">Try: STU1001, STU1002, STU1003, or STU1004</p>
                </div>
            <?php else: ?>
                <div class="no-data-fines">
                    Enter a Student ID above to view outstanding fines
                    <p style="margin-top: 10px;">Example: STU1001, STU1002, STU1003, STU1004</p>
                </div>
            <?php endif; ?>

        </div>

    </main>

</div>

</body>
</html>
<?php
// view_fines.php - Library Fine Management System
session_start();
require 'library_data.php';

if (!isset($_SESSION['archived_books'])) {
  $_SESSION['archived_books'] = [];
}

$pending_count = count(array_filter($_SESSION['borrow_requests'], function ($req) {
  return $req['status'] === 'pending';
}));

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

// Status filter options
$status_options = [
    'all' => 'All Fines',
    'pending' => 'Pending',
    'paid' => 'Paid'
];

$selected_status = $_GET['status'] ?? 'all';
$search_student = isset($_GET['student_id']) ? $_GET['student_id'] : '';

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
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/adminStyle.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Custom fines page styles - COMPACT VERSION */
        .stats-fines-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-fines-card {
            background: #f8f9fa;
            padding: 12px 10px;
            border-radius: 10px;
            text-align: center;
            border-top: 3px solid #c89b3c;
        }
        
        .stat-fines-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 6px;
            font-weight: 600;
        }
        
        .stat-fines-number {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .stat-fines-sub {
            font-size: 9px;
            color: #6c757d;
            margin-top: 4px;
        }
        
        .student-fines-info {
            background: #1a110b;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: white;
        }
        
        .student-fines-details {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 12px;
        }
        
        .section-fines-title {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .section-fines-title span {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a2e;
        }
        
        /* COMPACT TABLE */
        .table-fines-wrapper {
            width: 100%;
            margin-bottom: 20px;
            overflow-x: auto;
        }
        
        .table-fines {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .table-fines th {
            text-align: left;
            padding: 8px 6px;
            background: #f8f9fa;
            font-size: 10px;
            font-weight: 600;
            color: #495057;
            border-bottom: 1px solid #eef2f6;
        }
        
        .table-fines td {
            padding: 8px 6px;
            border-bottom: 1px solid #eef2f6;
            color: #1a1a2e;
            font-size: 11px;
        }
        
        .table-fines tr:hover {
            background: #faf8f5;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #f8d7da;
            color: #dc3545;
        }
        
        .status-paid {
            background: #d4edda;
            color: #28a745;
        }
        
        .fine-amount {
            font-weight: 600;
            color: #dc3545;
            font-size: 11px;
        }
        
        /* Buttons */
        .btn-fines {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }
        
        .btn-fines-success {
            background: #28a745;
            color: white;
        }
        
        .btn-fines-success:hover {
            background: #218838;
        }
        
        .total-summary-fines {
            background: #fff8f0;
            border: 1px solid #ffe4b5;
            border-radius: 10px;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 12px;
        }
        
        .no-data-fines {
            text-align: center;
            padding: 30px 20px;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .alert-fines {
            background: #d4edda;
            color: #155724;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 12px;
        }
        
        .fines-container {
            background: white;
            border-radius: 16px;
            padding: 18px;
        }
        
        @media (max-width: 1024px) {
            .stats-fines-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-fines-grid {
                grid-template-columns: 1fr;
            }
            .student-fines-details {
                flex-direction: column;
                gap: 6px;
            }
        }
    </style>
</head>

<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper">

    <header class="topbar">
        <span class="topbar-title">View Fines</span>
        <div class="topbar-spacer"></div>
        
        <form class="topbar-search" method="GET" action="view_fines.php">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" name="student_id" placeholder="Search by Student ID..." value="<?= htmlspecialchars($selected_student_id) ?>">
        </form>

        <a href="student_req.php" class="topbar-icon-btn" title="Student Borrow Requests">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <?php if ($pending_count > 0): ?>
                <span class="topbar-notif-dot"></span>
            <?php endif; ?>
        </a>

        <a href="admin_profile.php" class="topbar-icon-btn" title="Admin Profile">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </a>
    </header>

    <main class="page-content">

        <div class="page-header">
            <h1>View Fines</h1>
            <p>View and manage outstanding fines per student</p>
            <div class="gold-rule">
                <span></span>
                <i>*</i>
                <span></span>
            </div>
        </div>

        <div class="books-filter-section">
            <div class="category-pills">
                <?php foreach ($status_options as $value => $label): ?>
                    <a href="?status=<?= urlencode($value) ?>&student_id=<?= urlencode($selected_student_id) ?>" 
                       class="category-pill <?= $value === $status_filter ? 'active' : '' ?>">
                        <?= htmlspecialchars($label) ?>
                    </a>
                <?php endforeach; ?>
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
                                <th>Due Date</th>
                                <th>Days</th>
                                <th>Fine</th>
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
                                <td><?php echo date('d/m/Y', strtotime($fine['due_date'])); ?></td>
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
                                                Pay
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #28a745;">✓</span>
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
                    <span style="font-size: 18px; font-weight: 700; color: #dc3545;">PHP <?php echo number_format($pending_fines, 2); ?></span>
                    <form method="POST">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($selected_student_id); ?>">
                        <input type="hidden" name="total_amount" value="<?php echo $pending_fines; ?>">
                        <button type="submit" name="pay_all" class="btn-fines btn-fines-danger" style="background: #dc3545; color: white; padding: 4px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 10px;">
                            Pay All
                        </button>
                    </form>
                </div>
                <?php endif; ?>

            <?php elseif ($selected_student_id && !$student_data): ?>
                <div class="no-data-fines">
                    No student found with ID: <?php echo htmlspecialchars($selected_student_id); ?>
                    <p style="margin-top: 8px; font-size: 11px;">Try: STU1001, STU1002, STU1003, or STU1004</p>
                </div>
            <?php else: ?>
                <div class="no-data-fines">
                    Enter a Student ID above to view outstanding fines
                    <p style="margin-top: 8px; font-size: 11px;">Example: STU1001, STU1002, STU1003, STU1004</p>
                </div>
            <?php endif; ?>

        </div>

    </main>

</div>

</body>
</html>
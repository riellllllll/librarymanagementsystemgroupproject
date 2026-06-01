<?php
// view_fines.php - Library Fine Management System
session_start();
require_once __DIR__ . '/library_data.php';

// Session guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login/login.php');
    exit;
}

$pending_count = pending_request_count();

$db   = new Database();
$conn = $db->getConnection();

// Mock database for demonstration with simplified IDs
$fines_data = [
    '101' => [
        'student_name' => 'Emma Watson',
        'student_class' => 'Grade 11-A',
        'email' => 'emma.watson@example.com',
        'fines' => [
            ['book_id' => '01', 'book_title' => 'The Great Gatsby', 'issue_date' => '2026-04-10', 'due_date' => '2026-04-25', 'return_date' => null, 'days_overdue' => 32, 'fine_amount' => 160.00, 'status' => 'pending'],
            ['book_id' => '02', 'book_title' => 'Sapiens', 'issue_date' => '2026-05-01', 'due_date' => '2026-05-16', 'return_date' => null, 'days_overdue' => 11, 'fine_amount' => 55.00, 'status' => 'pending'],
            ['book_id' => '03', 'book_title' => 'Clean Code', 'issue_date' => '2026-03-05', 'due_date' => '2026-03-20', 'return_date' => '2026-04-01', 'days_overdue' => 12, 'fine_amount' => 60.00, 'status' => 'paid']
        ]
    ],
    '102' => [
        'student_name' => 'James Carter',
        'student_class' => 'Grade 10-B',
        'email' => 'james.carter@example.com',
        'fines' => [
            ['book_id' => '04', 'book_title' => 'Deep Work', 'issue_date' => '2026-04-18', 'due_date' => '2026-05-03', 'return_date' => null, 'days_overdue' => 24, 'fine_amount' => 120.00, 'status' => 'pending'],
            ['book_id' => '05', 'book_title' => 'Atomic Habits', 'issue_date' => '2026-05-10', 'due_date' => '2026-05-25', 'return_date' => null, 'days_overdue' => 2, 'fine_amount' => 10.00, 'status' => 'pending']
        ]
    ],
    '103' => [
        'student_name' => 'Lina Zhang',
        'student_class' => 'Grade 12-C',
        'email' => 'lina.zhang@example.com',
        'fines' => [
            ['book_id' => '06', 'book_title' => 'Dune', 'issue_date' => '2026-03-01', 'due_date' => '2026-03-16', 'return_date' => null, 'days_overdue' => 72, 'fine_amount' => 360.00, 'status' => 'pending']
        ]
    ],
    '104' => [
        'student_name' => 'Oliver Chen',
        'student_class' => 'Grade 9-D',
        'email' => 'oliver.chen@example.com',
        'fines' => [
            ['book_id' => '07', 'book_title' => 'The Hobbit', 'issue_date' => '2026-04-01', 'due_date' => '2026-04-16', 'return_date' => '2026-04-22', 'days_overdue' => 6, 'fine_amount' => 30.00, 'status' => 'paid'],
            ['book_id' => '08', 'book_title' => 'To Kill a Mockingbird', 'issue_date' => '2026-05-12', 'due_date' => '2026-05-27', 'return_date' => null, 'days_overdue' => 0, 'fine_amount' => 0.00, 'status' => 'pending']
        ]
    ]
];

// Default summary values (shown before searching)
$default_total_fines = 12450.00;
$default_pending_fines = 8750.00;
$default_paid_fines = 3700.00;
$default_overdue_books = 28;

// Status filter options
$status_options = [
    'all' => 'All Fines',
    'pending' => 'Pending',
    'paid' => 'Paid'
];

$status_filter       = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$selected_student_id = isset($_GET['student_id'])    ? trim($_GET['student_id']) : '';

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

// Calculate statistics (use default values if no student selected, otherwise calculate actual)
$total_fines = $default_total_fines;
$pending_fines = $default_pending_fines;
$paid_fines = $default_paid_fines;
$overdue_books = $default_overdue_books;

if ($student_data) {
    $total_fines = 0;
    $pending_fines = 0;
    $paid_fines = 0;
    $overdue_books = 0;
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
        
        /* COMPACT TABLE WITH ALIGNMENT */
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
        
        /* Column alignments */
        .table-fines th:nth-child(1),
        .table-fines td:nth-child(1) {
            text-align: center;
        }
        
        .table-fines th:nth-child(2),
        .table-fines td:nth-child(2) {
            text-align: left;
        }
        
        .table-fines th:nth-child(3),
        .table-fines td:nth-child(3) {
            text-align: center;
        }
        
        .table-fines th:nth-child(4),
        .table-fines td:nth-child(4) {
            text-align: center;
        }
        
        .table-fines th:nth-child(5),
        .table-fines td:nth-child(5) {
            text-align: center;
        }
        
        .table-fines th:nth-child(6),
        .table-fines td:nth-child(6) {
            text-align: center;
        }
        
        .table-fines th:nth-child(7),
        .table-fines td:nth-child(7) {
            text-align: center;
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

        /* Search bar styling */
        .topbar-search {
            position: relative;
            display: flex;
            align-items: center;
            width: min(420px, 100%);
            min-height: 42px;
            padding: 0 14px;
            gap: 10px;
            color: #8b96aa;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96)), #ffffff;
            border: 1px solid rgba(201, 151, 58, 0.28);
            border-radius: 999px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.9);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .topbar-search::before {
            content: "";
            position: absolute;
            inset: -1px;
            z-index: -1;
            border-radius: inherit;
            background: linear-gradient(135deg, rgba(201, 151, 58, 0.38), rgba(232, 194, 106, 0.08));
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .topbar-search:focus-within {
            border-color: rgba(201, 151, 58, 0.72);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.12), 0 0 0 4px rgba(201, 151, 58, 0.14);
            transform: translateY(-1px);
        }

        .topbar-search:focus-within::before {
            opacity: 1;
        }

        .topbar-search svg {
            flex: 0 0 auto;
            width: 16px;
            height: 16px;
            color: #c9973a;
            stroke-width: 2.4;
        }

        .topbar-search input {
            width: 100%;
            min-width: 0;
            height: 40px;
            color: #1f2937;
            background: transparent;
            border: 0;
            outline: 0;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
        }

        .topbar-search input::placeholder {
            color: #98a2b3;
            font-weight: 400;
        }

        @media (max-width: 760px) {
            .topbar {
                flex-wrap: wrap;
            }
            .topbar-search {
                order: 3;
                width: 100%;
                margin-top: 10px;
            }
        }

        @media (max-width: 520px) {
            .topbar-search {
                min-height: 40px;
                padding: 0 12px;
                border-radius: 14px;
              }
            .topbar-search input {
                height: 38px;
                font-size: 13px;
            }
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
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
      <div class="gold-rule"><span></span><i>✦</i><span></span></div>
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
          <span>Pending &amp; Outstanding Fines</span>
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
                                <td style="text-align: center;"><?php echo htmlspecialchars($fine['book_id']); ?></td>
                                <td style="text-align: left;"><?php echo htmlspecialchars($fine['book_title']); ?></td>
                                <td style="text-align: center;"><?php echo date('d/m/Y', strtotime($fine['due_date'])); ?></td>
                                <td style="text-align: center;"><?php echo $fine['days_overdue']; ?></td>
                                <td class="fine-amount" style="text-align: center;">PHP <?php echo number_format($fine['fine_amount'], 2); ?></td>
                                <td style="text-align: center;">
                                    <span class="status-badge <?php echo $fine['status'] === 'pending' ? 'status-pending' : 'status-paid'; ?>">
                                        <?php echo ucfirst($fine['status']); ?>
                                    </span>
                                </td
                                <td style="text-align: center;">
                                    <?php if ($fine['status'] === 'pending' && $fine['fine_amount'] > 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($selected_student_id); ?>">
                                            <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($fine['book_id']); ?>">
                                            <input type="hidden" name="amount" value="<?php echo $fine['fine_amount']; ?>">
                                            <button type="submit" name="pay_fine" class="btn-fines btn-fines-success" style="padding: 5px 12px; font-size: 11px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">
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
                        <button type="submit" name="pay_all" style="background: #dc3545; color: white; padding: 4px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 10px;">
                            Pay All
                        </button>
                    </form>
                </div>
                <?php endif; ?>

            <?php elseif ($selected_student_id && !$student_data): ?>
                <div class="no-data-fines">
                    No student found with ID: <?php echo htmlspecialchars($selected_student_id); ?>
                    <p style="margin-top: 8px; font-size: 11px;">Try: 101, 102, 103, or 104</p>
                </div>
            <?php else: ?>
                <div class="no-data-fines">
                    Enter a Student ID above to view outstanding fines
                    <p style="margin-top: 8px; font-size: 11px;">Example: 101, 102, 103, 104</p>
                </div>
            <?php endif; ?>

        </div>

      <?php endif; ?>

    </div><!-- /.fines-container -->

  </main>

</div><!-- /.main-wrapper -->

</body>
</html>
<?php
// ============================================================
// admin/borrowed_books.php — DB-powered (UI unchanged)
// ============================================================
session_start();
require_once __DIR__ . '/library_data.php';
require_once __DIR__ . '/../classes/BorrowRecord.php';

// Session guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login/login.php');
    exit;
}

$pending_count = pending_request_count();

$db     = new Database();
$borrow = new BorrowRecord($db->getConnection());
$borrow->updateOverdueStatuses();

// Map DB → UI status
function _map_status(string $db_status): string {
    return match($db_status) {
        'active'         => 'borrowed',
        'overdue'        => 'overdue',
        'pending_return' => 'borrowed',
        'returned'       => 'returned',
        default          => 'borrowed',
    };
}

// ── Load all records from DB ──
$db_rows = $borrow->getAll();

// Build the same $borrowed_books_data shape the UI expects
$borrowed_books_data = [];
foreach ($db_rows as $r) {
    $borrowed_books_data[] = [
        'id'            => (int)$r['id'],
        'book_id'       => str_pad((string)$r['book_id'], 2, '0', STR_PAD_LEFT),
        'book_title'    => $r['book_title'],
        'student_id'    => $r['student_number'],
        'student_name'  => $r['student_name'],
        'student_class' => $r['student_number'],  // no "class" column; show student number instead
        'borrow_date'   => $r['borrow_date'],
        'due_date'      => $r['due_date'],
        'return_date'   => $r['return_date'],
        'status'        => _map_status($r['status']),
        'fine'          => 0.00,  // shown in view_fines, not here
    ];
}

// Status filter options
$status_options = [
    'all'      => 'All Books',
    'borrowed' => 'Borrowed',
    'overdue'  => 'Overdue',
    'returned' => 'Returned'
];

$selected_status = $_GET['status'] ?? 'all';
$search_query    = strtolower(trim($_GET['q'] ?? ''));

// Filter
$filtered = array_values(array_filter($borrowed_books_data, function ($book) use ($selected_status, $search_query) {
    $matches_status = $selected_status === 'all' || $book['status'] === $selected_status;

    $book_text = strtolower(
        $book['book_id'] . ' ' .
        $book['book_title'] . ' ' .
        $book['student_name'] . ' ' .
        $book['student_id']
    );

    $matches_search = $search_query === '' || strpos($book_text, $search_query) !== false;
    return $matches_status && $matches_search;
}));

$total = count($filtered);
$books = $filtered;

// Stats from full set
$total_borrowed = 0;
$overdue_count  = 0;
$returned_count = 0;

foreach ($borrowed_books_data as $book) {
    if ($book['status'] !== 'returned') $total_borrowed++;
    if ($book['status'] === 'overdue')  $overdue_count++;
    if ($book['status'] === 'returned') $returned_count++;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Borrowed Books</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
  
  <style>
    .data-table td,
    .data-table th {
        padding: 10px 12px;
        font-size: 13px;
        text-align: left;
        vertical-align: middle;
    }
    
    /* Center align specific columns */
    .data-table td:nth-child(1),
    .data-table th:nth-child(1) {
        text-align: center;
        font-weight: 600;
    }
    
    .data-table td:nth-child(4),
    .data-table th:nth-child(4) {
        text-align: center;
    }
    
    .data-table td:nth-child(7),
    .data-table th:nth-child(7) {
        text-align: center;
    }
    
    /* Date columns - consistent width */
    .data-table td:nth-child(5),
    .data-table th:nth-child(5),
    .data-table td:nth-child(6),
    .data-table th:nth-child(6) {
        white-space: nowrap;
        text-align: center;
    }
    
    .returned-check {
        color: #28a745;
        font-size: 14px;
        font-weight: bold;
    }
    
    .status-badge-borrowed {
        background: #f0f0f0;
        color: #333333;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        text-align: center;
        min-width: 80px;
    }
    
    .status-badge-overdue {
        background: #f8d7da;
        color: #dc3545;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        text-align: center;
        min-width: 80px;
    }
    
    .status-badge-returned {
        background: #d4edda;
        color: #28a745;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        text-align: center;
        min-width: 80px;
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
  </style>
</head>

<body>

<?php include 'sideBar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">
    <span class="topbar-title">Borrowed Books</span>
    <div class="topbar-spacer"></div>

    <form class="topbar-search" method="GET" action="borrowed_books.php">
      <?php if ($selected_status !== 'all'): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($selected_status) ?>">
      <?php endif; ?>

      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/>
        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>

      <input
        type="text"
        name="q"
        placeholder="Search by book title, author, or ID..."
        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
      >
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
      <h1>Borrowed Books</h1>
      <p>Track and manage all books currently borrowed by students</p>
      <div class="gold-rule">
        <span></span>
        <i>*</i>
        <span></span>
      </div>
    </div>

    <div class="books-filter-section">
      <div class="category-pills">
        <?php foreach ($status_options as $value => $label): ?>
          <a
            href="?status=<?= urlencode($value) ?><?= ($_GET['q'] ?? '') !== '' ? '&q=' . urlencode($_GET['q']) : '' ?>"
            class="category-pill <?= $value === $selected_status ? 'active' : '' ?>"
          >
            <?= htmlspecialchars($label) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="view-books-toolbar">
        <p class="view-books-count">
          Showing <strong><?= count($books) ?></strong> of <strong><?= $total ?></strong> borrowed books
          <?php if ($selected_status !== 'all'): ?>
            in <span><?= htmlspecialchars($status_options[$selected_status]) ?></span>
          <?php endif; ?>
        </p>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid" style="margin-bottom: 30px;">
      <div class="stat-card">
        <div class="stat-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
          </svg>
        </div>
        <div class="stat-value"><?= $total_borrowed ?></div>
        <div class="stat-label">TOTAL BORROWED</div>
      </div>

      <div class="stat-card stat-danger">
        <div class="stat-icon icon-danger">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        </div>
        <div class="stat-value"><?= $overdue_count ?></div>
        <div class="stat-label">OVERDUE</div>
      </div>

      <div class="stat-card stat-sage">
        <div class="stat-icon icon-sage">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 6L9 17l-5-5"/>
          </svg>
        </div>
        <div class="stat-value"><?= $returned_count ?></div>
        <div class="stat-label">RETURNED</div>
      </div>
    </div>

    <?php if (empty($books)): ?>
      <div class="card">
        <div class="empty-state">
          <div class="empty-icon">&#128218;</div>
          <h3>No borrowed books found</h3>
          <p>No books match your selected status or search term.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="card">
        <div class="card-body">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Book ID</th>
                  <th>Book Title</th>
                  <th>Student</th>
                  <th>Student ID</th>
                  <th>Borrow Date</th>
                  <th>Due Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($books as $book): ?>
                <tr>
                  <td style="text-align: center; font-weight: 600;"><?= htmlspecialchars($book['book_id']) ?></td>
                  <td style="text-align: left;"><?= htmlspecialchars($book['book_title']) ?></td>
                  <td style="text-align: left;"><?= htmlspecialchars($book['student_name']) ?></td>
                  <td style="text-align: center;"><?= htmlspecialchars($book['student_id']) ?></td>
                  <td style="text-align: center; white-space: nowrap;"><?= date('d/m/Y', strtotime($book['borrow_date'])) ?></td>
                  <td style="text-align: center; white-space: nowrap;"><?= date('d/m/Y', strtotime($book['due_date'])) ?></td>
                  <td style="text-align: center;">
                    <span class="status-badge-<?= $book['status'] ?>">
                        <?= strtoupper($book['status']) ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </main>

</div>

</body>
</html>
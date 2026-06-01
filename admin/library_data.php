<?php
// ============================================================
// admin/library_data.php — DB Helper Functions for Admin Pages
// Used by dashboard.php, sideBar.php, and all admin pages
// ============================================================

require_once __DIR__ . '/../config/Database.php';

// ── Shared DB connection (created once per request) ──────────
function get_db(): mysqli
{
    static $conn = null;
    if ($conn === null) {
        $db   = new Database();
        $conn = $db->getConnection();
        if (!$conn) {
            die('<p style="color:red;font-family:sans-serif;">❌ Database connection failed. Check config/Database.php</p>');
        }
    }
    return $conn;
}

// ════════════════════════════════════════════════════════════
// BOOK STATS
// ════════════════════════════════════════════════════════════

/** Total non-archived books */
function active_book_count(): int
{
    $r = get_db()->query("SELECT COUNT(*) AS c FROM books WHERE is_archived = 0");
    return (int)($r->fetch_assoc()['c'] ?? 0);
}

/** Total archived books */
function archived_book_count(): int
{
    $r = get_db()->query("SELECT COUNT(*) AS c FROM books WHERE is_archived = 1");
    return (int)($r->fetch_assoc()['c'] ?? 0);
}

/** Total books (all) */
function total_book_count(): int
{
    $r = get_db()->query("SELECT COUNT(*) AS c FROM books");
    return (int)($r->fetch_assoc()['c'] ?? 0);
}

// ════════════════════════════════════════════════════════════
// BORROW STATS
// ════════════════════════════════════════════════════════════

/** Currently borrowed (active + overdue + pending return) */
function currently_borrowed_count(): int
{
    $r = get_db()->query(
        "SELECT COUNT(*) AS c FROM borrow_records
         WHERE status IN ('active','overdue','pending_return')"
    );
    return (int)($r->fetch_assoc()['c'] ?? 0);
}

/** Overdue books count */
function overdue_count(): int
{
    $r = get_db()->query(
        "SELECT COUNT(*) AS c FROM borrow_records
         WHERE status = 'active' AND due_date < CURDATE()"
    );
    return (int)($r->fetch_assoc()['c'] ?? 0);
}

/** Pending return requests from students */
function pending_return_count(): int
{
    $r = get_db()->query(
        "SELECT COUNT(*) AS c FROM borrow_records WHERE status = 'pending_return'"
    );
    return (int)($r->fetch_assoc()['c'] ?? 0);
}

/** Get recent borrow records for dashboard table */
function get_recent_borrowed(int $limit = 5): array
{
    $conn = get_db();
    $stmt = $conn->prepare(
        "SELECT b.title AS book_title,
                u.full_name AS student,
                br.borrow_date AS date,
                br.due_date,
                br.status
         FROM borrow_records br
         JOIN books b ON b.id = br.book_id
         JOIN users u ON u.id = br.user_id
         ORDER BY br.created_at DESC
         LIMIT ?"
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ════════════════════════════════════════════════════════════
// FINE STATS
// ════════════════════════════════════════════════════════════

/** Total unpaid fines amount (formatted) */
function pending_fines_total(): string
{
    $r = get_db()->query(
        "SELECT COALESCE(SUM(amount), 0) AS total FROM fines WHERE paid_status = 'unpaid'"
    );
    return number_format((float)($r->fetch_assoc()['total'] ?? 0), 2);
}

/** Count of students with unpaid fines */
function students_with_fines(): int
{
    $r = get_db()->query(
        "SELECT COUNT(DISTINCT user_id) AS c FROM fines WHERE paid_status = 'unpaid'"
    );
    return (int)($r->fetch_assoc()['c'] ?? 0);
}

// ════════════════════════════════════════════════════════════
// REQUEST STATS
// ════════════════════════════════════════════════════════════

/** Pending borrow requests count */
function pending_request_count(): int
{
    $r = get_db()->query(
        "SELECT COUNT(*) AS c FROM book_requests WHERE status = 'pending'"
    );
    return (int)($r->fetch_assoc()['c'] ?? 0);
}

// ════════════════════════════════════════════════════════════
// STUDENT STATS
// ════════════════════════════════════════════════════════════

/** Total registered students */
function total_students(): int
{
    $r = get_db()->query("SELECT COUNT(*) AS c FROM users WHERE role = 'student'");
    return (int)($r->fetch_assoc()['c'] ?? 0);
}
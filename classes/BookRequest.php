<?php
// ============================================================
// classes/BookRequest.php — Student Borrow Request Class
// ============================================================

require_once __DIR__ . '/Notification.php';

class BookRequest
{
    private mysqli $conn;
    private Notification $notif;

    public function __construct(mysqli $conn)
    {
        $this->conn  = $conn;
        $this->notif = new Notification($conn);
    }

    /** Student submits a borrow request with their preferred borrow + due dates */
    public function create(int $userId, int $bookId, ?string $borrowDate = null, ?string $dueDate = null): bool|string
    {
        // Check book available
        $chk = $this->conn->prepare(
            "SELECT copies_available, title FROM books WHERE id = ? AND is_archived = 0 LIMIT 1"
        );
        $chk->bind_param('i', $bookId);
        $chk->execute();
        $book = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$book) return 'Book not found.';
        if ((int)$book['copies_available'] < 1) return 'No available copies for "' . $book['title'] . '".';

        // Check no duplicate pending request
        $dup = $this->conn->prepare(
            "SELECT id FROM book_requests WHERE user_id = ? AND book_id = ? AND status = 'pending' LIMIT 1"
        );
        $dup->bind_param('ii', $userId, $bookId);
        $dup->execute();
        $dup->store_result();
        if ($dup->num_rows > 0) { $dup->close(); return 'You already have a pending request for this book.'; }
        $dup->close();

        // Check not already borrowed
        $brw = $this->conn->prepare(
            "SELECT id FROM borrow_records WHERE user_id = ? AND book_id = ?
             AND status IN ('active','overdue','pending_return') LIMIT 1"
        );
        $brw->bind_param('ii', $userId, $bookId);
        $brw->execute();
        $brw->store_result();
        if ($brw->num_rows > 0) { $brw->close(); return 'You already have this book borrowed.'; }
        $brw->close();

        // Validate dates (optional but if provided must be valid Y-m-d)
        $today = date('Y-m-d');
        $isValidDate = fn($d) => $d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

        $bd = $isValidDate($borrowDate) ? $borrowDate : $today;
        $dd = $isValidDate($dueDate)    ? $dueDate    : date('Y-m-d', strtotime('+7 days'));

        // Date range rules
        $maxBorrow = date('Y-m-d', strtotime('+7 days')); // borrow within next 7 days
        if (strtotime($bd) < strtotime($today)) {
            return 'Borrow date cannot be in the past.';
        }
        if (strtotime($bd) > strtotime($maxBorrow)) {
            return 'Borrow date must be within the next 7 days.';
        }

        // Return must be 1–7 days after borrow date
        $minReturn = date('Y-m-d', strtotime($bd . ' +1 day'));
        $maxReturn = date('Y-m-d', strtotime($bd . ' +7 days'));
        if (strtotime($dd) < strtotime($minReturn)) {
            return 'Return date must be at least 1 day after the borrow date.';
        }
        if (strtotime($dd) > strtotime($maxReturn)) {
            return 'Return date can only be up to 7 days after the borrow date.';
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO book_requests (user_id, book_id, status, requested_borrow_date, requested_due_date)
             VALUES (?, ?, 'pending', ?, ?)"
        );
        $stmt->bind_param('iiss', $userId, $bookId, $bd, $dd);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Get all pending requests (admin) */
    public function getPending(): array
    {
        $result = $this->conn->query(
            "SELECT br.*, b.title AS book_title, b.copies_available,
                    u.full_name AS student_name, u.student_number
             FROM book_requests br
             JOIN books b ON b.id = br.book_id
             JOIN users u ON u.id = br.user_id
             WHERE br.status = 'pending'
             ORDER BY br.request_date ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Get all requests (admin, all statuses) */
    public function getAll(): array
    {
        $result = $this->conn->query(
            "SELECT br.*, b.title AS book_title, b.copies_available,
                    u.full_name AS student_name, u.student_number
             FROM book_requests br
             JOIN books b ON b.id = br.book_id
             JOIN users u ON u.id = br.user_id
             ORDER BY br.request_date DESC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Get requests for one student */
    public function getByStudent(int $userId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT br.*, b.title AS book_title, b.author, b.copies_available
             FROM book_requests br
             JOIN books b ON b.id = br.book_id
             WHERE br.user_id = ?
             ORDER BY br.request_date DESC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Approve request → creates borrow_record + decreases copies
     * Uses the dates the student picked when they submitted the request.
     * Returns true or error string
     */
    public function approve(int $requestId, int $adminId): bool|string
    {
        // Load request including the dates the student picked
        $stmt = $this->conn->prepare(
            "SELECT br.*, b.title AS book_title FROM book_requests br
             JOIN books b ON b.id = br.book_id
             WHERE br.id = ? AND br.status = 'pending' LIMIT 1"
        );
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $req = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$req) return 'Request not found or already processed.';

        // Check availability again
        $chk = $this->conn->prepare(
            "SELECT copies_available FROM books WHERE id = ? LIMIT 1"
        );
        $chk->bind_param('i', $req['book_id']);
        $chk->execute();
        $book = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$book || (int)$book['copies_available'] < 1) return 'No copies available.';

        // Use dates from the request — fall back if not stored (older requests)
        $borrowDate = !empty($req['requested_borrow_date']) ? $req['requested_borrow_date'] : date('Y-m-d');
        $dueDate    = !empty($req['requested_due_date'])    ? $req['requested_due_date']    : date('Y-m-d', strtotime('+7 days'));
        $status     = 'active';

        $ins = $this->conn->prepare(
            "INSERT INTO borrow_records (user_id, book_id, issued_by, borrow_date, due_date, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $ins->bind_param('iiisss', $req['user_id'], $req['book_id'], $adminId, $borrowDate, $dueDate, $status);
        if (!$ins->execute()) return 'Failed to create borrow record.';
        $ins->close();

        // Decrease copies
        $dec = $this->conn->prepare(
            "UPDATE books SET copies_available = copies_available - 1 WHERE id = ? AND copies_available > 0"
        );
        $dec->bind_param('i', $req['book_id']);
        $dec->execute();
        $dec->close();

        // Mark request approved
        $upd = $this->conn->prepare(
            "UPDATE book_requests SET status = 'approved', processed_by = ? WHERE id = ?"
        );
        $upd->bind_param('ii', $adminId, $requestId);
        $upd->execute();
        $upd->close();

        // ── Notify the student ──
        $this->notif->create(
            (int)$req['user_id'],
            'approved',
            'Borrow Request Approved',
            'Your request for "' . $req['book_title'] . '" was approved. Due date: ' . date('M j, Y', strtotime($dueDate)) . '.'
        );

        return true;
    }

    /** Reject a request */
    public function reject(int $requestId, int $adminId): bool
    {
        // Load request (with book title for the notification)
        $sel = $this->conn->prepare(
            "SELECT br.user_id, b.title AS book_title FROM book_requests br
             JOIN books b ON b.id = br.book_id
             WHERE br.id = ? AND br.status = 'pending' LIMIT 1"
        );
        $sel->bind_param('i', $requestId);
        $sel->execute();
        $req = $sel->get_result()->fetch_assoc();
        $sel->close();
        if (!$req) return false;

        $stmt = $this->conn->prepare(
            "UPDATE book_requests SET status = 'rejected', processed_by = ?
             WHERE id = ? AND status = 'pending'"
        );
        $stmt->bind_param('ii', $adminId, $requestId);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();

        // ── Notify the student ──
        if ($ok) {
            $this->notif->create(
                (int)$req['user_id'],
                'rejected',
                'Borrow Request Rejected',
                'Your request for "' . $req['book_title'] . '" was not approved. Please contact the library for details.'
            );
        }

        return $ok;
    }

    /**
     * Student cancels their OWN pending request.
     * Verifies the request belongs to this user AND is still pending.
     * Hard-deletes the request (so it disappears from the table).
     */
    public function cancel(int $requestId, int $userId): bool|string
    {
        // Verify ownership + pending status
        $chk = $this->conn->prepare(
            "SELECT id FROM book_requests
             WHERE id = ? AND user_id = ? AND status = 'pending' LIMIT 1"
        );
        $chk->bind_param('ii', $requestId, $userId);
        $chk->execute();
        $chk->store_result();
        $exists = $chk->num_rows > 0;
        $chk->close();

        if (!$exists) return 'Request not found or cannot be cancelled.';

        $stmt = $this->conn->prepare(
            "DELETE FROM book_requests
             WHERE id = ? AND user_id = ? AND status = 'pending'"
        );
        $stmt->bind_param('ii', $requestId, $userId);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }
}
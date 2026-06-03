<?php
// ============================================================
// classes/BorrowRecord.php — Borrow/Return/Request Class
// ============================================================

require_once __DIR__ . '/Notification.php';

class BorrowRecord
{
    private mysqli $conn;
    private Notification $notif;
    const FINE_RATE = 5.00; // PHP per overdue day

    public function __construct(mysqli $conn)
    {
        $this->conn  = $conn;
        $this->notif = new Notification($conn);
    }

    // ════════════════════════════════════════════════════════
    // ISSUE BOOK (Admin manually issues)
    // ════════════════════════════════════════════════════════

    /**
     * Issue a book to a student
     * Returns true, or error string
     */
    public function issue(int $userId, int $bookId, string $borrowDate, string $dueDate, int $issuedBy): bool|string
    {
        // Check book exists and has available copies
        $chk = $this->conn->prepare(
            "SELECT id, copies_available, title FROM books WHERE id = ? AND is_archived = 0 LIMIT 1"
        );
        $chk->bind_param('i', $bookId);
        $chk->execute();
        $book = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$book)                          return 'Book not found.';
        if ((int)$book['copies_available'] < 1) return 'No available copies for "' . $book['title'] . '".';

        // Check student exists
        $su = $this->conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'student' LIMIT 1");
        $su->bind_param('i', $userId);
        $su->execute();
        $su->store_result();
        if ($su->num_rows === 0) { $su->close(); return 'Student not found.'; }
        $su->close();

        // Check student doesn't already have this book
        $dup = $this->conn->prepare(
            "SELECT id FROM borrow_records WHERE user_id = ? AND book_id = ? AND status IN ('active','overdue','pending_return') LIMIT 1"
        );
        $dup->bind_param('ii', $userId, $bookId);
        $dup->execute();
        $dup->store_result();
        if ($dup->num_rows > 0) { $dup->close(); return 'This student already has this book borrowed.'; }
        $dup->close();

        // Insert borrow record
        $stmt = $this->conn->prepare(
            "INSERT INTO borrow_records (user_id, book_id, issued_by, borrow_date, due_date, status)
             VALUES (?, ?, ?, ?, ?, 'active')"
        );
        $stmt->bind_param('iiiss', $userId, $bookId, $issuedBy, $borrowDate, $dueDate);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) return 'Failed to create borrow record.';

        // Decrease available copies
        $upd = $this->conn->prepare(
            "UPDATE books SET copies_available = copies_available - 1 WHERE id = ? AND copies_available > 0"
        );
        $upd->bind_param('i', $bookId);
        $upd->execute();
        $upd->close();

        return true;
    }

    // ════════════════════════════════════════════════════════
    // RETURN BOOK (Admin confirms return)
    // ════════════════════════════════════════════════════════

    /**
     * Admin confirms a return — calculates fine, marks returned, restores copy
     * Returns ['fine' => float, 'days' => int] on success, or error string
     */
    public function confirmReturn(int $recordId): array|string
    {
        // Load record
        $stmt = $this->conn->prepare(
            "SELECT br.*, b.title AS book_title FROM borrow_records br
             JOIN books b ON b.id = br.book_id
             WHERE br.id = ? AND br.status IN ('active','overdue','pending_return') LIMIT 1"
        );
        $stmt->bind_param('i', $recordId);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$record) return 'Borrow record not found or already returned.';

        // Calculate overdue fine
        $today      = new DateTime(date('Y-m-d'));
        $due        = new DateTime($record['due_date']);
        $daysOverdue = max(0, (int)$today->diff($due)->days * ($today > $due ? 1 : -1));
        $fineAmount  = $daysOverdue * self::FINE_RATE;

        // Mark returned
        $returnDate = date('Y-m-d');
        $status     = 'returned';
        $upd = $this->conn->prepare(
            "UPDATE borrow_records SET status = ?, return_date = ? WHERE id = ?"
        );
        $upd->bind_param('ssi', $status, $returnDate, $recordId);
        $upd->execute();
        $upd->close();

        // Restore book copy
        $restore = $this->conn->prepare(
            "UPDATE books SET copies_available = copies_available + 1 WHERE id = ? AND copies_available < total_copies"
        );
        $restore->bind_param('i', $record['book_id']);
        $restore->execute();
        $restore->close();

        // Create or update fine record if overdue
        if ($fineAmount > 0) {
            // How much has already been paid for this borrow (from previous fine cycles)?
            $sumStmt = $this->conn->prepare(
                "SELECT COALESCE(SUM(amount), 0) AS already_paid
                 FROM fines
                 WHERE borrow_id = ? AND paid_status = 'paid'"
            );
            $sumStmt->bind_param('i', $recordId);
            $sumStmt->execute();
            $alreadyPaid = (float)$sumStmt->get_result()->fetch_assoc()['already_paid'];
            $sumStmt->close();

            // Remaining = total fine owed - what's already paid
            $remaining = max(0, $fineAmount - $alreadyPaid);

            // Find an existing unpaid (or payment_requested) row to update
            $chk = $this->conn->prepare(
                "SELECT id FROM fines
                 WHERE borrow_id = ? AND paid_status IN ('unpaid','payment_requested')
                 ORDER BY id DESC LIMIT 1"
            );
            $chk->bind_param('i', $recordId);
            $chk->execute();
            $existing = $chk->get_result()->fetch_assoc();
            $chk->close();

            if ($remaining > 0) {
                if ($existing) {
                    // Update the existing unpaid/pending row to the final remaining amount
                    $upd = $this->conn->prepare("UPDATE fines SET amount = ? WHERE id = ?");
                    $upd->bind_param('di', $remaining, $existing['id']);
                    $upd->execute();
                    $upd->close();
                } else {
                    // No unpaid row exists yet — create one for the remaining
                    $fi = $this->conn->prepare(
                        "INSERT INTO fines (borrow_id, user_id, amount, paid_status) VALUES (?, ?, ?, 'unpaid')"
                    );
                    $fi->bind_param('iid', $recordId, $record['user_id'], $remaining);
                    $fi->execute();
                    $fi->close();
                }
            } elseif ($existing) {
                // Already fully covered by previous payments — remove the stale unpaid row
                $del = $this->conn->prepare("DELETE FROM fines WHERE id = ?");
                $del->bind_param('i', $existing['id']);
                $del->execute();
                $del->close();
            }

            // Notify student about return + fine
            $this->notif->create(
                (int)$record['user_id'],
                'fine',
                'Book Returned — Fine Applied',
                '"' . $record['book_title'] . '" returned. Total fine: PHP ' . number_format($fineAmount, 2) .
                ' for ' . $daysOverdue . ' overdue day' . ($daysOverdue > 1 ? 's' : '') .
                ($remaining > 0 ? ' (₱' . number_format($remaining, 2) . ' remaining).' : ' (already paid).')
            );
        } else {
            // Notify student about on-time return
            $this->notif->create(
                (int)$record['user_id'],
                'returned',
                'Book Returned',
                '"' . $record['book_title'] . '" was returned successfully. No fine. Thank you!'
            );
        }

        return ['fine' => $fineAmount, 'days' => $daysOverdue];
    }

    /**
     * Student initiates a return request (sets status to pending_return)
     */
    public function initiateReturn(int $recordId, int $userId): bool|string
    {
        $stmt = $this->conn->prepare(
            "UPDATE borrow_records SET status = 'pending_return'
             WHERE id = ? AND user_id = ? AND status IN ('active','overdue')"
        );
        $stmt->bind_param('ii', $recordId, $userId);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok ?: 'Record not found or already processed.';
    }

    /**
     * Admin rejects a return request (revert to active/overdue)
     */
    public function rejectReturn(int $recordId): bool
    {
        $today  = date('Y-m-d');
        // Determine if overdue
        $status = 'active';
        $chk = $this->conn->prepare(
            "SELECT br.due_date, br.user_id, b.title AS book_title
             FROM borrow_records br
             JOIN books b ON b.id = br.book_id
             WHERE br.id = ? LIMIT 1"
        );
        $chk->bind_param('i', $recordId);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($row && $row['due_date'] < $today) $status = 'overdue';

        $stmt = $this->conn->prepare(
            "UPDATE borrow_records SET status = ? WHERE id = ? AND status = 'pending_return'"
        );
        $stmt->bind_param('si', $status, $recordId);
        $ok = $stmt->execute();
        $stmt->close();

        // Notify the student that their return was rejected
        if ($ok && $row) {
            $this->notif->create(
                (int)$row['user_id'],
                'rejected',
                'Return Request Rejected',
                'Your return request for "' . $row['book_title'] . '" was rejected. ' .
                'The book is still in your borrow list. Please contact the library.'
            );
        }

        return $ok;
    }

    /**
     * Student cancels their own pending return request.
     * Flips status from 'pending_return' back to 'active' (or 'overdue' if past due date).
     * Verifies the record belongs to this user AND is actually pending_return.
     */
    public function cancelReturnRequest(int $recordId, int $userId): bool|string
    {
        // Check ownership + status + get due_date for status restore
        $chk = $this->conn->prepare(
            "SELECT due_date FROM borrow_records
             WHERE id = ? AND user_id = ? AND status = 'pending_return' LIMIT 1"
        );
        $chk->bind_param('ii', $recordId, $userId);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$row) return 'Return request not found or cannot be cancelled.';

        // Determine restore status (overdue if past due, otherwise active)
        $today  = date('Y-m-d');
        $status = $row['due_date'] < $today ? 'overdue' : 'active';

        $stmt = $this->conn->prepare(
            "UPDATE borrow_records SET status = ?
             WHERE id = ? AND user_id = ? AND status = 'pending_return'"
        );
        $stmt->bind_param('sii', $status, $recordId, $userId);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    // ════════════════════════════════════════════════════════
    // READ / QUERY
    // ════════════════════════════════════════════════════════

    /** All active/overdue/pending_return records (admin view) */
    public function getAllActive(string $search = '', string $status = ''): array
    {
        $where  = ["br.status != 'returned'"];
        $params = [];
        $types  = '';

        if ($status !== '' && $status !== 'all') {
            $where[]  = 'br.status = ?';
            $params[] = $status;
            $types   .= 's';
        }

        if ($search !== '') {
            $like     = '%' . $search . '%';
            $where[]  = '(b.title LIKE ? OR u.full_name LIKE ? OR u.student_number LIKE ?)';
            $params[] = $like; $params[] = $like; $params[] = $like;
            $types   .= 'sss';
        }

        $sql = "SELECT br.*, b.title AS book_title, b.author,
                       u.full_name AS student_name, u.student_number,
                       DATEDIFF(CURDATE(), br.due_date) AS days_overdue
                FROM borrow_records br
                JOIN books b ON b.id = br.book_id
                JOIN users u ON u.id = br.user_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY br.status DESC, br.due_date ASC";

        if (empty($params)) {
            $result = $this->conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** All records (admin view, all statuses) */
    public function getAll(string $search = '', string $status = 'all'): array
    {
        $where  = ['1=1'];
        $params = [];
        $types  = '';

        if ($status !== '' && $status !== 'all') {
            $where[]  = 'br.status = ?';
            $params[] = $status;
            $types   .= 's';
        }

        if ($search !== '') {
            $like     = '%' . $search . '%';
            $where[]  = '(b.title LIKE ? OR u.full_name LIKE ? OR u.student_number LIKE ?)';
            $params[] = $like; $params[] = $like; $params[] = $like;
            $types   .= 'sss';
        }

        $sql = "SELECT br.*, b.title AS book_title,
                       u.full_name AS student_name, u.student_number,
                       DATEDIFF(CURDATE(), br.due_date) AS days_overdue
                FROM borrow_records br
                JOIN books b ON b.id = br.book_id
                JOIN users u ON u.id = br.user_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY br.created_at DESC";

        if (empty($params)) {
            $result = $this->conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Pending return requests only */
    public function getPendingReturns(): array
    {
        $result = $this->conn->query(
            "SELECT br.*, b.title AS book_title,
                    u.full_name AS student_name, u.student_number,
                    DATEDIFF(CURDATE(), br.due_date) AS days_overdue,
                    GREATEST(0, DATEDIFF(CURDATE(), br.due_date)) * " . self::FINE_RATE . " AS fine_if_confirmed
             FROM borrow_records br
             JOIN books b ON b.id = br.book_id
             JOIN users u ON u.id = br.user_id
             WHERE br.status = 'pending_return'
             ORDER BY br.due_date ASC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Borrow history for one student */
    public function getByStudent(int $userId): array
    {
        // Aggregate fines per borrow so we get ONE row per borrow_record,
        // never duplicated even if multiple fine rows exist for the same borrow.
        $stmt = $this->conn->prepare(
            "SELECT br.*, b.title AS book_title, b.author, b.category,
                    COALESCE(SUM(CASE WHEN f.paid_status = 'unpaid' THEN f.amount ELSE 0 END), 0) AS unpaid_fine,
                    COALESCE(SUM(CASE WHEN f.paid_status = 'paid'   THEN f.amount ELSE 0 END), 0) AS paid_fine,
                    COALESCE(SUM(f.amount), 0) AS fine_amount,
                    CASE
                        WHEN SUM(CASE WHEN f.paid_status = 'unpaid'            THEN 1 ELSE 0 END) > 0 THEN 'unpaid'
                        WHEN SUM(CASE WHEN f.paid_status = 'payment_requested' THEN 1 ELSE 0 END) > 0 THEN 'payment_requested'
                        WHEN SUM(CASE WHEN f.paid_status = 'paid'              THEN 1 ELSE 0 END) > 0 THEN 'paid'
                        ELSE NULL
                    END AS paid_status
             FROM borrow_records br
             JOIN books b ON b.id = br.book_id
             LEFT JOIN fines f ON f.borrow_id = br.id
             WHERE br.user_id = ?
             GROUP BY br.id
             ORDER BY br.created_at DESC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Get one record by ID */
    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT br.*, b.title AS book_title, u.full_name AS student_name, u.student_number
             FROM borrow_records br
             JOIN books b ON b.id = br.book_id
             JOIN users u ON u.id = br.user_id
             WHERE br.id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?? false;
    }

    /** Find student by student_number (for issue form lookup) */
    public function findStudent(string $studentNumber): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT id, full_name, student_number, course FROM users
             WHERE student_number = ? AND role = 'student' LIMIT 1"
        );
        $stmt->bind_param('s', $studentNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?? false;
    }

    /**
     * Mark overdue borrows AND auto-create/update fines for them.
     * Called on every page load so fines reflect current day count in real-time.
     * - Marks status = 'overdue' for any past-due active record
     * - Updates existing UNPAID fines to today's day count
     * - Creates a NEW fine if:
     *     a) the borrow is overdue but has no fine yet, OR
     *     b) the borrow is overdue, the previous fine was already paid, and more days have passed
     * - Notifies the student the FIRST time a fine is created for each borrow
     */
    public function updateOverdueStatuses(): void
    {
        $rate = self::FINE_RATE;

        // 1) Flip active → overdue when past due date
        $this->conn->query(
            "UPDATE borrow_records SET status = 'overdue'
             WHERE status = 'active' AND due_date < CURDATE()"
        );

        // 2) Update existing UNPAID fines to match current overdue days
        $this->conn->query(
            "UPDATE fines f
             JOIN borrow_records br ON br.id = f.borrow_id
             SET f.amount = DATEDIFF(CURDATE(), br.due_date) * $rate
                          - COALESCE((
                              SELECT SUM(amount) FROM fines f2
                              WHERE f2.borrow_id = f.borrow_id
                                AND f2.id != f.id
                                AND f2.paid_status = 'paid'
                            ), 0)
             WHERE f.paid_status = 'unpaid'
               AND br.status IN ('overdue','pending_return')
               AND br.return_date IS NULL
               AND br.due_date < CURDATE()"
        );

        // 3a) BEFORE creating fines, find borrows that will get their FIRST fine
        //     (no fines exist for this borrow at all yet)
        $newOverdueRes = $this->conn->query(
            "SELECT br.id AS borrow_id, br.user_id, br.due_date,
                    b.title AS book_title,
                    (DATEDIFF(CURDATE(), br.due_date) * $rate) AS fine_amount,
                    DATEDIFF(CURDATE(), br.due_date) AS days_overdue
             FROM borrow_records br
             JOIN books b ON b.id = br.book_id
             WHERE br.status IN ('overdue','pending_return')
               AND br.return_date IS NULL
               AND br.due_date < CURDATE()
               AND NOT EXISTS (SELECT 1 FROM fines f WHERE f.borrow_id = br.id)"
        );
        $newFines = $newOverdueRes ? $newOverdueRes->fetch_all(MYSQLI_ASSOC) : [];

        // 3b) Now insert new fines (covers both first-ever AND additional-cycle cases)
        $this->conn->query(
            "INSERT INTO fines (borrow_id, user_id, amount, paid_status)
             SELECT br.id, br.user_id,
                    (DATEDIFF(CURDATE(), br.due_date) * $rate) - COALESCE(SUM(f.amount), 0) AS remaining,
                    'unpaid'
             FROM borrow_records br
             LEFT JOIN fines f ON f.borrow_id = br.id
             WHERE br.status IN ('overdue','pending_return')
               AND br.return_date IS NULL
               AND br.due_date < CURDATE()
               AND NOT EXISTS (
                   SELECT 1 FROM fines f2
                   WHERE f2.borrow_id = br.id
                     AND f2.paid_status IN ('unpaid','payment_requested')
               )
             GROUP BY br.id, br.user_id, br.due_date
             HAVING remaining > 0"
        );

        // 3c) Send notification ONCE per borrow that just got its first fine
        foreach ($newFines as $nf) {
            $this->notif->create(
                (int)$nf['user_id'],
                'fine',
                'Overdue Fine — ₱' . number_format((float)$nf['fine_amount'], 2),
                '"' . $nf['book_title'] . '" is now ' . (int)$nf['days_overdue'] .
                ' day' . ((int)$nf['days_overdue'] > 1 ? 's' : '') . ' overdue. ' .
                'A fine of ₱' . number_format((float)$nf['fine_amount'], 2) .
                ' has been applied. Please return the book and settle the fine.'
            );
        }
    }
}
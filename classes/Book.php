<?php
// ============================================================
// classes/Book.php — Book CRUD Class
// ============================================================

class Book
{
    private mysqli $conn;

    // Fine rate per overdue day (PHP)
    const FINE_RATE = 5.00;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    // ════════════════════════════════════════════════════════
    // READ
    // ════════════════════════════════════════════════════════

    /** Get all active (non-archived) books with optional search + category filter */
    public function getAll(string $search = '', string $category = ''): array
    {
        $where  = ['b.is_archived = 0'];
        $params = [];
        $types  = '';

        if ($category !== '' && $category !== 'All') {
            $where[]  = 'b.category = ?';
            $params[] = $category;
            $types   .= 's';
        }

        if ($search !== '') {
            $like     = '%' . $search . '%';
            $where[]  = '(b.title LIKE ? OR b.author LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $types   .= 'ss';
        }

        $sql = 'SELECT * FROM books WHERE ' . implode(' AND ', $where) . ' ORDER BY b.id ASC';
        // alias not needed for simple select
        $sql = 'SELECT * FROM books WHERE ' . implode(' AND ', str_replace('b.', '', $where)) . ' ORDER BY id ASC';

        if (empty($params)) {
            $result = $this->conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Get all archived books */
    public function getArchived(): array
    {
        $result = $this->conn->query(
            'SELECT * FROM books WHERE is_archived = 1 ORDER BY id ASC'
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Get one book by ID */
    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare('SELECT * FROM books WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?? false;
    }

    /** Get all distinct categories from active books */
    public function getCategories(): array
    {
        $result = $this->conn->query(
            "SELECT DISTINCT category FROM books WHERE is_archived = 0 ORDER BY category ASC"
        );
        if (!$result) return [];
        $cats = ['All'];
        while ($row = $result->fetch_assoc()) {
            $cats[] = $row['category'];
        }
        return $cats;
    }

    // ════════════════════════════════════════════════════════
    // CREATE
    // ════════════════════════════════════════════════════════

    /**
     * Add a new book
     * Returns true on success
     */
    public function add(array $data): bool|string
    {
        $copies   = (int)$data['copies'];
        $added_by = (int)($data['added_by'] ?? 0);

        $stmt = $this->conn->prepare(
            'INSERT INTO books (title, author, category, total_copies, copies_available, added_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'sssiii',
            $data['title'],
            $data['author'],
            $data['category'],
            $copies,
            $copies,   // copies_available = total_copies on creation
            $added_by
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ════════════════════════════════════════════════════════
    // UPDATE
    // ════════════════════════════════════════════════════════

    /**
     * Update book info
     * Adjusts copies_available if total_copies is reduced
     */
    public function update(int $id, array $data): bool|string
    {
        // Get current copies_available to adjust if total reduced
        $current = $this->getById($id);
        if (!$current) return false;

        $new_total     = (int)$data['copies'];
        $borrowed      = (int)$current['total_copies'] - (int)$current['copies_available'];
        $new_available = max(0, $new_total - $borrowed);

        $stmt = $this->conn->prepare(
            'UPDATE books
             SET title = ?, author = ?, category = ?,
                 total_copies = ?, copies_available = ?
             WHERE id = ?'
        );
        $stmt->bind_param(
            'sssiii',
            $data['title'],
            $data['author'],
            $data['category'],
            $new_total,
            $new_available,
            $id
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ════════════════════════════════════════════════════════
    // DELETE / ARCHIVE
    // ════════════════════════════════════════════════════════

    /**
     * Soft-delete: move to archive (is_archived = 1)
     * Returns error string if book has active borrows
     */
    public function archive(int $id): bool|string
    {
        // Block if actively borrowed
        $chk = $this->conn->prepare(
            "SELECT COUNT(*) AS c FROM borrow_records
             WHERE book_id = ? AND status IN ('active','overdue','pending_return')"
        );
        $chk->bind_param('i', $id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();

        if ((int)$row['c'] > 0) {
            return 'Cannot archive: book is currently borrowed.';
        }

        $stmt = $this->conn->prepare('UPDATE books SET is_archived = 1 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Restore a book from archive */
    public function restore(int $id): bool
    {
        $stmt = $this->conn->prepare('UPDATE books SET is_archived = 0 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Permanently delete an archived book */
    public function delete(int $id): bool|string
    {
        // Only allow deleting archived books
        $book = $this->getById($id);
        if (!$book || !$book['is_archived']) {
            return 'Only archived books can be permanently deleted.';
        }

        $stmt = $this->conn->prepare('DELETE FROM books WHERE id = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ════════════════════════════════════════════════════════
    // AVAILABILITY HELPERS
    // ════════════════════════════════════════════════════════

    /** Decrease available copies by 1 (on issue) */
    public function decreaseCopies(int $id): bool
    {
        $stmt = $this->conn->prepare(
            'UPDATE books SET copies_available = copies_available - 1
             WHERE id = ? AND copies_available > 0'
        );
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    /** Increase available copies by 1 (on return) */
    public function increaseCopies(int $id): bool
    {
        $stmt = $this->conn->prepare(
            'UPDATE books SET copies_available = copies_available + 1
             WHERE id = ? AND copies_available < total_copies'
        );
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
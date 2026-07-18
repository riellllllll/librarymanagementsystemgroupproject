<?php
// ============================================================
// includes/books_dom.php — XML Export & Import using DOMDocument
// Satisfies professor requirement #5 (XML + DOM Integration)
// ============================================================

require_once __DIR__ . '/../config/Database.php';

// ════════════════════════════════════════════════════════════
// A. XML EXPORT — Build an XML string of all books from the DB
//    Uses: $dom = new DOMDocument("1.0", "UTF-8");
// ════════════════════════════════════════════════════════════
function export_books_to_xml(mysqli $conn): string
{
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;

    // Root <books> element
    $root = $dom->createElement('books');
    $dom->appendChild($root);

    $result = $conn->query(
        "SELECT id, title, author, category,
                total_copies, copies_available
         FROM books
         ORDER BY id ASC"
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bookEl = $dom->createElement('book');

            // Each field becomes a child node
            $bookEl->appendChild($dom->createElement('id', htmlspecialchars($row['id'])));
            $bookEl->appendChild($dom->createElement('title', htmlspecialchars($row['title'])));
            $bookEl->appendChild($dom->createElement('author', htmlspecialchars($row['author'])));
            $bookEl->appendChild($dom->createElement('category', htmlspecialchars($row['category'])));
            $bookEl->appendChild($dom->createElement('total_copies', htmlspecialchars($row['total_copies'])));
            $bookEl->appendChild($dom->createElement('copies_available', htmlspecialchars($row['copies_available'])));

            $root->appendChild($bookEl);
        }
    }

    return $dom->saveXML();
}

// ════════════════════════════════════════════════════════════
// EXPORT — Students (bonus: also satisfies "export all users")
// ════════════════════════════════════════════════════════════
function export_students_to_xml(mysqli $conn): string
{
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;

    $root = $dom->createElement('students');
    $dom->appendChild($root);

    $result = $conn->query(
        "SELECT id, student_number, full_name, email, course, year_level
         FROM users WHERE role = 'student' ORDER BY id ASC"
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $s = $dom->createElement('student');
            $s->appendChild($dom->createElement('id', htmlspecialchars($row['id'])));
            $s->appendChild($dom->createElement('student_number', htmlspecialchars($row['student_number'])));
            $s->appendChild($dom->createElement('name', htmlspecialchars($row['full_name'])));
            $s->appendChild($dom->createElement('email', htmlspecialchars($row['email'])));
            $s->appendChild($dom->createElement('course', htmlspecialchars($row['course'] ?? '')));
            $s->appendChild($dom->createElement('year_level', htmlspecialchars($row['year_level'] ?? '')));
            $root->appendChild($s);
        }
    }

    return $dom->saveXML();
}

// ════════════════════════════════════════════════════════════
// IMPORT — Students (mirrors export_students_to_xml's shape)
//    Reuses User::addStudent() so imported students get the same
//    password hashing + duplicate checks as the "Add Student" form.
//    Returns ['inserted' => int, 'skipped' => int, 'errors' => array]
// ════════════════════════════════════════════════════════════
function import_students_from_xml(mysqli $conn, string $xmlFilePath): array
{
    require_once __DIR__ . '/../classes/User.php';

    $report = ['inserted' => 0, 'skipped' => 0, 'errors' => []];

    if (!file_exists($xmlFilePath)) {
        $report['errors'][] = 'XML file not found.';
        return $report;
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;

    libxml_use_internal_errors(true);
    if (!$dom->load($xmlFilePath)) {
        foreach (libxml_get_errors() as $e) {
            $report['errors'][] = trim($e->message);
        }
        libxml_clear_errors();
        $report['errors'][] = 'Invalid or malformed XML file.';
        return $report;
    }
    libxml_clear_errors();

    $studentNodes = $dom->getElementsByTagName('student');

    if ($studentNodes->length === 0) {
        $report['errors'][] = 'No <student> elements found in the XML.';
        return $report;
    }

    $usr = new User($conn);

    // Imported students have no password in the XML (by design — passwords
    // are never exported), so everyone gets this default and should change
    // it on first login, same as accounts created via "Add Student".
    $default_password = 'CvSU@2026';

    foreach ($studentNodes as $node) {
        $student_number = dom_value($node, 'student_number') ?: dom_value($node, 'id_number');
        $email          = dom_value($node, 'email');

        // Prefer explicit first_name/last_name tags; fall back to splitting
        // a combined "name"/"full_name" tag (that's what our own export produces).
        $first_name = dom_value($node, 'first_name');
        $last_name  = dom_value($node, 'last_name');
        if ($first_name === '' && $last_name === '') {
            $full  = dom_value($node, 'name') ?: dom_value($node, 'full_name');
            $parts = preg_split('/\s+/', trim($full), 2);
            $first_name = $parts[0] ?? '';
            $last_name  = $parts[1] ?? '';
        }

        $course     = dom_value($node, 'course');
        $year_level = dom_value($node, 'year_level') ?: dom_value($node, 'year');

        if ($student_number === '' || $email === '' || $first_name === '') {
            $report['skipped']++;
            $report['errors'][] = 'Skipped a student with missing student_number/email/name.';
            continue;
        }

        $data = [
            'student_number' => $student_number,
            'first_name'     => $first_name,
            'last_name'      => $last_name,
            'email'          => strtolower($email),
            'course'         => $course,
            'year_level'     => $year_level,
            'password'       => $default_password,
        ];

        if ($usr->addStudent($data)) {
            $report['inserted']++;
        } else {
            $report['skipped']++;
            $report['errors'][] = 'Skipped "' . htmlspecialchars($student_number) . '" — student number or email already exists.';
        }
    }

    if ($report['inserted'] > 0) {
        $report['errors'][] = 'Imported students were given the default password "' . $default_password . '" — ask them to change it after logging in.';
    }

    return $report;
}

// ════════════════════════════════════════════════════════════
// B. XML IMPORT — Parse an uploaded XML file and INSERT into DB
//    Uses DOMDocument->load() + getElementsByTagName()
//    Returns ['inserted' => int, 'skipped' => int, 'errors' => array]
// ════════════════════════════════════════════════════════════
function import_books_from_xml(mysqli $conn, string $xmlFilePath): array
{
    $report = ['inserted' => 0, 'skipped' => 0, 'errors' => []];

    if (!file_exists($xmlFilePath)) {
        $report['errors'][] = 'XML file not found.';
        return $report;
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;

    // Suppress libxml warnings, capture them instead
    libxml_use_internal_errors(true);
    if (!$dom->load($xmlFilePath)) {
        foreach (libxml_get_errors() as $e) {
            $report['errors'][] = trim($e->message);
        }
        libxml_clear_errors();
        $report['errors'][] = 'Invalid or malformed XML file.';
        return $report;
    }
    libxml_clear_errors();

    $bookNodes = $dom->getElementsByTagName('book');

    if ($bookNodes->length === 0) {
        $report['errors'][] = 'No <book> elements found in the XML.';
        return $report;
    }

    // Prepared statements (reused in the loop)
    $checkStmt  = $conn->prepare("SELECT id FROM books WHERE title = ? AND author = ? LIMIT 1");
    $insertStmt = $conn->prepare(
        "INSERT INTO books (title, author, category, total_copies, copies_available)
         VALUES (?, ?, ?, ?, ?)"
    );

    foreach ($bookNodes as $node) {
        $title    = dom_value($node, 'title');
        $author   = dom_value($node, 'author');
        // Accept both "category" and old "genre" tag
        $category = dom_value($node, 'category') ?: dom_value($node, 'genre');
        // Accept both "total_copies"/"copies" and "copies_available"/"available"
        $total    = (int)(dom_value($node, 'total_copies') ?: dom_value($node, 'copies') ?: 1);
        $avail    = dom_value($node, 'copies_available');
        $avail    = $avail !== '' ? (int)$avail : (int)(dom_value($node, 'available') ?: $total);

        // Validate required fields
        if ($title === '' || $author === '' || $category === '') {
            $report['skipped']++;
            $report['errors'][] = 'Skipped a book with missing title/author/category.';
            continue;
        }

        // Skip duplicate by title+author
        $checkStmt->bind_param('ss', $title, $author);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            $report['skipped']++;
            continue;
        }

        if ($total < 1)  $total = 1;
        if ($avail < 0)  $avail = 0;
        if ($avail > $total) $avail = $total;

        $insertStmt->bind_param('sssii', $title, $author, $category, $total, $avail);
        if ($insertStmt->execute()) {
            $report['inserted']++;
        } else {
            $report['skipped']++;
            $report['errors'][] = 'DB insert failed for "' . $title . '".';
        }
    }

    $checkStmt->close();
    $insertStmt->close();

    return $report;
}

// ── Helper: read a child tag's text from a DOM node ──────────
function dom_value(DOMElement $parent, string $tag): string
{
    $nodes = $parent->getElementsByTagName($tag);
    if ($nodes->length === 0) return '';
    return trim($nodes->item(0)->nodeValue);
}
<?php
// ============================================================
// admin/library_export.php — XML Export & Import (Admin)
// ============================================================
session_start();
require_once __DIR__ . '/library_data.php';
require_once __DIR__ . '/../includes/books_dom.php';

$pending_count = pending_request_count();

$db   = new Database();
$conn = $db->getConnection();

$message = null;
$error   = null;
$report  = null;

// ── Handle EXPORT (download) ─────────────────────────────────
if (isset($_GET['export'])) {
    $type = $_GET['export'];

    if ($type === 'books') {
        $xml      = export_books_to_xml($conn);
        $filename = 'books_export_' . date('Y-m-d') . '.xml';
    } elseif ($type === 'students') {
        $xml      = export_students_to_xml($conn);
        $filename = 'students_export_' . date('Y-m-d') . '.xml';
    } else {
        header('Location: library_export.php');
        exit;
    }

    // Force download
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($xml));
    echo $xml;
    exit;
}

// ── Handle IMPORT (upload) ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml_file'])) {
    $file = $_FILES['xml_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed. Please try again.';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xml') {
            $error = 'Please upload a valid .xml file.';
        } else {
            $report = import_books_from_xml($conn, $file['tmp_name']);
            if ($report['inserted'] > 0) {
                $message = $report['inserted'] . ' book(s) imported successfully.'
                    . ($report['skipped'] > 0 ? ' ' . $report['skipped'] . ' skipped (duplicates/invalid).' : '');
            } elseif (empty($report['errors'])) {
                $error = 'No new books imported. They may already exist.';
            } else {
                $error = 'Import completed with issues. See details below.';
            }
        }
    }
}

// Counts for preview
$book_total    = total_book_count();
$student_total = total_students();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>XML Export / Import — CvSU Library</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
</head>
<body>

<?php include __DIR__ . '/sideBar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">
    <span class="topbar-title">XML Export / Import</span>
    <div class="topbar-spacer"></div>
    <a href="student_req.php" class="topbar-icon-btn">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <?php if ($pending_count > 0): ?><span class="topbar-notif-dot"></span><?php endif; ?>
    </a>
    <a href="admin_profile.php" class="topbar-icon-btn">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    </a>
  </header>

  <main class="page-content">

    <div class="page-header">
      <h1>XML Export &amp; Import</h1>
      <p>Export library records to XML, or import books from an XML file (DOM).</p>
      <div class="gold-rule"><span></span><i>*</i><span></span></div>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-sage" style="margin-bottom:1rem;"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-rust" style="margin-bottom:1rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">

      <!-- ── EXPORT ──────────────────────────────────────── -->
      <div class="card">
        <div class="card-body">
          <div class="card-title">📤 Export to XML</div>
          <p class="card-subtitle">Download current database records as an XML file.</p>

          <div style="display:flex;flex-direction:column;gap:12px;margin-top:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:rgba(201,151,58,0.06);border:1px solid var(--border);border-radius:10px;">
              <div>
                <div style="font-weight:600;font-size:0.9rem;">All Books</div>
                <div style="font-size:0.78rem;color:var(--text-muted);"><?= $book_total ?> records</div>
              </div>
              <a href="library_export.php?export=books" class="btn-primary" style="font-size:0.82rem;">Export Books</a>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:rgba(201,151,58,0.06);border:1px solid var(--border);border-radius:10px;">
              <div>
                <div style="font-weight:600;font-size:0.9rem;">All Students</div>
                <div style="font-size:0.78rem;color:var(--text-muted);"><?= $student_total ?> records</div>
              </div>
              <a href="library_export.php?export=students" class="btn-outline" style="font-size:0.82rem;">Export Students</a>
            </div>
          </div>
        </div>
      </div>

      <!-- ── IMPORT ──────────────────────────────────────── -->
      <div class="card">
        <div class="card-body">
          <div class="card-title">📥 Import Books from XML</div>
          <p class="card-subtitle">Upload an XML file to add books into the database.</p>

          <form method="POST" enctype="multipart/form-data" style="margin-top:14px;">
            <div class="field">
              <label>Select XML File</label>
              <div class="input-wrap">
                <input class="no-icon" type="file" name="xml_file" accept=".xml" required>
              </div>
            </div>
            <button type="submit" class="btn-primary">Upload &amp; Import</button>
          </form>

          <details style="margin-top:16px;">
            <summary style="cursor:pointer;font-size:0.82rem;color:var(--gold);">Expected XML format</summary>
            <pre style="background:rgba(0,0,0,0.04);padding:12px;border-radius:8px;font-size:0.74rem;overflow:auto;margin-top:8px;">&lt;books&gt;
  &lt;book&gt;
    &lt;title&gt;Sample Book&lt;/title&gt;
    &lt;author&gt;John Doe&lt;/author&gt;
    &lt;category&gt;Fiction&lt;/category&gt;
    &lt;total_copies&gt;3&lt;/total_copies&gt;
    &lt;copies_available&gt;3&lt;/copies_available&gt;
  &lt;/book&gt;
&lt;/books&gt;</pre>
          </details>
        </div>
      </div>
    </div>

    <!-- ── Import Report ─────────────────────────────────── -->
    <?php if ($report): ?>
      <div class="card" style="margin-top:1.5rem;">
        <div class="card-body">
          <div class="card-title">Import Report</div>
          <div style="display:flex;gap:24px;margin:10px 0;">
            <div><span style="font-size:1.4rem;font-weight:700;color:#588157;"><?= $report['inserted'] ?></span>
              <div style="font-size:0.78rem;color:var(--text-muted);">Inserted</div></div>
            <div><span style="font-size:1.4rem;font-weight:700;color:#c0392b;"><?= $report['skipped'] ?></span>
              <div style="font-size:0.78rem;color:var(--text-muted);">Skipped</div></div>
          </div>
          <?php if (!empty($report['errors'])): ?>
            <details>
              <summary style="cursor:pointer;font-size:0.82rem;color:var(--text-muted);">View details (<?= count($report['errors']) ?>)</summary>
              <ul style="font-size:0.8rem;color:var(--text-muted);margin-top:8px;padding-left:18px;">
                <?php foreach (array_slice($report['errors'], 0, 20) as $e): ?>
                  <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
              </ul>
            </details>
          <?php endif; ?>
          <a href="view_books.php" class="btn-outline" style="margin-top:12px;font-size:0.82rem;">View Books →</a>
        </div>
      </div>
    <?php endif; ?>

  </main>
</div>

</body>
</html>
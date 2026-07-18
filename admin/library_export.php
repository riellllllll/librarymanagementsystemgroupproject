<?php
// ============================================================
// admin/library_export.php - XML and Excel Export & Import (Admin)
// ============================================================
session_start();
require_once __DIR__ . '/library_data.php';
require_once __DIR__ . '/../includes/books_dom.php';
require_once __DIR__ . '/../includes/excel_import_export.php';

$pending_count = pending_request_count();

$db = new Database();
$conn = $db->getConnection();

$message = null;
$error = null;
$report = null;
$report_type = null;

// Handle EXPORT (download)
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $format = strtolower($_GET['format'] ?? 'xml');

    if (!in_array($type, ['books', 'students'], true) || !in_array($format, ['xml', 'xlsx'], true)) {
        header('Location: library_export.php');
        exit;
    }

    if ($format === 'xlsx') {
        $content = $type === 'books' ? export_books_to_excel($conn) : export_students_to_excel($conn);
        $filename = $type . '_export_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    } else {
        $content = $type === 'books' ? export_books_to_xml($conn) : export_students_to_xml($conn);
        $filename = $type . '_export_' . date('Y-m-d') . '.xml';
        header('Content-Type: application/xml; charset=utf-8');
    }

    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}

// Handle IMPORT (upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    $import_type = $_POST['import_type'] ?? 'books';
    $import_format = strtolower($_POST['import_format'] ?? 'xml');
    $report_type = $import_type;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed. Please try again.';
    } elseif (!in_array($import_type, ['books', 'students'], true) || !in_array($import_format, ['xml', 'xlsx'], true)) {
        $error = 'Please choose a valid import type and file format.';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== $import_format) {
            $error = 'Please upload a valid .' . $import_format . ' file.';
        } elseif ($import_format === 'xlsx' && $import_type === 'students') {
            $report = import_students_from_excel($conn, $file['tmp_name']);
        } elseif ($import_format === 'xlsx') {
            $report = import_books_from_excel($conn, $file['tmp_name']);
        } elseif ($import_type === 'students') {
            $report = import_students_from_xml($conn, $file['tmp_name']);
        } else {
            $report = import_books_from_xml($conn, $file['tmp_name']);
        }

        if ($report) {
            $label = $import_type === 'students' ? 'student' : 'book';
            if ($report['inserted'] > 0) {
                $message = $report['inserted'] . ' ' . $label . '(s) imported successfully.'
                    . ($report['skipped'] > 0 ? ' ' . $report['skipped'] . ' skipped (duplicates/invalid).' : '');
            } elseif (empty($report['errors'])) {
                $error = 'No new ' . $label . 's imported. They may already exist.';
            } else {
                $error = 'Import completed with issues. See details below.';
            }
        }
    }
}

$book_total = total_book_count();
$student_total = total_students();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Library Export / Import - CvSU Library</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student.css">
  <link rel="stylesheet" href="../assets/adminStyle.css">
</head>
<body>

<?php include __DIR__ . '/sideBar.php'; ?>

<div class="main-wrapper">

  <header class="topbar">
    <span class="topbar-title">Library Export / Import</span>
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
      <h1>Library Export &amp; Import</h1>
      <p>Export and import books or students using XML or Excel files.</p>
      <div class="gold-rule"><span></span><i>*</i><span></span></div>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-sage" style="margin-bottom:1rem;"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-rust" style="margin-bottom:1rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">

      <div class="card">
        <div class="card-body">
          <div class="card-title">Export Records</div>
          <p class="card-subtitle">Download the current database records as XML or Excel.</p>

          <div style="display:flex;flex-direction:column;gap:12px;margin-top:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 14px;background:rgba(201,151,58,0.06);border:1px solid var(--border);border-radius:10px;">
              <div>
                <div style="font-weight:600;font-size:0.9rem;">All Books</div>
                <div style="font-size:0.78rem;color:var(--text-muted);"><?= $book_total ?> records</div>
              </div>
              <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                <a href="library_export.php?export=books&amp;format=xlsx" class="btn-primary" style="font-size:0.82rem;">Excel</a>
                <a href="library_export.php?export=books&amp;format=xml" class="btn-outline" style="font-size:0.82rem;">XML</a>
              </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 14px;background:rgba(201,151,58,0.06);border:1px solid var(--border);border-radius:10px;">
              <div>
                <div style="font-weight:600;font-size:0.9rem;">All Students</div>
                <div style="font-size:0.78rem;color:var(--text-muted);"><?= $student_total ?> records</div>
              </div>
              <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                <a href="library_export.php?export=students&amp;format=xlsx" class="btn-primary" style="font-size:0.82rem;">Excel</a>
                <a href="library_export.php?export=students&amp;format=xml" class="btn-outline" style="font-size:0.82rem;">XML</a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="card-title">Import Records</div>
          <p class="card-subtitle">Upload an XML or Excel file to add books or students into the database.</p>

          <form method="POST" enctype="multipart/form-data" style="margin-top:14px;" id="importForm">
            <div class="field">
              <label>Import Type</label>
              <div class="input-wrap">
                <select class="no-icon" name="import_type" id="importType">
                  <option value="books">Books</option>
                  <option value="students">Students</option>
                </select>
              </div>
            </div>

            <div class="field">
              <label>File Format</label>
              <div class="input-wrap">
                <select class="no-icon" name="import_format" id="importFormat">
                  <option value="xlsx">Excel (.xlsx)</option>
                  <option value="xml">XML (.xml)</option>
                </select>
              </div>
            </div>

            <div class="field">
              <label>Select File</label>
              <div class="input-wrap">
                <input class="no-icon" type="file" name="import_file" id="importFile" accept=".xlsx,.xml" required>
              </div>
            </div>
            <button type="submit" class="btn-primary">Upload &amp; Import</button>
          </form>

          <details style="margin-top:16px;" id="booksExcelDetails" open>
            <summary style="cursor:pointer;font-size:0.82rem;color:var(--gold);">Expected Excel columns (Books)</summary>
            <pre style="background:rgba(0,0,0,0.04);padding:12px;border-radius:8px;font-size:0.74rem;overflow:auto;margin-top:8px;">id | title | author | category | total_copies | copies_available</pre>
          </details>

          <details style="margin-top:10px;" id="studentsExcelDetails">
            <summary style="cursor:pointer;font-size:0.82rem;color:var(--gold);">Expected Excel columns (Students)</summary>
            <pre style="background:rgba(0,0,0,0.04);padding:12px;border-radius:8px;font-size:0.74rem;overflow:auto;margin-top:8px;">id | student_number | name | email | course | year_level</pre>
            <p style="font-size:0.74rem;color:var(--text-muted);margin-top:8px;">
              Imported students get the default password <code>CvSU@2026</code>. You may also use <code>first_name</code> and <code>last_name</code> columns instead of <code>name</code>.
            </p>
          </details>

          <details style="margin-top:10px;" id="booksXmlDetails">
            <summary style="cursor:pointer;font-size:0.82rem;color:var(--gold);">Expected XML format (Books)</summary>
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

          <details style="margin-top:10px;" id="studentsXmlDetails">
            <summary style="cursor:pointer;font-size:0.82rem;color:var(--gold);">Expected XML format (Students)</summary>
            <pre style="background:rgba(0,0,0,0.04);padding:12px;border-radius:8px;font-size:0.74rem;overflow:auto;margin-top:8px;">&lt;students&gt;
  &lt;student&gt;
    &lt;student_number&gt;2024-00123&lt;/student_number&gt;
    &lt;name&gt;Juan Dela Cruz&lt;/name&gt;
    &lt;email&gt;juan.delacruz@cvsu.edu.ph&lt;/email&gt;
    &lt;course&gt;BS Computer Science&lt;/course&gt;
    &lt;year_level&gt;2nd Year&lt;/year_level&gt;
  &lt;/student&gt;
&lt;/students&gt;</pre>
          </details>
        </div>
      </div>
    </div>

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
          <a href="<?= $report_type === 'students' ? 'view_students.php' : 'view_books.php' ?>" class="btn-outline" style="margin-top:12px;font-size:0.82rem;">View <?= $report_type === 'students' ? 'Students' : 'Books' ?></a>
        </div>
      </div>
    <?php endif; ?>

  </main>
</div>

<script>
(function() {
  var typeSelect = document.getElementById('importType');
  var formatSelect = document.getElementById('importFormat');
  var fileInput = document.getElementById('importFile');
  var hints = {
    booksExcel: document.getElementById('booksExcelDetails'),
    studentsExcel: document.getElementById('studentsExcelDetails'),
    booksXml: document.getElementById('booksXmlDetails'),
    studentsXml: document.getElementById('studentsXmlDetails')
  };

  function syncHints() {
    var type = typeSelect.value;
    var format = formatSelect.value;
    Object.keys(hints).forEach(function(key) { hints[key].style.display = 'none'; });
    hints[type + (format === 'xlsx' ? 'Excel' : 'Xml')].style.display = '';
    fileInput.accept = format === 'xlsx' ? '.xlsx' : '.xml';
  }

  typeSelect.addEventListener('change', syncHints);
  formatSelect.addEventListener('change', syncHints);
  syncHints();
})();
</script>

</body>
</html>

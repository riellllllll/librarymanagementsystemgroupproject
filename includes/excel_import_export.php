<?php
// ============================================================
// includes/excel_import_export.php - Excel Export & Import (.xlsx)
// ============================================================

require_once __DIR__ . '/../config/Database.php';

const BOOK_EXCEL_HEADERS = ['id', 'title', 'author', 'category', 'total_copies', 'copies_available'];
const STUDENT_EXCEL_HEADERS = ['id', 'student_number', 'name', 'email', 'course', 'year_level'];

function export_books_to_excel(mysqli $conn): string
{
    $rows = [];
    $result = $conn->query(
        "SELECT id, title, author, category, total_copies, copies_available
         FROM books
         ORDER BY id ASC"
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                $row['id'],
                $row['title'],
                $row['author'],
                $row['category'],
                $row['total_copies'],
                $row['copies_available'],
            ];
        }
    }

    return build_xlsx('Books', BOOK_EXCEL_HEADERS, $rows);
}

function export_students_to_excel(mysqli $conn): string
{
    $rows = [];
    $result = $conn->query(
        "SELECT id, student_number, full_name, email, course, year_level
         FROM users
         WHERE role = 'student'
         ORDER BY id ASC"
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                $row['id'],
                $row['student_number'],
                $row['full_name'],
                $row['email'],
                $row['course'] ?? '',
                $row['year_level'] ?? '',
            ];
        }
    }

    return build_xlsx('Students', STUDENT_EXCEL_HEADERS, $rows);
}

function import_books_from_excel(mysqli $conn, string $xlsxFilePath): array
{
    $report = ['inserted' => 0, 'skipped' => 0, 'errors' => []];
    $rows = read_xlsx_rows($xlsxFilePath, $report);
    if (empty($rows)) return $report;

    $headers = normalize_excel_headers(array_shift($rows));
    $required = ['title', 'author', 'category'];
    foreach ($required as $header) {
        if (!in_array($header, $headers, true)) {
            $report['errors'][] = 'Missing required Excel column: ' . $header;
            return $report;
        }
    }

    $checkStmt = $conn->prepare("SELECT id FROM books WHERE title = ? AND author = ? LIMIT 1");
    $insertStmt = $conn->prepare(
        "INSERT INTO books (title, author, category, total_copies, copies_available)
         VALUES (?, ?, ?, ?, ?)"
    );

    foreach ($rows as $rowNumber => $row) {
        $data = row_to_assoc($headers, $row);
        $title = trim($data['title'] ?? '');
        $author = trim($data['author'] ?? '');
        $category = trim($data['category'] ?? ($data['genre'] ?? ''));
        $total = (int)($data['total_copies'] ?? ($data['copies'] ?? 1));
        $availRaw = trim((string)($data['copies_available'] ?? ($data['available'] ?? '')));
        $avail = $availRaw !== '' ? (int)$availRaw : $total;

        if ($title === '' || $author === '' || $category === '') {
            $report['skipped']++;
            $report['errors'][] = 'Row ' . ($rowNumber + 2) . ': missing title/author/category.';
            continue;
        }

        $checkStmt->bind_param('ss', $title, $author);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            $report['skipped']++;
            continue;
        }

        if ($total < 1) $total = 1;
        if ($avail < 0) $avail = 0;
        if ($avail > $total) $avail = $total;

        $insertStmt->bind_param('sssii', $title, $author, $category, $total, $avail);
        if ($insertStmt->execute()) {
            $report['inserted']++;
        } else {
            $report['skipped']++;
            $report['errors'][] = 'Row ' . ($rowNumber + 2) . ': database insert failed for "' . $title . '".';
        }
    }

    $checkStmt->close();
    $insertStmt->close();
    return $report;
}

function import_students_from_excel(mysqli $conn, string $xlsxFilePath): array
{
    require_once __DIR__ . '/../classes/User.php';

    $report = ['inserted' => 0, 'skipped' => 0, 'errors' => []];
    $rows = read_xlsx_rows($xlsxFilePath, $report);
    if (empty($rows)) return $report;

    $headers = normalize_excel_headers(array_shift($rows));
    foreach (['student_number', 'email'] as $header) {
        if (!in_array($header, $headers, true)) {
            $report['errors'][] = 'Missing required Excel column: ' . $header;
            return $report;
        }
    }

    $usr = new User($conn);
    $default_password = 'CvSU@2026';

    foreach ($rows as $rowNumber => $row) {
        $data = row_to_assoc($headers, $row);
        $student_number = trim($data['student_number'] ?? ($data['id_number'] ?? ''));
        $email = strtolower(trim($data['email'] ?? ''));
        $first_name = trim($data['first_name'] ?? '');
        $last_name = trim($data['last_name'] ?? '');

        if ($first_name === '' && $last_name === '') {
            $full = trim($data['name'] ?? ($data['full_name'] ?? ''));
            $parts = preg_split('/\s+/', $full, 2);
            $first_name = $parts[0] ?? '';
            $last_name = $parts[1] ?? '';
        }

        if ($student_number === '' || $email === '' || $first_name === '') {
            $report['skipped']++;
            $report['errors'][] = 'Row ' . ($rowNumber + 2) . ': missing student_number/email/name.';
            continue;
        }

        $student = [
            'student_number' => $student_number,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'course' => trim($data['course'] ?? ''),
            'year_level' => trim($data['year_level'] ?? ($data['year'] ?? '')),
            'password' => $default_password,
        ];

        if ($usr->addStudent($student)) {
            $report['inserted']++;
        } else {
            $report['skipped']++;
            $report['errors'][] = 'Row ' . ($rowNumber + 2) . ': duplicate student number or email.';
        }
    }

    if ($report['inserted'] > 0) {
        $report['errors'][] = 'Imported students were given the default password "' . $default_password . '".';
    }

    return $report;
}

function build_xlsx(string $sheetName, array $headers, array $rows): string
{
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);

    $sheetRows = array_merge([$headers], $rows);
    $zip->addFromString('[Content_Types].xml', xlsx_content_types());
    $zip->addFromString('_rels/.rels', xlsx_root_rels());
    $zip->addFromString('xl/workbook.xml', xlsx_workbook_xml($sheetName));
    $zip->addFromString('xl/_rels/workbook.xml.rels', xlsx_workbook_rels());
    $zip->addFromString('xl/styles.xml', xlsx_styles_xml());
    $zip->addFromString('xl/worksheets/sheet1.xml', xlsx_sheet_xml($sheetRows));
    $zip->close();

    $xlsx = file_get_contents($tmp);
    @unlink($tmp);
    return $xlsx ?: '';
}

function xlsx_sheet_xml(array $rows): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $xml .= '<sheetViews><sheetView workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="15"/>';
    $xml .= '<cols>';
    $widths = [14, 26, 24, 18, 16, 18];
    foreach ($widths as $i => $width) {
        $col = $i + 1;
        $xml .= '<col min="' . $col . '" max="' . $col . '" width="' . $width . '" customWidth="1"/>';
    }
    $xml .= '</cols><sheetData>';

    foreach ($rows as $rIndex => $row) {
        $rowNum = $rIndex + 1;
        $style = $rowNum === 1 ? ' s="1"' : '';
        $xml .= '<row r="' . $rowNum . '">';
        foreach ($row as $cIndex => $value) {
            $cell = xlsx_col_name($cIndex + 1) . $rowNum;
            if (is_numeric($value) && $rowNum > 1) {
                $xml .= '<c r="' . $cell . '"><v>' . htmlspecialchars((string)$value, ENT_XML1) . '</v></c>';
            } else {
                $xml .= '<c r="' . $cell . '" t="inlineStr"' . $style . '><is><t>' . htmlspecialchars((string)$value, ENT_XML1) . '</t></is></c>';
            }
        }
        $xml .= '</row>';
    }

    $xml .= '</sheetData><autoFilter ref="A1:' . xlsx_col_name(count($rows[0] ?? [])) . max(1, count($rows)) . '"/>';
    $xml .= '</worksheet>';
    return $xml;
}

function read_xlsx_rows(string $xlsxFilePath, array &$report): array
{
    if (!file_exists($xlsxFilePath)) {
        $report['errors'][] = 'Excel file not found.';
        return [];
    }

    $zip = new ZipArchive();
    if ($zip->open($xlsxFilePath) !== true) {
        $report['errors'][] = 'Could not open Excel file. Please upload a valid .xlsx file.';
        return [];
    }

    $sharedStrings = read_xlsx_shared_strings($zip);
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if ($sheetXml === false) {
        $report['errors'][] = 'The Excel file has no first worksheet.';
        return [];
    }

    $sheet = simplexml_load_string($sheetXml);
    if (!$sheet) {
        $report['errors'][] = 'Could not read the Excel worksheet.';
        return [];
    }

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $cell) {
            $ref = (string)$cell['r'];
            $colIndex = xlsx_col_index(preg_replace('/\d+/', '', $ref));
            $type = (string)$cell['t'];
            $value = '';

            if ($type === 's') {
                $idx = (int)$cell->v;
                $value = $sharedStrings[$idx] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = (string)$cell->is->t;
            } else {
                $value = (string)$cell->v;
            }

            $cells[$colIndex - 1] = trim($value);
        }

        if (!empty(array_filter($cells, fn($v) => $v !== ''))) {
            $maxIndex = max(array_keys($cells));
            $ordered = [];
            for ($i = 0; $i <= $maxIndex; $i++) {
                $ordered[] = $cells[$i] ?? '';
            }
            $rows[] = $ordered;
        }
    }

    if (count($rows) < 2) {
        $report['errors'][] = 'Excel file must include a header row and at least one data row.';
    }

    return $rows;
}

function read_xlsx_shared_strings(ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false) return [];

    $strings = [];
    $shared = simplexml_load_string($xml);
    if (!$shared) return [];

    foreach ($shared->si as $si) {
        if (isset($si->t)) {
            $strings[] = (string)$si->t;
        } else {
            $text = '';
            foreach ($si->r as $run) {
                $text .= (string)$run->t;
            }
            $strings[] = $text;
        }
    }

    return $strings;
}

function normalize_excel_headers(array $headers): array
{
    return array_map(function ($header) {
        $header = strtolower(trim((string)$header));
        $header = str_replace([' ', '-'], '_', $header);
        return preg_replace('/[^a-z0-9_]/', '', $header);
    }, $headers);
}

function row_to_assoc(array $headers, array $row): array
{
    $assoc = [];
    foreach ($headers as $i => $header) {
        if ($header !== '') {
            $assoc[$header] = $row[$i] ?? '';
        }
    }
    return $assoc;
}

function xlsx_col_name(int $index): string
{
    $name = '';
    while ($index > 0) {
        $index--;
        $name = chr(65 + ($index % 26)) . $name;
        $index = intdiv($index, 26);
    }
    return $name;
}

function xlsx_col_index(string $name): int
{
    $index = 0;
    foreach (str_split(strtoupper($name)) as $char) {
        $index = ($index * 26) + (ord($char) - 64);
    }
    return $index;
}

function xlsx_content_types(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
}

function xlsx_root_rels(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
}

function xlsx_workbook_xml(string $sheetName): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="' . htmlspecialchars($sheetName, ENT_XML1) . '" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';
}

function xlsx_workbook_rels(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
}

function xlsx_styles_xml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>
  <fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>
  <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>
</styleSheet>';
}


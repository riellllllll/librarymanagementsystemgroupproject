<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!function_exists('format_book_id')) {
  function format_book_id($id) {
    return str_pad((string)(int)$id, 2, '0', STR_PAD_LEFT);
  }
}

if (!function_exists('format_student_number')) {
  function format_student_number($id) {
    return str_pad((string)(int)$id, 3, '0', STR_PAD_LEFT);
  }
}

require_once '../includes/books_dom.php';

$all_books = load_books_from_xml();

$default_borrow_requests = [
  [
    'id' => 1,
    'student' => 'Juan Dela Cruz',
    'student_id' => '101',
    'book_id' => '01',
    'book_title' => 'The Great Gatsby',
    'date' => 'May 21, 2026',
    'status' => 'pending'
  ],
  [
    'id' => 2,
    'student' => 'Maria Santos',
    'student_id' => '102',
    'book_id' => '05',
    'book_title' => 'Clean Code',
    'date' => 'May 21, 2026',
    'status' => 'pending'
  ]
];

if (!isset($_SESSION['books']) || !is_array($_SESSION['books'])) {
  $_SESSION['books'] = is_array($all_books) ? $all_books : [];
}

foreach ($_SESSION['books'] as $index => $book) {
  if (isset($book['id'])) {
    $_SESSION['books'][$index]['id'] = format_book_id($book['id']);
  }
}

if (!isset($_SESSION['archived_books']) || !is_array($_SESSION['archived_books'])) {
  $_SESSION['archived_books'] = [];
}

foreach ($_SESSION['archived_books'] as $index => $book) {
  if (isset($book['id'])) {
    $_SESSION['archived_books'][$index]['id'] = format_book_id($book['id']);
  }
}

if (!isset($_SESSION['borrow_requests']) || !is_array($_SESSION['borrow_requests'])) {
  $_SESSION['borrow_requests'] = $default_borrow_requests;
}

foreach ($_SESSION['borrow_requests'] as $index => $request) {
  if (isset($request['book_id'])) {
    $_SESSION['borrow_requests'][$index]['book_id'] = format_book_id($request['book_id']);
  }

  if (($request['student_id'] ?? '') === '2026-0001') {
    $_SESSION['borrow_requests'][$index]['student_id'] = '101';
  }

  if (($request['student_id'] ?? '') === '2026-0002') {
    $_SESSION['borrow_requests'][$index]['student_id'] = '102';
  }
}

if (!isset($_SESSION['borrowed_books']) || !is_array($_SESSION['borrowed_books'])) {
  $_SESSION['borrowed_books'] = [];
}

if (!isset($_SESSION['return_requests']) || !is_array($_SESSION['return_requests'])) {
  $_SESSION['return_requests'] = [];
}

foreach ($_SESSION['borrowed_books'] as $index => $borrowed_book) {
  if (isset($borrowed_book['book_id'])) {
    $_SESSION['borrowed_books'][$index]['book_id'] = format_book_id($borrowed_book['book_id']);
  }

  if (($borrowed_book['student_id'] ?? '') === '2026-0001') {
    $_SESSION['borrowed_books'][$index]['student_id'] = '101';
  }

  if (($borrowed_book['student_id'] ?? '') === '2026-0002') {
    $_SESSION['borrowed_books'][$index]['student_id'] = '102';
  }
}

if (!isset($_SESSION['pending_fines_total'])) {
  $_SESSION['pending_fines_total'] = 20;
}

if (!function_exists('pending_request_count')) {
  function pending_request_count() {
    $count = 0;

    foreach ($_SESSION['borrow_requests'] as $req) {
      if (isset($req['status']) && $req['status'] === 'pending') {
        $count++;
      }
    }

    return $count;
  }
}

if (!function_exists('active_book_count')) {
  function active_book_count() {
    return count($_SESSION['books']);
  }
}

if (!function_exists('pending_fines_total')) {
  function pending_fines_total() {
    return isset($_SESSION['pending_fines_total']) ? (int)$_SESSION['pending_fines_total'] : 0;
  }
}

if (!function_exists('find_book_index')) {
  function find_book_index($book_id) {
    foreach ($_SESSION['books'] as $index => $book) {
      if (format_book_id($book['id']) === format_book_id($book_id)) {
        return $index;
      }
    }

    return null;
  }
}
?>

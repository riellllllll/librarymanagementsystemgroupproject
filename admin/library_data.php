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

$default_books = [
  ['id' => '01', 'title' => 'The Great Gatsby', 'author' => 'F. Scott Fitzgerald', 'genre' => 'Fiction', 'category' => 'Fiction', 'copies' => 3, 'available' => 2, 'color' => 'color-a'],
  ['id' => '02', 'title' => 'To Kill a Mockingbird', 'author' => 'Harper Lee', 'genre' => 'Fiction', 'category' => 'Fiction', 'copies' => 4, 'available' => 1, 'color' => 'color-b'],
  ['id' => '03', 'title' => 'A Brief History of Time', 'author' => 'Stephen Hawking', 'genre' => 'Science', 'category' => 'Science', 'copies' => 2, 'available' => 2, 'color' => 'color-c'],
  ['id' => '04', 'title' => 'Sapiens', 'author' => 'Yuval Noah Harari', 'genre' => 'History', 'category' => 'History', 'copies' => 3, 'available' => 0, 'color' => 'color-d'],
  ['id' => '05', 'title' => 'Clean Code', 'author' => 'Robert C. Martin', 'genre' => 'Technology', 'category' => 'Technology', 'copies' => 5, 'available' => 4, 'color' => 'color-e'],
  ['id' => '06', 'title' => '1984', 'author' => 'George Orwell', 'genre' => 'Fiction', 'category' => 'Fiction', 'copies' => 3, 'available' => 2, 'color' => 'color-a'],
  ['id' => '07', 'title' => 'The Selfish Gene', 'author' => 'Richard Dawkins', 'genre' => 'Science', 'category' => 'Science', 'copies' => 2, 'available' => 1, 'color' => 'color-b'],
  ['id' => '08', 'title' => 'Calculus Made Easy', 'author' => 'Silvanus P. Thompson', 'genre' => 'Mathematics', 'category' => 'Mathematics', 'copies' => 4, 'available' => 3, 'color' => 'color-c'],
  ['id' => '09', 'title' => 'Design Patterns', 'author' => 'GoF', 'genre' => 'Technology', 'category' => 'Technology', 'copies' => 3, 'available' => 3, 'color' => 'color-d'],
  ['id' => '10', 'title' => 'Noli Me Tangere', 'author' => 'Jose Rizal', 'genre' => 'Literature', 'category' => 'Literature', 'copies' => 6, 'available' => 5, 'color' => 'color-e'],
  ['id' => '11', 'title' => 'El Filibusterismo', 'author' => 'Jose Rizal', 'genre' => 'Literature', 'category' => 'Literature', 'copies' => 5, 'available' => 4, 'color' => 'color-a'],
  ['id' => '12', 'title' => 'Guns, Germs, and Steel', 'author' => 'Jared Diamond', 'genre' => 'History', 'category' => 'History', 'copies' => 2, 'available' => 2, 'color' => 'color-b'],
  ['id' => '13', 'title' => 'The Pragmatic Programmer', 'author' => 'Andrew Hunt', 'genre' => 'Technology', 'category' => 'Technology', 'copies' => 3, 'available' => 2, 'color' => 'color-c'],
  ['id' => '14', 'title' => 'Pride and Prejudice', 'author' => 'Jane Austen', 'genre' => 'Literature', 'category' => 'Literature', 'copies' => 4, 'available' => 3, 'color' => 'color-d'],
  ['id' => '15', 'title' => 'Cosmos', 'author' => 'Carl Sagan', 'genre' => 'Science', 'category' => 'Science', 'copies' => 3, 'available' => 1, 'color' => 'color-e'],
  ['id' => '16', 'title' => 'The Art of War', 'author' => 'Sun Tzu', 'genre' => 'History', 'category' => 'History', 'copies' => 4, 'available' => 4, 'color' => 'color-a'],
];

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
  $_SESSION['books'] = $default_books;
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
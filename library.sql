
CREATE DATABASE library_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE library_db;

CREATE TABLE users (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  role            ENUM('admin','student') NOT NULL DEFAULT 'student',
  username        VARCHAR(50)     DEFAULT NULL,
  student_number  VARCHAR(12)     DEFAULT NULL,
  first_name      VARCHAR(60)     NOT NULL,
  last_name       VARCHAR(60)     NOT NULL,
  middle_name     VARCHAR(60)     DEFAULT NULL,
  full_name       VARCHAR(180)    NOT NULL,
  email           VARCHAR(120)    NOT NULL,
  password        VARCHAR(255)    NOT NULL,
  dob             DATE            DEFAULT NULL,
  gender          ENUM('Male','Female','Prefer not to say') DEFAULT NULL,
  course          VARCHAR(80)     DEFAULT NULL,
  year_level      VARCHAR(20)     DEFAULT NULL,
  phone           VARCHAR(20)     DEFAULT NULL,
  status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT uq_users_email   UNIQUE KEY  (email),
  CONSTRAINT uq_users_sno     UNIQUE KEY  (student_number),
  CONSTRAINT uq_users_uname   UNIQUE KEY  (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE books (
  id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  title            VARCHAR(200)  NOT NULL,
  author           VARCHAR(150)  NOT NULL,
  category         VARCHAR(80)   NOT NULL,
  total_copies     INT UNSIGNED  NOT NULL DEFAULT 1,
  copies_available INT UNSIGNED  NOT NULL DEFAULT 1,
  is_archived      TINYINT(1)    NOT NULL DEFAULT 0,
  added_by         INT UNSIGNED  DEFAULT NULL,
  created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_books_added  FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE borrow_records (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED  NOT NULL,
  book_id     INT UNSIGNED  NOT NULL,
  issued_by   INT UNSIGNED  DEFAULT NULL,
  borrow_date DATE          NOT NULL,
  due_date    DATE          NOT NULL,
  return_date DATE          DEFAULT NULL,
  status      ENUM('active','pending_return','returned','overdue')
              NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_borrow_user   FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_borrow_book   FOREIGN KEY (book_id)   REFERENCES books(id) ON DELETE CASCADE,
  CONSTRAINT fk_borrow_issued FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fines (
  id          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  borrow_id   INT UNSIGNED   NOT NULL,
  user_id     INT UNSIGNED   NOT NULL,
  amount      DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  paid_status ENUM('unpaid','payment_requested','paid') NOT NULL DEFAULT 'unpaid',
  paid_date   DATE           DEFAULT NULL,
  payment_method        VARCHAR(40) DEFAULT NULL,
  payment_submitted_at  DATETIME    DEFAULT NULL,
  created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_fines_borrow FOREIGN KEY (borrow_id) REFERENCES borrow_records(id) ON DELETE CASCADE,
  CONSTRAINT fk_fines_user   FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE book_requests (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id            INT UNSIGNED NOT NULL,
  book_id            INT UNSIGNED NOT NULL,
  processed_by       INT UNSIGNED DEFAULT NULL,
  status             ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  request_date       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  requested_borrow_date DATE      DEFAULT NULL,
  requested_due_date    DATE      DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_req_user      FOREIGN KEY (user_id)      REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_req_book      FOREIGN KEY (book_id)      REFERENCES books(id) ON DELETE CASCADE,
  CONSTRAINT fk_req_processed FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  type        ENUM('approved','rejected','returned','fine','info') NOT NULL DEFAULT 'info',
  title       VARCHAR(120) NOT NULL,
  message     VARCHAR(255) NOT NULL,
  is_read     TINYINT(1)   NOT NULL DEFAULT 0,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO books (title, author, category, total_copies, copies_available) VALUES
('The Great Gatsby',          'F. Scott Fitzgerald', 'Fiction',     3, 3),
('To Kill a Mockingbird',     'Harper Lee',          'Fiction',     4, 4),
('1984',                      'George Orwell',       'Fiction',     3, 3),
('A Brief History of Time',   'Stephen Hawking',     'Science',     2, 2),
('The Selfish Gene',          'Richard Dawkins',     'Science',     2, 2),
('Cosmos',                    'Carl Sagan',          'Science',     3, 3),
('Sapiens',                   'Yuval Noah Harari',   'History',     3, 3),
('Guns, Germs, and Steel',    'Jared Diamond',       'History',     2, 2),
('The Art of War',            'Sun Tzu',             'History',     4, 4),
('Clean Code',                'Robert C. Martin',    'Technology',  2, 2),
('Design Patterns',           'Gang of Four',        'Technology',  3, 3),
('The Pragmatic Programmer',  'David Thomas',        'Technology',  2, 2),
('Noli Me Tangere',           'Jose Rizal',          'Literature',  6, 6),
('El Filibusterismo',         'Jose Rizal',          'Literature',  5, 5),
('Pride and Prejudice',       'Jane Austen',         'Literature',  4, 4),
('Calculus Made Easy',        'Silvanus P. Thompson','Mathematics', 4, 4),
('A Mathematician''s Apology','G. H. Hardy',         'Mathematics', 2, 2),
('Principia Mathematica',     'Isaac Newton',        'Mathematics', 1, 1);


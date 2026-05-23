<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Books</title>
    <link rel="stylesheet" href="../assets/admin.css">
</head>

<body>

<div class="container">

    <?php include "sidebar.php"; ?>

    <main class="main-content">

        <div class="header">
            <h1>View Books</h1>
        </div>

        <div class="panel">

            <div class="panel-header">
                <h2>All Books</h2>
            </div>

          </div>

        <?php endforeach; ?>

      </div>

      <?php if ($total_pages > 1): ?>

        <div class="pagination">

          <?php for ($i = 1; $i <= $total_pages; $i++): ?>

            <a
              href="?genre=<?= urlencode($selected_genre) ?>&q=<?= urlencode($_GET['q'] ?? '') ?>&page=<?= $i ?>"
              class="page-btn <?= $i === $current_page_num ? 'active' : '' ?>"
            >
              <?= $i ?>
            </a>

          <?php endfor; ?>

        </div>

      <?php endif; ?>

    <?php endif; ?>

  </main>

</div>

</body>
</html>
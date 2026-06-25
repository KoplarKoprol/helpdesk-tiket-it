<!-- views/layout/header.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk Tiket IT</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/style.css">
</head>
<body>
<header class="navbar">
    <div class="navbar-brand">Helpdesk Tiket IT</div>
    <?php if (isset($_SESSION['user_id'])): ?>
        <nav class="navbar-nav">
            <span>Halo, <?= htmlspecialchars($_SESSION['nama']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)</span>
            <a href="<?= BASE_URL ?>index.php?page=logout">Logout</a>
        </nav>
    <?php endif; ?>
</header>
<main class="container">

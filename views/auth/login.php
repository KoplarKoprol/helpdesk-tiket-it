<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="form-card">
    <h2>Login</h2>

    <?php if (!empty($error)): ?>
        <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
        <p class="alert alert-success">Registrasi berhasil. Silakan login.</p>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>index.php?page=do_login" method="POST">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="btn btn-primary">Login</button>
    </form>

    <p>Belum punya akun? <a href="<?= BASE_URL ?>index.php?page=register">Daftar di sini</a></p>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

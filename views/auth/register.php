<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="form-card">
    <h2>Daftar Akun</h2>

    <?php if (!empty($error)): ?>
        <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>index.php?page=do_register" method="POST">
        <label for="nama">Nama Lengkap</label>
        <input type="text" id="nama" name="nama" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" minlength="8" required>
        <small>Minimal 8 karakter</small>

        <button type="submit" class="btn btn-primary">Daftar</button>
    </form>

    <p>Sudah punya akun? <a href="<?= BASE_URL ?>index.php?page=login">Login di sini</a></p>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

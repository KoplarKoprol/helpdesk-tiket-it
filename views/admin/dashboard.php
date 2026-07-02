<?php require __DIR__ . '/../layout/header.php'; ?>

<h2>Dashboard Admin</h2>

<div class="stats-grid">
    <?php foreach ($statusCounts as $row): ?>
        <div class="stat-card">
            <div class="stat-number"><?= htmlspecialchars($row['jumlah']) ?></div>
            <div class="stat-label"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $row['status']))) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<p><a href="<?= BASE_URL ?>index.php?page=admin_all_tickets" class="btn btn-primary">Lihat Semua Tiket</a></p>

<!-- TODO: tambahkan tombol export laporan PDF/Excel di sini -->

<?php require __DIR__ . '/../layout/footer.php'; ?>

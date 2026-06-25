<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="page-header">
    <h2>Tiket Saya</h2>
    <a href="<?= BASE_URL ?>index.php?page=create_ticket" class="btn btn-primary">+ Buat Tiket Baru</a>
</div>

<?php if (isset($_GET['created'])): ?>
    <p class="alert alert-success">Tiket berhasil dibuat.</p>
<?php endif; ?>

<table class="table">
    <thead>
        <tr>
            <th>Judul</th>
            <th>Kategori</th>
            <th>Status</th>
            <th>Dibuat</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($tickets)): ?>
            <tr><td colspan="5">Belum ada tiket.</td></tr>
        <?php else: ?>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['judul']) ?></td>
                    <td><?= htmlspecialchars($t['kategori_nama'] ?? '-') ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($t['status']) ?>"><?= htmlspecialchars($t['status']) ?></span></td>
                    <td><?= htmlspecialchars($t['created_at']) ?></td>
                    <td><a href="<?= BASE_URL ?>index.php?page=ticket_detail&id=<?= $t['id'] ?>">Lihat</a></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>

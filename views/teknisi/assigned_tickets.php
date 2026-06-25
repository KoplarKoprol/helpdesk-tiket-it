<?php require __DIR__ . '/../layout/header.php'; ?>

<h2>Tiket yang Ditugaskan ke Saya</h2>

<table class="table">
    <thead>
        <tr>
            <th>Judul</th>
            <th>Kategori</th>
            <th>Prioritas</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($tickets)): ?>
            <tr><td colspan="5">Belum ada tiket yang ditugaskan.</td></tr>
        <?php else: ?>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['judul']) ?></td>
                    <td><?= htmlspecialchars($t['kategori_nama'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($t['prioritas']) ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($t['status']) ?>"><?= htmlspecialchars($t['status']) ?></span></td>
                    <td><a href="<?= BASE_URL ?>index.php?page=ticket_detail&id=<?= $t['id'] ?>">Kelola</a></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>

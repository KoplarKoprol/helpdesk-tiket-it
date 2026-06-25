<?php require __DIR__ . '/../layout/header.php'; ?>

<h2>Semua Tiket</h2>

<form method="GET" action="<?= BASE_URL ?>index.php" class="filter-form">
    <input type="hidden" name="page" value="admin_all_tickets">
    <select name="status">
        <option value="">Semua Status</option>
        <option value="open">Open</option>
        <option value="in_progress">In Progress</option>
        <option value="resolved">Resolved</option>
        <option value="closed">Closed</option>
    </select>
    <select name="prioritas">
        <option value="">Semua Prioritas</option>
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
</form>

<table class="table">
    <thead>
        <tr>
            <th>Judul</th>
            <th>Pembuat</th>
            <th>Teknisi</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($tickets)): ?>
            <tr><td colspan="5">Tidak ada tiket.</td></tr>
        <?php else: ?>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['judul']) ?></td>
                    <td><?= htmlspecialchars($t['pembuat_nama'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($t['teknisi_nama'] ?? 'Belum ditugaskan') ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($t['status']) ?>"><?= htmlspecialchars($t['status']) ?></span></td>
                    <td>
                        <a href="<?= BASE_URL ?>index.php?page=ticket_detail&id=<?= $t['id'] ?>">Lihat</a>
                        <?php if (!$t['teknisi_id']): ?>
                            |
                            <form action="<?= BASE_URL ?>index.php?page=assign_teknisi" method="POST" style="display:inline">
                                <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                <select name="teknisi_id" required>
                                    <option value="">Pilih Teknisi</option>
                                    <!-- TODO: looping data teknisi dari User::getAllTeknisi() -->
                                </select>
                                <button type="submit" class="btn-link">Assign</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>

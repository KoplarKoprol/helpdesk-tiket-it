<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="detail-card">
    <h2><?= htmlspecialchars($ticket['judul']) ?></h2>
    <p class="meta">
        Kategori: <?= htmlspecialchars($ticket['kategori_nama'] ?? '-') ?> |
        Prioritas: <?= htmlspecialchars($ticket['prioritas']) ?> |
        Status: <span class="badge badge-<?= htmlspecialchars($ticket['status']) ?>"><?= htmlspecialchars($ticket['status']) ?></span>
    </p>
    <p class="meta">
        Dibuat oleh: <?= htmlspecialchars($ticket['pembuat_nama'] ?? '-') ?> |
        Teknisi: <?= htmlspecialchars($ticket['teknisi_nama'] ?? 'Belum ditugaskan') ?>
    </p>

    <h3>Deskripsi</h3>
    <p><?= nl2br(htmlspecialchars($ticket['deskripsi'])) ?></p>

    <?php if (in_array($_SESSION['role'], ['teknisi', 'admin'])): ?>
        <h3>Update Status</h3>
        <form action="<?= BASE_URL ?>index.php?page=update_ticket_status" method="POST">
            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
            <select name="status">
                <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                <option value="reopened" <?= $ticket['status'] === 'reopened' ? 'selected' : '' ?>>Reopened</option>
            </select>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    <?php endif; ?>

    <!-- TODO: form tambah komentar dan upload lampiran -->
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

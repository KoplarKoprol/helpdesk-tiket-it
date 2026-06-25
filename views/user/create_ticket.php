<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="form-card">
    <h2>Buat Tiket Baru</h2>

    <?php if (!empty($error)): ?>
        <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>index.php?page=store_ticket" method="POST">
        <label for="judul">Judul Masalah</label>
        <input type="text" id="judul" name="judul" required>

        <label for="kategori_id">Kategori</label>
        <select id="kategori_id" name="kategori_id" required>
            <option value="">-- Pilih Kategori --</option>
            <!-- TODO: ambil dari tabel kategori secara dinamis -->
            <option value="1">Hardware</option>
            <option value="2">Software</option>
            <option value="3">Jaringan</option>
        </select>

        <label for="prioritas">Prioritas</label>
        <select id="prioritas" name="prioritas" required>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
        </select>

        <label for="deskripsi">Deskripsi Masalah</label>
        <textarea id="deskripsi" name="deskripsi" rows="5" required></textarea>

        <button type="submit" class="btn btn-primary">Kirim Tiket</button>
    </form>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

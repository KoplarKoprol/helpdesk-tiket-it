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
    <p class="ticket-description"><?= nl2br(htmlspecialchars($ticket['deskripsi'])) ?></p>

    <?php if (in_array($_SESSION['role'], ['teknisi', 'admin'])): ?>
        <div class="status-update-section">
            <h3>Update Status</h3>
            <form action="<?= BASE_URL ?>index.php?page=update_ticket_status" method="POST" class="status-form">
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
        </div>
    <?php endif; ?>

    <hr class="section-divider">

    <!-- KOMENTAR & LAMPIRAN SECTION -->
    <div class="comments-section">
        <h3>Diskusi Tiket</h3>
        
        <!-- List Komentar -->
        <div id="commentsList" class="comments-list">
            <div class="comments-loading">Memuat diskusi...</div>
        </div>

        <!-- Form Tambah Komentar -->
        <div class="comment-form-container">
            <h4>Tambah Komentar</h4>
            <div id="commentError" class="alert alert-error" style="display: none;"></div>
            <form id="addCommentForm" enctype="multipart/form-data">
                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                
                <div class="form-group">
                    <textarea name="body" id="commentBody" rows="4" placeholder="Tulis tanggapan atau solusi di sini..." required></textarea>
                </div>

                <div class="form-group">
                    <label for="commentAttachments">Upload Lampiran (Opsional, Maksimal 5 file, maks 5MB per file)</label>
                    <input type="file" name="attachment[]" id="commentAttachments" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                    <small>Mendukung gambar (JPG, PNG, GIF, WebP), PDF, Word, Excel, TXT, ZIP</small>
                </div>

                <button type="submit" id="submitCommentBtn" class="btn btn-primary">Kirim Komentar</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ticketId = <?= $ticket['id'] ?>;
    const currentUserId = <?= $_SESSION['user_id'] ?>;
    const currentUserRole = '<?= $_SESSION['role'] ?>';
    const baseUrl = '<?= BASE_URL ?>';
    
    const commentsList = document.getElementById('commentsList');
    const addCommentForm = document.getElementById('addCommentForm');
    const commentError = document.getElementById('commentError');
    const commentBody = document.getElementById('commentBody');
    const commentAttachments = document.getElementById('commentAttachments');
    const submitCommentBtn = document.getElementById('submitCommentBtn');

    // Load comments initially
    fetchComments();

    // Submit new comment via AJAX
    addCommentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        commentError.style.display = 'none';
        commentError.textContent = '';
        
        const bodyText = commentBody.value.trim();
        if (bodyText === '') {
            showError('Komentar tidak boleh kosong.');
            return;
        }

        // Validasi jumlah file
        if (commentAttachments.files.length > 5) {
            showError('Maksimal 5 file lampiran per komentar.');
            return;
        }

        // Validasi ukuran file
        for (let i = 0; i < commentAttachments.files.length; i++) {
            if (commentAttachments.files[i].size > 5 * 1024 * 1024) {
                showError(`File "${commentAttachments.files[i].name}" melebihi batas 5 MB.`);
                return;
            }
        }

        submitCommentBtn.disabled = true;
        submitCommentBtn.textContent = 'Mengirim...';

        const formData = new FormData(addCommentForm);

        fetch(baseUrl + 'index.php?page=comment_store', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            submitCommentBtn.disabled = false;
            submitCommentBtn.textContent = 'Kirim Komentar';
            
            if (data.errors) {
                showError(data.errors.join('<br>'));
            } else if (data.error) {
                showError(data.error);
            } else {
                // Success! Clear form and reload comments
                commentBody.value = '';
                commentAttachments.value = '';
                fetchComments();
            }
        })
        .catch(err => {
            submitCommentBtn.disabled = false;
            submitCommentBtn.textContent = 'Kirim Komentar';
            showError('Terjadi kesalahan koneksi server.');
            console.error(err);
        });
    });

    function showError(message) {
        commentError.innerHTML = message;
        commentError.style.display = 'block';
        commentError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function fetchComments() {
        fetch(baseUrl + 'index.php?page=comments_list&ticket_id=' + ticketId)
            .then(res => res.json())
            .then(data => {
                const comments = data.comments || [];
                if (comments.length === 0) {
                    commentsList.innerHTML = '<div class="no-comments">Belum ada diskusi pada tiket ini.</div>';
                    return;
                }

                commentsList.innerHTML = '';
                comments.forEach(c => {
                    const card = document.createElement('div');
                    card.className = 'comment-card';
                    
                    const isOwner = parseInt(c.user_id) === currentUserId;
                    const isAdmin = currentUserRole === 'admin';
                    const showDelete = isOwner || isAdmin;
                    
                    // Format role label
                    let roleLabel = '';
                    let roleClass = 'role-user';
                    if (c.role_id == 3) {
                        roleLabel = 'Admin';
                        roleClass = 'role-admin';
                    } else if (c.role_id == 2) {
                        roleLabel = 'Teknisi';
                        roleClass = 'role-teknisi';
                    } else {
                        roleLabel = 'User';
                        roleClass = 'role-user';
                    }

                    // Render attachments
                    let attachmentsHtml = '';
                    const attachs = c.attachments || [];
                    if (attachs.length > 0) {
                        attachmentsHtml = '<div class="comment-attachments-list">';
                        attachs.forEach(a => {
                            attachmentsHtml += `
                                <div class="attachment-item">
                                    <a href="${baseUrl}index.php?page=download_attachment&id=${a.id}" class="attachment-link" title="Download">
                                        📎 ${escapeHtml(a.original_name)} (${formatBytes(a.file_size)})
                                    </a>
                                    ${showDelete ? `<button class="delete-attach-btn" data-id="${a.id}">✖</button>` : ''}
                                </div>
                            `;
                        });
                        attachmentsHtml += '</div>';
                    }

                    // Render comment content
                    card.innerHTML = `
                        <div class="comment-header">
                            <div class="commenter-info">
                                <span class="commenter-name">${escapeHtml(c.penulis_nama)}</span>
                                <span class="commenter-badge ${roleClass}">${roleLabel}</span>
                                <span class="comment-time">${formatDateString(c.created_at)}</span>
                            </div>
                            ${showDelete ? `<button class="btn-delete-comment" data-id="${c.id}">Hapus</button>` : ''}
                        </div>
                        <div class="comment-body">
                            ${nl2br(escapeHtml(c.isi))}
                        </div>
                        ${attachmentsHtml}
                    `;

                    // Bind delete comment button
                    const delBtn = card.querySelector('.btn-delete-comment');
                    if (delBtn) {
                        delBtn.addEventListener('click', function() {
                            if (confirm('Apakah Anda yakin ingin menghapus komentar ini?')) {
                                deleteComment(c.id);
                            }
                        });
                    }

                    // Bind delete attachment button
                    const delAttachBtns = card.querySelectorAll('.delete-attach-btn');
                    delAttachBtns.forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            const attachId = btn.getAttribute('data-id');
                            if (confirm('Apakah Anda yakin ingin menghapus lampiran ini?')) {
                                deleteAttachment(attachId);
                            }
                        });
                    });

                    commentsList.appendChild(card);
                });
            })
            .catch(err => {
                commentsList.innerHTML = '<div class="comments-error">Gagal memuat diskusi.</div>';
                console.error(err);
            });
    }

    function deleteComment(id) {
        const formData = new FormData();
        formData.append('id', id);
        fetch(baseUrl + 'index.php?page=comment_delete', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            fetchComments();
        })
        .catch(err => console.error(err));
    }

    function deleteAttachment(id) {
        const formData = new FormData();
        formData.append('id', id);
        fetch(baseUrl + 'index.php?page=delete_attachment', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            fetchComments();
        })
        .catch(err => console.error(err));
    }

    // Helper functions
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function nl2br(str) {
        return str.replace(/\n/g, '<br>');
    }

    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    function formatDateString(dateStr) {
        const date = new Date(dateStr.replace(/-/g, "/"));
        return date.toLocaleString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>

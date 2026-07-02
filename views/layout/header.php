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
            
            <!-- Dropdown Notifikasi -->
            <div class="notification-container" id="notifContainer">
                <button class="notif-bell-btn" id="notifBellBtn" aria-label="Notifikasi">
                    🔔
                    <span class="notif-badge" id="notifBadge" style="display: none;">0</span>
                </button>
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <h3>Notifikasi</h3>
                        <button id="markAllReadBtn" class="btn-link">Tandai semua dibaca</button>
                    </div>
                    <div class="notif-body" id="notifBody">
                        <div class="notif-empty">Tidak ada notifikasi baru</div>
                    </div>
                </div>
            </div>

            <?php 
                $dashboardPage = 'user_dashboard';
                if ($_SESSION['role'] === 'admin') $dashboardPage = 'admin_dashboard';
                elseif ($_SESSION['role'] === 'teknisi') $dashboardPage = 'teknisi_dashboard';
            ?>
            <a href="<?= BASE_URL ?>index.php?page=<?= $dashboardPage ?>">Dashboard</a>
            <a href="<?= BASE_URL ?>index.php?page=logout">Logout</a>
        </nav>
    <?php endif; ?>
</header>
<main class="container">

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['user_id'])): ?>
    const notifBellBtn = document.getElementById('notifBellBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifBadge = document.getElementById('notifBadge');
    const notifBody = document.getElementById('notifBody');
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    const baseUrl = '<?= BASE_URL ?>';

    // Ambil data notifikasi saat pertama kali load
    fetchNotifications();

    // Toggle dropdown saat lonceng diklik
    notifBellBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('active');
    });

    // Sembunyikan dropdown jika klik di luar dropdown
    document.addEventListener('click', function() {
        notifDropdown.classList.remove('active');
    });

    notifDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Tandai semua notifikasi telah dibaca
    markAllReadBtn.addEventListener('click', function() {
        const formData = new FormData();
        formData.append('all', '1');
        fetch(baseUrl + 'index.php?page=notification_mark_all_read', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            fetchNotifications();
        })
        .catch(err => console.error(err));
    });

    function fetchNotifications() {
        fetch(baseUrl + 'index.php?page=notifications_list')
            .then(res => res.json())
            .then(data => {
                const count = parseInt(data.unread_count || 0);
                if (count > 0) {
                    notifBadge.textContent = count;
                    notifBadge.style.display = 'inline-block';
                } else {
                    notifBadge.style.display = 'none';
                }

                const notifs = data.notifications || [];
                if (notifs.length === 0) {
                    notifBody.innerHTML = '<div class="notif-empty">Tidak ada notifikasi baru</div>';
                    return;
                }

                notifBody.innerHTML = '';
                notifs.forEach(n => {
                    const item = document.createElement('div');
                    item.className = 'notif-item' + (n.is_read == 0 ? ' unread' : '');
                    item.innerHTML = `
                        <div class="notif-text">${n.message}</div>
                        <div class="notif-time">${timeAgo(new Date(n.created_at.replace(/-/g, "/")))}</div>
                    `;
                    item.addEventListener('click', function() {
                        // Tandai notifikasi ini dibaca
                        const formData = new FormData();
                        formData.append('id', n.id);
                        fetch(baseUrl + 'index.php?page=notification_mark_read', {
                            method: 'POST',
                            body: formData
                        })
                        .then(() => {
                            // Alihkan ke detail tiket terkait
                            window.location.href = baseUrl + 'index.php?page=ticket_detail&id=' + n.ticket_id;
                        });
                    });
                    notifBody.appendChild(item);
                });
            })
            .catch(err => console.error(err));
    }

    function timeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        let interval = Math.floor(seconds / 31536000);
        if (interval > 1) return interval + " tahun lalu";
        interval = Math.floor(seconds / 2592000);
        if (interval > 1) return interval + " bulan lalu";
        interval = Math.floor(seconds / 86400);
        if (interval > 1) return interval + " hari lalu";
        interval = Math.floor(seconds / 3600);
        if (interval > 1) return interval + " jam lalu";
        interval = Math.floor(seconds / 60);
        if (interval > 1) return interval + " menit lalu";
        return seconds < 10 ? "baru saja" : Math.floor(seconds) + " detik lalu";
    }
    <?php endif; ?>
});
</script>

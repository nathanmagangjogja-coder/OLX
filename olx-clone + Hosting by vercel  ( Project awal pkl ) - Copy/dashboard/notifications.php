<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Notifikasi - OLX Clone';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
redirectIfNotLoggedIn('/login.php');

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();
?>

<main class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row gap-6">
        <aside class="md:w-64 flex-shrink-0">
            <?php include 'sidebar.php'; ?>
        </aside>
        <div class="flex-1">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">Notifikasi</h1>
                <button id="mark-all-read" class="text-[#002F34] text-sm">Tandai semua sudah dibaca</button>
            </div>
            <div class="bg-white rounded-lg shadow-sm divide-y">
                <?php if (empty($notifications)): ?>
                <div class="p-8 text-center text-gray-500">Tidak ada notifikasi</div>
                <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                <div class="p-4 flex items-start <?= $notif['is_read'] ? 'opacity-60' : '' ?>">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 mr-3">
                        🔔
                    </div>
                    <div class="flex-1">
                        <h3 class="font-medium"><?= htmlspecialchars($notif['title']) ?></h3>
                        <p class="text-gray-600"><?= htmlspecialchars($notif['body']) ?></p>
                        <p class="text-xs text-gray-400 mt-1"><?= timeAgoIndonesia($notif['created_at']) ?></p>
                    </div>
                    <?php if (!$notif['is_read']): ?>
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<script>
document.getElementById('mark-all-read')?.addEventListener('click', async () => {
    await fetch('<?= BASE_URL ?>api/notifications.php?action=mark_all_read', { method: 'POST' });
    location.reload();
});
</script>
<?php require_once '../includes/footer.php'; ?>
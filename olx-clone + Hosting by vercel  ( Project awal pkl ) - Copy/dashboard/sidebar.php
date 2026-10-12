<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$user = getCurrentUser();
$userId = $_SESSION['user_id'];
$unreadStmt = $pdo->prepare("
    SELECT SUM(CASE WHEN c.buyer_id = ? THEN c.buyer_unread ELSE c.seller_unread END) as unread
    FROM conversations c
    WHERE c.buyer_id = ? OR c.seller_id = ?
");
$unreadStmt->execute([$userId, $userId, $userId]);
$unread = $unreadStmt->fetchColumn() ?: 0;
?>
<div class="bg-white rounded-lg shadow-sm p-4">
    <div class="flex items-center space-x-3 mb-4">
        <?php if ($user['avatar']): ?>
            <img src="<?= BASE_URL ?>uploads/avatars/<?= $user['avatar'] ?>" class="w-12 h-12 rounded-full object-cover">
        <?php else: ?>
            <div class="w-12 h-12 rounded-full bg-[#002F34] text-white flex items-center justify-center text-xl">
                <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
            </div>
        <?php endif; ?>
        <div>
            <p class="font-bold"><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></p>
            <a href="settings.php" class="text-xs text-[#002F34]">Edit Profil</a>
        </div>
    </div>
    <nav class="space-y-1">
        <a href="index.php" class="block px-3 py-2 rounded hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-gray-100 text-[#002F34] font-medium' : '' ?>">Dashboard</a>
        <a href="my-listings.php" class="block px-3 py-2 rounded hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) == 'my-listings.php' ? 'bg-gray-100 text-[#002F34] font-medium' : '' ?>">Iklan Saya</a>
        <a href="favorites.php" class="block px-3 py-2 rounded hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) == 'favorites.php' ? 'bg-gray-100 text-[#002F34] font-medium' : '' ?>">Favorit</a>
        <a href="messages.php" class="block px-3 py-2 rounded hover:bg-gray-100 flex justify-between <?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'bg-gray-100 text-[#002F34] font-medium' : '' ?>">
            Pesan
            <?php if ($unread > 0): ?>
            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $unread ?></span>
            <?php endif; ?>
        </a>
        <a href="notifications.php" class="block px-3 py-2 rounded hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'bg-gray-100 text-[#002F34] font-medium' : '' ?>">Notifikasi</a>
        <a href="settings.php" class="block px-3 py-2 rounded hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-gray-100 text-[#002F34] font-medium' : '' ?>">Pengaturan</a>
    </nav>
</div>
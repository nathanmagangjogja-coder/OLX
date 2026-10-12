<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Dashboard - OLX Clone';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
redirectIfNotLoggedIn('/login.php');

$userId = $_SESSION['user_id'];
$user = getCurrentUser();

// Quick stats
$activeStmt = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ? AND status = 'active'");
$activeStmt->execute([$userId]);
$activeCount = $activeStmt->fetchColumn();

$soldStmt = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ? AND status = 'sold'");
$soldStmt->execute([$userId]);
$soldCount = $soldStmt->fetchColumn();

$viewsStmt = $pdo->prepare("SELECT SUM(view_count) FROM listings WHERE user_id = ?");
$viewsStmt->execute([$userId]);
$totalViews = $viewsStmt->fetchColumn() ?: 0;

$favStmt = $pdo->prepare("SELECT SUM(favorite_count) FROM listings WHERE user_id = ?");
$favStmt->execute([$userId]);
$totalFavs = $favStmt->fetchColumn() ?: 0;

// Recent messages unread count
$msgStmt = $pdo->prepare("
    SELECT SUM(CASE WHEN c.buyer_id = ? THEN c.buyer_unread ELSE c.seller_unread END) as unread
    FROM conversations c
    WHERE c.buyer_id = ? OR c.seller_id = ?
");
$msgStmt->execute([$userId, $userId, $userId]);
$unreadMessages = $msgStmt->fetchColumn() ?: 0;
?>

<main class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row gap-6">
        <!-- Sidebar -->
        <aside class="md:w-64 flex-shrink-0">
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
                        <p class="text-xs text-gray-500"><?= $user['seller_type'] ?></p>
                    </div>
                </div>
                <nav class="space-y-1">
                    <a href="index.php" class="block px-3 py-2 rounded bg-gray-100 text-[#002F34] font-medium">Dashboard</a>
                    <a href="my-listings.php" class="block px-3 py-2 rounded hover:bg-gray-100">Iklan Saya</a>
                    <a href="favorites.php" class="block px-3 py-2 rounded hover:bg-gray-100">Favorit</a>
                    <a href="messages.php" class="block px-3 py-2 rounded hover:bg-gray-100 flex justify-between">
                        Pesan
                        <?php if ($unreadMessages > 0): ?>
                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $unreadMessages ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="notifications.php" class="block px-3 py-2 rounded hover:bg-gray-100">Notifikasi</a>
                    <a href="settings.php" class="block px-3 py-2 rounded hover:bg-gray-100">Pengaturan</a>
                </nav>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 space-y-6">
            <h1 class="text-2xl font-bold">Selamat datang, <?= htmlspecialchars($user['full_name'] ?? $user['username']) ?>!</h1>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <p class="text-gray-500 text-sm">Iklan Aktif</p>
                    <p class="text-2xl font-bold"><?= $activeCount ?></p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <p class="text-gray-500 text-sm">Terjual</p>
                    <p class="text-2xl font-bold"><?= $soldCount ?></p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <p class="text-gray-500 text-sm">Total Dilihat</p>
                    <p class="text-2xl font-bold"><?= number_format($totalViews, 0, ',', '.') ?></p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <p class="text-gray-500 text-sm">Total Favorit</p>
                    <p class="text-2xl font-bold"><?= number_format($totalFavs, 0, ',', '.') ?></p>
                </div>
            </div>
            
            <!-- Recent Listings -->
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="font-bold text-lg">Iklan Terbaru Anda</h2>
                    <a href="my-listings.php" class="text-[#002F34] text-sm">Lihat semua &rarr;</a>
                </div>
                <?php
                $recentStmt = $pdo->prepare("
                    SELECT l.*, 
                           (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
                    FROM listings l
                    WHERE l.user_id = ?
                    ORDER BY l.created_at DESC
                    LIMIT 3
                ");
                $recentStmt->execute([$userId]);
                $recentListings = $recentStmt->fetchAll();
                ?>
                <?php if (empty($recentListings)): ?>
                <div class="text-center py-8 text-gray-500">
                    <p>Belum ada iklan.</p>
                    <a href="<?= BASE_URL ?>post.php" class="inline-block mt-2 bg-[#FFCE32] text-[#002F34] px-4 py-2 rounded-full font-bold">Pasang Iklan Pertama</a>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recentListings as $listing): ?>
                    <div class="flex items-center border-b pb-3">
                        <div class="w-16 h-16 bg-gray-200 rounded flex-shrink-0">
                            <?php if ($listing['primary_image']): ?>
                                <img src="<?= BASE_URL ?>uploads/listings/<?= $listing['primary_image'] ?>" class="w-full h-full object-cover rounded">
                            <?php endif; ?>
                        </div>
                        <div class="ml-3 flex-1">
                            <a href="<?= BASE_URL ?>detail.php?id=<?= $listing['id'] ?>" class="font-medium hover:text-[#002F34]"><?= htmlspecialchars($listing['title']) ?></a>
                            <p class="text-sm text-gray-500"><?= formatRupiah($listing['price']) ?> • <?= $listing['view_count'] ?> dilihat</p>
                            <span class="text-xs px-2 py-1 rounded-full bg-<?= $listing['status'] === 'active' ? 'green' : 'gray' ?>-100"><?= ucfirst($listing['status']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php require_once '../includes/footer.php'; ?>
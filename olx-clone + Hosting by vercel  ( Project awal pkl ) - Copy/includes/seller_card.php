<?php
// File: includes/seller_card.php
// Digunakan di halaman detail.php untuk menampilkan info penjual

if (!isset($listing) || !isset($pdo)) {
    return;
}

// Ambil statistik penjual
$sellerId = $listing['seller_id'];
$stmt = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ? AND status = 'active'");
$stmt->execute([$sellerId]);
$activeListings = $stmt->fetchColumn();

// Hitung rating rata-rata (jika ada)
$ratingStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE reviewed_id = ?");
$ratingStmt->execute([$sellerId]);
$ratingData = $ratingStmt->fetch();
$avgRating = round($ratingData['avg_rating'] ?? 0, 1);
$totalReviews = $ratingData['total_reviews'] ?? 0;
?>

<div class="flex items-center space-x-3 mb-4">
    <?php if (!empty($listing['seller_avatar'])): ?>
        <img src="<?= BASE_URL ?>uploads/avatars/<?= htmlspecialchars($listing['seller_avatar']) ?>" 
             alt="<?= htmlspecialchars($listing['seller_name']) ?>" 
             class="w-12 h-12 rounded-full object-cover">
    <?php else: ?>
        <div class="w-12 h-12 rounded-full bg-[#002F34] text-white flex items-center justify-center text-xl font-bold">
            <?= strtoupper(substr($listing['seller_name'], 0, 1)) ?>
        </div>
    <?php endif; ?>
    <div>
        <h3 class="font-bold"><?= htmlspecialchars($listing['seller_name']) ?></h3>
        <p class="text-sm text-gray-500">
            Aktif <?= isset($listing['last_seen']) ? timeAgoIndonesia($listing['last_seen']) : 'beberapa waktu lalu' ?>
        </p>
    </div>
</div>

<div class="flex items-center space-x-2 text-sm text-gray-600 mb-3">
    <span class="flex items-center">
        <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
        </svg>
        <?= $avgRating ?> (<?= $totalReviews ?> ulasan)
    </span>
    <span>•</span>
    <span><?= $activeListings ?> iklan aktif</span>
</div>

<a href="<?= BASE_URL ?>search.php?user_id=<?= $sellerId ?>" 
   class="inline-block text-[#002F34] text-sm font-medium hover:underline">
    Lihat semua iklan &rarr;
</a>
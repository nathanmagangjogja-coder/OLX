<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$userId = intval($_GET['id'] ?? 0);
if (!$userId) {
    header('Location: ' . BASE_URL);
    exit;
}

// Ambil data user
$stmt = $pdo->prepare("
    SELECT u.*, p.name as province_name, c.name as city_name
    FROM users u
    LEFT JOIN provinces p ON u.province_id = p.id
    LEFT JOIN cities c ON u.city_id = c.id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . BASE_URL);
    exit;
}

// Hitung jumlah iklan aktif
$activeStmt = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ? AND status = 'active'");
$activeStmt->execute([$userId]);
$activeCount = $activeStmt->fetchColumn();

// Hitung total iklan
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ?");
$totalStmt->execute([$userId]);
$totalCount = $totalStmt->fetchColumn();

// Ambil rating rata-rata dan jumlah ulasan
$ratingStmt = $pdo->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
    FROM reviews 
    WHERE reviewed_id = ?
");
$ratingStmt->execute([$userId]);
$ratingData = $ratingStmt->fetch();
$avgRating = round($ratingData['avg_rating'] ?? 0, 1);
$totalReviews = $ratingData['total_reviews'] ?? 0;

// Ambil daftar iklan (semua status aktif)
$listingsStmt = $pdo->prepare("
    SELECT l.*, 
           (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           c.name as city_name
    FROM listings l
    LEFT JOIN cities c ON l.city_id = c.id
    WHERE l.user_id = ? AND l.status = 'active'
    ORDER BY l.created_at DESC
");
$listingsStmt->execute([$userId]);
$listings = $listingsStmt->fetchAll();

// Ambil ulasan (3 terbaru)
$reviewsStmt = $pdo->prepare("
    SELECT r.*, u.full_name as reviewer_name, u.avatar as reviewer_avatar
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    WHERE r.reviewed_id = ?
    ORDER BY r.created_at DESC
    LIMIT 3
");
$reviewsStmt->execute([$userId]);
$reviews = $reviewsStmt->fetchAll();

$pageTitle = htmlspecialchars($user['full_name'] ?? $user['username']) . ' - Profil Penjual';
$pageDescription = 'Lihat profil dan iklan dari ' . htmlspecialchars($user['full_name'] ?? $user['username']);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container mx-auto px-4 py-6">
    <!-- Breadcrumb -->
    <nav class="text-sm mb-4">
        <a href="<?= BASE_URL ?>" class="text-gray-500 hover:text-[#002F34]">Home</a> &gt;
        <span class="text-gray-700">Profil Penjual</span>
    </nav>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Left Column: Info Penjual -->
        <div class="lg:w-80 flex-shrink-0">
            <div class="bg-white rounded-lg shadow-sm p-6 sticky top-24">
                <div class="text-center">
                    <?php if ($user['avatar']): ?>
                        <img src="<?= BASE_URL ?>uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>" 
                             class="w-24 h-24 rounded-full object-cover mx-auto">
                    <?php else: ?>
                        <div class="w-24 h-24 rounded-full bg-[#002F34] text-white flex items-center justify-center text-3xl font-bold mx-auto">
                            <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <h1 class="text-xl font-bold mt-3"><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></h1>
                    
                    <?php if (!empty($user['bio'])): ?>
                        <p class="text-gray-600 text-sm mt-2"><?= htmlspecialchars($user['bio']) ?></p>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-center mt-2 text-sm text-gray-500">
                        <svg class="w-4 h-4 mr-1 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span><?= $avgRating ?> (<?= $totalReviews ?> ulasan)</span>
                    </div>
                    
                    <div class="flex items-center justify-center mt-1 text-sm text-gray-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Anggota sejak <?= date('M Y', strtotime($user['created_at'])) ?></span>
                    </div>
                    
                    <div class="flex items-center justify-center mt-1 text-sm text-gray-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span><?= htmlspecialchars($user['city_name'] ?? '') ?><?= $user['province_name'] ? ', ' . htmlspecialchars($user['province_name']) : '' ?></span>
                    </div>
                    
                    <?php if ($user['is_verified']): ?>
                        <div class="mt-3">
                            <span class="bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full">✓ Pengguna terverifikasi</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <hr class="my-4">
                
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <p class="text-2xl font-bold"><?= $activeCount ?></p>
                        <p class="text-xs text-gray-500">Iklan Aktif</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= $totalCount ?></p>
                        <p class="text-xs text-gray-500">Total Iklan</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= $totalReviews ?></p>
                        <p class="text-xs text-gray-500">Ulasan</p>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <!-- Tombol Aksi -->
                <div class="space-y-2">
                    <?php if (isLoggedIn() && $_SESSION['user_id'] == $userId): ?>
                        <a href="<?= BASE_URL ?>dashboard/settings.php" class="block w-full bg-[#002F34] text-white text-center py-2 rounded-lg">Edit Profil</a>
                    <?php else: ?>
                        <button class="w-full bg-[#FFCE32] text-[#002F34] font-bold py-2 rounded-lg">+ Follow</button>
                    <?php endif; ?>
                    
                    <button onclick="shareProfile()" class="w-full border border-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-50">
                        🔗 Bagikan Profil
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column: Iklan & Ulasan -->
        <div class="flex-1">
            <!-- Tab Iklan / Ulasan -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="border-b">
                    <nav class="flex">
                        <button class="tab-btn active px-6 py-3 font-medium text-[#002F34] border-b-2 border-[#002F34]" data-tab="listings">
                            Iklan (<?= $activeCount ?>)
                        </button>
                        <button class="tab-btn px-6 py-3 font-medium text-gray-500 hover:text-[#002F34]" data-tab="reviews">
                            Ulasan Penjual (<?= $totalReviews ?>)
                        </button>
                    </nav>
                </div>

                <!-- Tab Content: Iklan -->
                <div id="tab-listings" class="p-4">
                    <?php if (empty($listings)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <p>Belum ada iklan aktif.</p>
                        </div>
                    <?php else: ?>
                        <div class="product-grid grid gap-4">
                            <?php foreach ($listings as $listing): ?>
                                <div class="product-card bg-white rounded-lg overflow-hidden shadow-sm border">
                                    <a href="<?= BASE_URL ?>detail.php?id=<?= $listing['id'] ?>">
                                        <div class="aspect-square bg-gray-200">
                                            <?php if ($listing['primary_image']): ?>
                                                <img src="<?= BASE_URL ?>uploads/listings/<?= $listing['primary_image'] ?>" 
                                                     alt="<?= htmlspecialchars($listing['title']) ?>" 
                                                     class="w-full h-full object-cover" loading="lazy">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="p-3">
                                            <h3 class="font-medium text-gray-800 line-clamp-2 mb-1"><?= htmlspecialchars($listing['title']) ?></h3>
                                            <p class="text-[#002F34] font-bold text-lg"><?= formatRupiah($listing['price']) ?></p>
                                            <div class="flex items-center justify-between mt-2">
                                                <span class="text-gray-500 text-sm"><?= htmlspecialchars($listing['city_name'] ?? '') ?></span>
                                                <span class="text-gray-400 text-xs"><?= timeAgoIndonesia($listing['created_at']) ?></span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab Content: Ulasan -->
                <div id="tab-reviews" class="p-4 hidden">
                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <p>Belum ada ulasan.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($reviews as $review): ?>
                                <div class="border-b pb-4">
                                    <div class="flex items-start">
                                        <?php if ($review['reviewer_avatar']): ?>
                                            <img src="<?= BASE_URL ?>uploads/avatars/<?= htmlspecialchars($review['reviewer_avatar']) ?>" class="w-10 h-10 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center font-bold">
                                                <?= strtoupper(substr($review['reviewer_name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="ml-3 flex-1">
                                            <div class="flex items-center">
                                                <span class="font-medium"><?= htmlspecialchars($review['reviewer_name']) ?></span>
                                                <span class="ml-2 flex items-center">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <svg class="w-4 h-4 <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                        </svg>
                                                    <?php endfor; ?>
                                                </span>
                                                <span class="text-xs text-gray-500 ml-auto"><?= timeAgoIndonesia($review['created_at']) ?></span>
                                            </div>
                                            <p class="text-gray-700 mt-1"><?= htmlspecialchars($review['comment']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="#" class="text-[#002F34] text-sm">Lihat semua ulasan &rarr;</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Update active tab button
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active', 'text-[#002F34]', 'border-b-2', 'border-[#002F34]');
            b.classList.add('text-gray-500');
        });
        this.classList.add('active', 'text-[#002F34]', 'border-b-2', 'border-[#002F34]');
        this.classList.remove('text-gray-500');
        
        // Show corresponding tab content
        const tabName = this.dataset.tab;
        document.getElementById('tab-listings').classList.add('hidden');
        document.getElementById('tab-reviews').classList.add('hidden');
        document.getElementById('tab-' + tabName).classList.remove('hidden');
    });
});

// Share profile
function shareProfile() {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({
            title: '<?= htmlspecialchars($user['full_name'] ?? $user['username']) ?> - OLX Clone',
            url: url
        }).catch(() => {});
    } else {
        navigator.clipboard?.writeText(url).then(() => {
            window.showToast('Link profil disalin ke clipboard!', 'success');
        }).catch(() => {
            window.showToast('Gagal menyalin link. Silakan salin manual.', 'error');
        });
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
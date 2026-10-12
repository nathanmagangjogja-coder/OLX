<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Favorit Saya - OLX Clone';
redirectIfNotLoggedIn('/login.php');

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT l.*, 
           (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           c.name as city_name,
           f.created_at as favorited_at
    FROM favorites f
    JOIN listings l ON f.listing_id = l.id
    LEFT JOIN cities c ON l.city_id = c.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row gap-6">
        <aside class="md:w-64 flex-shrink-0">
            <?php include __DIR__ . '/sidebar.php'; ?>
        </aside>
        <div class="flex-1">
            <h1 class="text-2xl font-bold mb-4">Favorit Saya</h1>
            <?php if (empty($favorites)): ?>
            <div class="bg-white rounded-lg p-8 text-center">
                <p class="text-gray-500 mb-4">Belum ada produk favorit.</p>
                <a href="<?= BASE_URL ?>" class="bg-[#FFCE32] text-[#002F34] px-6 py-2 rounded-full font-bold">Jelajahi Iklan</a>
            </div>
            <?php else: ?>
            <div class="product-grid grid gap-4">
                <?php foreach ($favorites as $fav): ?>
                <div class="product-card bg-white rounded-lg overflow-hidden shadow-sm relative group">
                    <a href="<?= BASE_URL ?>detail.php?id=<?= $fav['id'] ?>">
                        <div class="aspect-square bg-gray-200">
                            <?php if ($fav['primary_image']): ?>
                                <img src="<?= BASE_URL ?>uploads/listings/<?= $fav['primary_image'] ?>" class="w-full h-full object-cover" loading="lazy">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <h3 class="font-medium line-clamp-2"><?= htmlspecialchars($fav['title']) ?></h3>
                            <p class="text-[#002F34] font-bold"><?= formatRupiah($fav['price']) ?></p>
                            <p class="text-gray-500 text-sm"><?= htmlspecialchars($fav['city_name'] ?? '') ?></p>
                        </div>
                    </a>
                    <button onclick="removeFavorite(<?= $fav['id'] ?>)" class="absolute top-2 right-2 bg-white rounded-full p-1 shadow-md text-red-500 opacity-0 group-hover:opacity-100 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?>';

async function removeFavorite(listingId) {
    if (!confirm('Hapus dari favorit?')) return;
    
    const formData = new FormData();
    formData.append('action', 'toggle');
    formData.append('listing_id', listingId);
    formData.append('csrf_token', CSRF_TOKEN);
    
    try {
        const res = await fetch(BASE_URL + 'api/favorites.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.showToast('Dihapus dari favorit', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            window.showToast(data.message || 'Gagal menghapus favorit', 'error');
        }
    } catch (e) {
        window.showToast('Terjadi kesalahan jaringan', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Iklan Saya - OLX Clone';
redirectIfNotLoggedIn('/login.php');

$userId = $_SESSION['user_id'];
$statusFilter = $_GET['status'] ?? 'active';
$allowedStatus = ['active', 'draft', 'sold', 'expired', 'banned'];

$where = "user_id = ?";
$params = [$userId];
if (in_array($statusFilter, $allowedStatus)) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("
    SELECT l.*, 
           (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           c.name as city_name
    FROM listings l
    LEFT JOIN cities c ON l.city_id = c.id
    WHERE $where
    ORDER BY l.created_at DESC
");
$stmt->execute($params);
$listings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row gap-6">
        <!-- Sidebar -->
        <aside class="md:w-64 flex-shrink-0">
            <?php include __DIR__ . '/sidebar.php'; ?>
        </aside>
        
        <div class="flex-1">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">Iklan Saya</h1>
                <a href="<?= BASE_URL ?>post.php" class="bg-[#FFCE32] text-[#002F34] px-4 py-2 rounded-full font-bold">+ Pasang Iklan</a>
            </div>
            
            <!-- Tabs -->
            <div class="border-b mb-4">
                <nav class="flex space-x-4">
                    <a href="?status=active" class="py-2 px-1 border-b-2 <?= $statusFilter === 'active' ? 'border-[#002F34] text-[#002F34] font-medium' : 'border-transparent text-gray-500' ?>">Aktif</a>
                    <a href="?status=draft" class="py-2 px-1 border-b-2 <?= $statusFilter === 'draft' ? 'border-[#002F34] text-[#002F34] font-medium' : 'border-transparent text-gray-500' ?>">Draft</a>
                    <a href="?status=sold" class="py-2 px-1 border-b-2 <?= $statusFilter === 'sold' ? 'border-[#002F34] text-[#002F34] font-medium' : 'border-transparent text-gray-500' ?>">Terjual</a>
                    <a href="?status=expired" class="py-2 px-1 border-b-2 <?= $statusFilter === 'expired' ? 'border-[#002F34] text-[#002F34] font-medium' : 'border-transparent text-gray-500' ?>">Kedaluwarsa</a>
                </nav>
            </div>
            
            <?php if (empty($listings)): ?>
            <div class="bg-white rounded-lg p-8 text-center">
                <p class="text-gray-500">Tidak ada iklan dengan status ini.</p>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-3 text-left">Iklan</th>
                            <th class="p-3 text-left">Harga</th>
                            <th class="p-3 text-left">Dilihat</th>
                            <th class="p-3 text-left">Favorit</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $listing): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gray-200 rounded flex-shrink-0">
                                        <?php if ($listing['primary_image']): ?>
                                            <img src="<?= BASE_URL ?>uploads/listings/<?= $listing['primary_image'] ?>" class="w-full h-full object-cover rounded">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">📷</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-3">
                                        <a href="<?= BASE_URL ?>detail.php?id=<?= $listing['id'] ?>" class="font-medium hover:text-[#002F34]"><?= htmlspecialchars($listing['title']) ?></a>
                                        <p class="text-xs text-gray-500"><?= timeAgoIndonesia($listing['created_at']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 font-bold"><?= formatRupiah($listing['price']) ?></td>
                            <td class="p-3"><?= $listing['view_count'] ?></td>
                            <td class="p-3"><?= $listing['favorite_count'] ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 text-xs rounded-full <?= $listing['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>"><?= ucfirst($listing['status']) ?></span>
                            </td>
                            <td class="p-3">
                                <div class="flex space-x-2">
                                    <a href="<?= BASE_URL ?>post.php?edit=<?= $listing['id'] ?>" class="text-blue-600 hover:underline">Edit</a>
                                    <?php if ($listing['status'] === 'active'): ?>
                                    <button onclick="markAsSold(<?= $listing['id'] ?>)" class="text-green-600 hover:underline">Terjual</button>
                                    <button onclick="deleteListing(<?= $listing['id'] ?>)" class="text-red-600 hover:underline">Hapus</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?>';

// Fungsi showToast sekarang menggunakan yang global dari toast.js

async function markAsSold(id) {
    if (!confirm('Tandai sebagai terjual?')) return;
    const formData = new FormData();
    formData.append('action', 'mark_sold');
    formData.append('listing_id', id);
    formData.append('csrf_token', CSRF_TOKEN);
    try {
        const res = await fetch(BASE_URL + 'api/listings.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.showToast('Iklan berhasil ditandai terjual', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            window.showToast(data.message || 'Gagal mengubah status', 'error');
        }
    } catch (e) {
        window.showToast('Terjadi kesalahan jaringan', 'error');
    }
}

async function deleteListing(id) {
    if (!confirm('Yakin hapus permanen?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('listing_id', id);
    formData.append('csrf_token', CSRF_TOKEN);
    try {
        const res = await fetch(BASE_URL + 'api/listings.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.showToast('Iklan berhasil dihapus', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            window.showToast(data.message || 'Gagal menghapus iklan', 'error');
        }
    } catch (e) {
        window.showToast('Terjadi kesalahan jaringan', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
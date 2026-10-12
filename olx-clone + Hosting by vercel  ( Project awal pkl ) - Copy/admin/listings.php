<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Manajemen Iklan';
redirectIfNotAdmin('/login.php');

// Handle ban via GET
if (isset($_GET['ban']) && is_numeric($_GET['ban'])) {
    $id = intval($_GET['ban']);
    $pdo->prepare("UPDATE listings SET status = 'banned' WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = ['message' => 'Iklan telah dibanned', 'type' => 'success'];
    header('Location: listings.php');
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) die('Invalid CSRF');
    $action = $_POST['action'] ?? '';
    $listingId = intval($_POST['listing_id'] ?? 0);
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM listings WHERE id = ?")->execute([$listingId]);
        $_SESSION['flash'] = ['message' => 'Iklan berhasil dihapus', 'type' => 'success'];
    } elseif ($action === 'ban') {
        $pdo->prepare("UPDATE listings SET status = 'banned' WHERE id = ?")->execute([$listingId]);
        $_SESSION['flash'] = ['message' => 'Iklan telah dibanned', 'type' => 'success'];
    } elseif ($action === 'activate') {
        $pdo->prepare("UPDATE listings SET status = 'active' WHERE id = ?")->execute([$listingId]);
        $_SESSION['flash'] = ['message' => 'Iklan diaktifkan kembali', 'type' => 'success'];
    }
    header('Location: listings.php');
    exit;
}

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
if ($search) {
    $where[] = "(l.title LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    $where[] = "l.status = ?";
    $params[] = $status;
}

$sql = "SELECT l.*, u.username as seller_name FROM listings l JOIN users u ON l.user_id = u.id";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$paramIndex = 1;
foreach ($params as $p) $stmt->bindValue($paramIndex++, $p);
$stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
$stmt->execute();
$listings = $stmt->fetchAll();

$countSql = "SELECT COUNT(*) FROM listings l JOIN users u ON l.user_id = u.id";
if ($where) $countSql .= " WHERE " . implode(' AND ', $where);
$countStmt = $pdo->prepare($countSql);
foreach ($params as $i => $p) $countStmt->bindValue($i+1, $p);
$countStmt->execute();
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

require_once __DIR__ . '/layout.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Manajemen Iklan</h1>
    <p class="text-gray-600">Kelola semua iklan yang ada di platform.</p>
</div>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 rounded mb-4">
        <?= $_SESSION['flash']['message']; unset($_SESSION['flash']); ?>
    </div>
<?php endif; ?>

<!-- Filter Card -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <div class="flex-1">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul atau penjual..." class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-[#002F34]">
        </div>
        <div class="md:w-48">
            <select name="status" class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-[#002F34]">
                <option value="">Semua Status</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                <option value="sold" <?= $status === 'sold' ? 'selected' : '' ?>>Terjual</option>
                <option value="banned" <?= $status === 'banned' ? 'selected' : '' ?>>Banned</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-[#002F34] text-white px-6 py-2.5 rounded-lg hover:bg-[#001f24] transition">Filter</button>
            <a href="listings.php" class="border border-gray-300 px-6 py-2.5 rounded-lg hover:bg-gray-50 transition">Reset</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penjual</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($listings as $l): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap font-mono text-gray-600">#<?= $l['id'] ?></td>
                    <td class="px-6 py-4">
                        <a href="<?= BASE_URL ?>detail.php?id=<?= $l['id'] ?>" target="_blank" class="text-gray-900 hover:text-[#002F34] font-medium">
                            <?= htmlspecialchars($l['title']) ?>
                        </a>
                    </td>
                    <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($l['seller_name']) ?></td>
                    <td class="px-6 py-4 font-medium text-gray-900"><?= formatRupiah($l['price']) ?></td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 text-xs font-medium rounded-full <?= $l['status'] === 'active' ? 'bg-green-100 text-green-800' : ($l['status'] === 'banned' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') ?>">
                            <?= ucfirst($l['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <a href="<?= BASE_URL ?>detail.php?id=<?= $l['id'] ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">Lihat</a>
                            <?php if ($l['status'] === 'banned'): ?>
                            <form method="POST" onsubmit="return confirm('Aktifkan iklan ini?')" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="activate">
                                <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                                <button class="text-green-600 hover:text-green-800 text-sm">Aktifkan</button>
                            </form>
                            <?php else: ?>
                            <form method="POST" onsubmit="return confirm('Ban iklan ini?')" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="ban">
                                <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                                <button class="text-yellow-600 hover:text-yellow-800 text-sm">Ban</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" onsubmit="return confirm('Hapus permanen?')" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                                <button class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($listings)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <p>Tidak ada iklan ditemukan</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <div class="mt-6"><?= generatePagination($total, $page, $limit) ?></div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
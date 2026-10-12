<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Manajemen Laporan';
redirectIfNotAdmin('/login.php');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) die('Invalid CSRF');
    $action = $_POST['action'] ?? '';
    $reportId = intval($_POST['report_id'] ?? 0);
    if ($action === 'resolve') {
        $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute([$reportId]);
        $_SESSION['flash'] = ['message' => 'Laporan ditandai selesai', 'type' => 'success'];
    } elseif ($action === 'review') {
        $pdo->prepare("UPDATE reports SET status = 'reviewing' WHERE id = ?")->execute([$reportId]);
        $_SESSION['flash'] = ['message' => 'Laporan sedang ditinjau', 'type' => 'info'];
    }
    header('Location: reports.php');
    exit;
}

$status = $_GET['status'] ?? 'pending';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = ["r.status = ?"];
$params = [$status];

$sql = "SELECT r.*, l.title as listing_title, l.status as listing_status,
               u.username as reporter_name, seller.username as seller_name
        FROM reports r
        JOIN listings l ON r.listing_id = l.id
        JOIN users u ON r.reporter_id = u.id
        JOIN users seller ON l.user_id = seller.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY r.created_at DESC LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$paramIndex = 1;
foreach ($params as $p) $stmt->bindValue($paramIndex++, $p);
$stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
$stmt->execute();
$reports = $stmt->fetchAll();

$countSql = "SELECT COUNT(*) FROM reports r WHERE status = ?";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute([$status]);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

require_once __DIR__ . '/layout.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Manajemen Laporan</h1>
    <p class="text-gray-600">Tinjau dan proses laporan dari pengguna.</p>
</div>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="bg-<?= $_SESSION['flash']['type'] === 'info' ? 'blue' : 'green' ?>-100 border-l-4 border-<?= $_SESSION['flash']['type'] === 'info' ? 'blue' : 'green' ?>-500 text-<?= $_SESSION['flash']['type'] === 'info' ? 'blue' : 'green' ?>-800 p-4 rounded mb-4">
        <?= $_SESSION['flash']['message']; unset($_SESSION['flash']); ?>
    </div>
<?php endif; ?>

<!-- Status Tabs -->
<div class="bg-white rounded-xl shadow-sm p-1 mb-6 inline-flex">
    <a href="?status=pending" class="px-6 py-2 rounded-lg text-sm font-medium transition <?= $status === 'pending' ? 'bg-[#002F34] text-white' : 'text-gray-600 hover:bg-gray-100' ?>">Pending</a>
    <a href="?status=reviewing" class="px-6 py-2 rounded-lg text-sm font-medium transition <?= $status === 'reviewing' ? 'bg-[#002F34] text-white' : 'text-gray-600 hover:bg-gray-100' ?>">Sedang Ditinjau</a>
    <a href="?status=resolved" class="px-6 py-2 rounded-lg text-sm font-medium transition <?= $status === 'resolved' ? 'bg-[#002F34] text-white' : 'text-gray-600 hover:bg-gray-100' ?>">Selesai</a>
</div>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelapor</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Iklan</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penjual</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alasan</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($reports as $r): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap font-mono text-gray-600">#<?= $r['id'] ?></td>
                    <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($r['reporter_name']) ?></td>
                    <td class="px-6 py-4">
                        <a href="<?= BASE_URL ?>detail.php?id=<?= $r['listing_id'] ?>" target="_blank" class="text-gray-900 hover:text-[#002F34] font-medium">
                            <?= htmlspecialchars($r['listing_title']) ?>
                        </a>
                        <div class="text-xs text-gray-500 mt-1">Status iklan: <?= $r['listing_status'] ?></div>
                    </td>
                    <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($r['seller_name']) ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800"><?= ucfirst($r['reason']) ?></span>
                    </td>
                    <td class="px-6 py-4 text-gray-600 max-w-xs">
                        <div class="truncate" title="<?= htmlspecialchars($r['description'] ?? '') ?>">
                            <?= htmlspecialchars($r['description'] ?? '-') ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-500 whitespace-nowrap"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 text-xs font-medium rounded-full <?= $r['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($r['status'] === 'reviewing' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                            <?= ucfirst($r['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2">
                            <?php if ($r['status'] === 'pending'): ?>
                            <form method="POST" onsubmit="return confirm('Mulai meninjau laporan ini?')" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="review">
                                <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                <button class="text-blue-600 hover:text-blue-800 text-sm">Tinjau</button>
                            </form>
                            <?php elseif ($r['status'] === 'reviewing'): ?>
                            <form method="POST" onsubmit="return confirm('Tandai laporan selesai?')" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="resolve">
                                <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                <button class="text-green-600 hover:text-green-800 text-sm">Selesai</button>
                            </form>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>detail.php?id=<?= $r['listing_id'] ?>" target="_blank" class="text-gray-600 hover:text-gray-800 text-sm">Lihat</a>
                            <?php if ($r['listing_status'] !== 'banned'): ?>
                            <a href="listings.php?ban=<?= $r['listing_id'] ?>" onclick="return confirm('Ban iklan ini?')" class="text-red-600 hover:text-red-800 text-sm">Ban</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <p>Tidak ada laporan dengan status ini</p>
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
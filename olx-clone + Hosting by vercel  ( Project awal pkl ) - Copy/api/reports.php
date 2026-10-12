<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login untuk melaporkan iklan']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid']);
    exit;
}

$listingId = intval($_POST['listing_id'] ?? 0);
$reason = $_POST['reason'] ?? '';
$description = trim($_POST['description'] ?? '');

$allowedReasons = ['spam', 'fraud', 'prohibited', 'duplicate', 'offensive', 'other'];
if ($listingId <= 0 || !in_array($reason, $allowedReasons)) {
    echo json_encode(['success' => false, 'message' => 'Data laporan tidak valid']);
    exit;
}

// Cek apakah iklan ada
$check = $pdo->prepare("SELECT id FROM listings WHERE id = ?");
$check->execute([$listingId]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Iklan tidak ditemukan']);
    exit;
}

// Simpan laporan
$stmt = $pdo->prepare("
    INSERT INTO reports (reporter_id, listing_id, reason, description, status, created_at)
    VALUES (?, ?, ?, ?, 'pending', NOW())
");
$stmt->execute([$_SESSION['user_id'], $listingId, $reason, $description]);

echo json_encode(['success' => true, 'message' => 'Laporan berhasil dikirim. Terima kasih!']);
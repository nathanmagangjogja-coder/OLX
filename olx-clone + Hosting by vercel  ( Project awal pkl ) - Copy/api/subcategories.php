<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
if ($action === 'get_by_category') {
    $catId = intval($_GET['category_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, name FROM subcategories WHERE category_id=? AND is_active=1 ORDER BY sort_order");
    $stmt->execute([$catId]);
    echo json_encode($stmt->fetchAll());
} else {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid action']);
}
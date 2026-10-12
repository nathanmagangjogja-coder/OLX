<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'autocomplete') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode([]);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT title 
        FROM listings 
        WHERE title LIKE ? AND status = 'active' 
        LIMIT 10
    ");
    $stmt->execute(["%$q%"]);
    $results = $stmt->fetchAll();
    
    echo json_encode(array_map(function($item) {
        return ['title' => $item['title']];
    }, $results));
    exit;
}

// For location cascade
if ($action === 'cities') {
    $provinceId = intval($_GET['province_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, name FROM cities WHERE province_id = ? ORDER BY name");
    $stmt->execute([$provinceId]);
    echo json_encode($stmt->fetchAll());
    exit;
}
?>
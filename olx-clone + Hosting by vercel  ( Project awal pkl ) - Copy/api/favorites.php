<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

redirectIfNotLoggedIn('/login.php');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
    }
}

$userId = $_SESSION['user_id'];

switch ($action) {
    case 'toggle':
        $listingId = intval($_POST['listing_id'] ?? 0);
        
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
        $stmt->execute([$userId, $listingId]);
        
        if ($stmt->fetch()) {
            // Remove
            $del = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND listing_id = ?");
            $del->execute([$userId, $listingId]);
            $pdo->prepare("UPDATE listings SET favorite_count = favorite_count - 1 WHERE id = ?")->execute([$listingId]);
            echo json_encode(['success' => true, 'action' => 'removed']);
        } else {
            // Add
            $ins = $pdo->prepare("INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)");
            $ins->execute([$userId, $listingId]);
            $pdo->prepare("UPDATE listings SET favorite_count = favorite_count + 1 WHERE id = ?")->execute([$listingId]);
            echo json_encode(['success' => true, 'action' => 'added']);
        }
        break;
        
    case 'list':
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
        echo json_encode($stmt->fetchAll());
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
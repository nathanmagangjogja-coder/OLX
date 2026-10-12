<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

redirectIfNotLoggedIn('/login.php');
$userId = $_SESSION['user_id'];

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
    }
}

switch ($action) {
    case 'conversations':
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   l.title as listing_title,
                   (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as listing_image,
                   u.full_name as other_party_name,
                   u.avatar as other_party_avatar
            FROM conversations c
            JOIN listings l ON c.listing_id = l.id
            JOIN users u ON (CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END) = u.id
            WHERE c.buyer_id = ? OR c.seller_id = ?
            ORDER BY c.last_message_at DESC
        ");
        $stmt->execute([$userId, $userId, $userId]);
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'messages':
        $convId = intval($_GET['conversation_id'] ?? 0);
        // Verify user is part of conversation
        $check = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)");
        $check->execute([$convId, $userId, $userId]);
        if (!$check->fetch()) {
            http_response_code(403);
            die(json_encode(['error' => 'Unauthorized']));
        }
        
        $stmt = $pdo->prepare("
            SELECT m.*, u.full_name as sender_name, u.avatar as sender_avatar
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$convId]);
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'send':
        $convId = intval($_POST['conversation_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $type = $_POST['type'] ?? 'text';
        $offerAmount = intval($_POST['offer_amount'] ?? 0);
        
        if (empty($content) && $type !== 'offer') {
            echo json_encode(['success' => false, 'message' => 'Pesan tidak boleh kosong']);
            break;
        }
        
        // Verify conversation
        $conv = $pdo->prepare("SELECT * FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)");
        $conv->execute([$convId, $userId, $userId]);
        $conversation = $conv->fetch();
        if (!$conversation) {
            http_response_code(403);
            die(json_encode(['error' => 'Unauthorized']));
        }
        
        // Insert message
        $ins = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content, type, offer_amount) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$convId, $userId, $content, $type, $offerAmount]);
        
        // Update conversation
        $otherParty = ($conversation['buyer_id'] == $userId) ? 'seller' : 'buyer';
        $upd = $pdo->prepare("
            UPDATE conversations SET 
                last_message = ?,
                last_message_at = NOW(),
                {$otherParty}_unread = {$otherParty}_unread + 1
            WHERE id = ?
        ");
        $upd->execute([$content, $convId]);
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
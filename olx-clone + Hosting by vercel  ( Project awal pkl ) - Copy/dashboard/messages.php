<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Pesan - OLX Clone';
redirectIfNotLoggedIn('/login.php');

$userId = $_SESSION['user_id'];

// Get conversations
$convStmt = $pdo->prepare("
    SELECT c.*, l.title as listing_title, l.id as listing_id,
           (SELECT filename FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as listing_image,
           u.full_name as other_name, u.avatar as other_avatar, u.id as other_id
    FROM conversations c
    JOIN listings l ON c.listing_id = l.id
    JOIN users u ON (CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END) = u.id
    WHERE c.buyer_id = ? OR c.seller_id = ?
    ORDER BY c.last_message_at DESC
");
$convStmt->execute([$userId, $userId, $userId]);
$conversations = $convStmt->fetchAll();

$activeConv = isset($_GET['conv']) ? intval($_GET['conv']) : ($conversations[0]['id'] ?? 0);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container mx-auto px-4 py-6 h-[calc(100vh-200px)]">
    <div class="flex h-full bg-white rounded-lg shadow-sm overflow-hidden">
        <!-- Conversation List -->
        <div class="w-full md:w-80 border-r overflow-y-auto">
            <div class="p-3 border-b font-bold">Pesan</div>
            <?php if (empty($conversations)): ?>
            <div class="p-4 text-center text-gray-500">Belum ada percakapan</div>
            <?php else: ?>
            <?php foreach ($conversations as $conv): 
                $unread = ($conv['buyer_id'] == $userId) ? $conv['buyer_unread'] : $conv['seller_unread'];
            ?>
            <a href="?conv=<?= $conv['id'] ?>" class="block p-3 border-b hover:bg-gray-50 <?= $activeConv == $conv['id'] ? 'bg-gray-100' : '' ?>">
                <div class="flex items-start">
                    <?php if ($conv['other_avatar']): ?>
                        <img src="<?= BASE_URL ?>uploads/avatars/<?= $conv['other_avatar'] ?>" class="w-10 h-10 rounded-full">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center"><?= strtoupper(substr($conv['other_name'],0,1)) ?></div>
                    <?php endif; ?>
                    <div class="ml-3 flex-1">
                        <div class="flex justify-between">
                            <span class="font-medium"><?= htmlspecialchars($conv['other_name']) ?></span>
                            <span class="text-xs text-gray-500"><?= timeAgoIndonesia($conv['last_message_at'] ?? $conv['created_at']) ?></span>
                        </div>
                        <p class="text-sm text-gray-600 truncate"><?= htmlspecialchars($conv['listing_title']) ?></p>
                        <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($conv['last_message'] ?? 'Mulai percakapan') ?></p>
                    </div>
                    <?php if ($unread > 0): ?>
                    <span class="ml-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $unread ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Chat Area -->
        <div class="flex-1 flex flex-col">
            <?php if ($activeConv): 
                // Get conversation details
                $convDetail = null;
                foreach ($conversations as $c) if ($c['id'] == $activeConv) { $convDetail = $c; break; }
                if ($convDetail):
                    // Mark as read
                    if ($convDetail['buyer_id'] == $userId) {
                        $pdo->prepare("UPDATE conversations SET buyer_unread = 0 WHERE id = ?")->execute([$activeConv]);
                    } else {
                        $pdo->prepare("UPDATE conversations SET seller_unread = 0 WHERE id = ?")->execute([$activeConv]);
                    }
                    // Get messages
                    $msgStmt = $pdo->prepare("
                        SELECT m.*, u.full_name as sender_name, u.avatar as sender_avatar
                        FROM messages m
                        JOIN users u ON m.sender_id = u.id
                        WHERE m.conversation_id = ?
                        ORDER BY m.created_at ASC
                    ");
                    $msgStmt->execute([$activeConv]);
                    $messages = $msgStmt->fetchAll();
            ?>
            <!-- Chat Header -->
            <div class="p-3 border-b flex items-center">
                <div class="w-8 h-8 bg-gray-200 rounded-full mr-2">
                    <?php if ($convDetail['listing_image']): ?>
                    <img src="<?= BASE_URL ?>uploads/listings/<?= $convDetail['listing_image'] ?>" class="w-full h-full object-cover rounded-full">
                    <?php endif; ?>
                </div>
                <div>
                    <p class="font-medium"><?= htmlspecialchars($convDetail['other_name']) ?></p>
                    <a href="<?= BASE_URL ?>detail.php?id=<?= $convDetail['listing_id'] ?>" class="text-xs text-[#002F34]"><?= htmlspecialchars($convDetail['listing_title']) ?></a>
                </div>
            </div>
            
            <!-- Messages -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="message-container">
                <?php foreach ($messages as $msg): ?>
                <div class="flex <?= $msg['sender_id'] == $userId ? 'justify-end' : 'justify-start' ?>">
                    <div class="max-w-[70%] <?= $msg['sender_id'] == $userId ? 'bg-[#002F34] text-white' : 'bg-gray-100' ?> rounded-lg p-3">
                        <?php if ($msg['type'] === 'offer'): ?>
                        <p class="font-bold">Penawaran Harga: <?= formatRupiah($msg['offer_amount']) ?></p>
                        <p class="text-sm"><?= htmlspecialchars($msg['content']) ?></p>
                        <?php if ($msg['sender_id'] != $userId && $msg['offer_status'] === 'pending'): ?>
                        <div class="mt-2 flex space-x-2">
                            <button onclick="respondOffer(<?= $msg['id'] ?>, 'accepted')" class="bg-green-500 text-white px-2 py-1 rounded text-xs">Terima</button>
                            <button onclick="respondOffer(<?= $msg['id'] ?>, 'rejected')" class="bg-red-500 text-white px-2 py-1 rounded text-xs">Tolak</button>
                        </div>
                        <?php else: ?>
                        <p class="text-xs italic">Status: <?= ucfirst($msg['offer_status']) ?></p>
                        <?php endif; ?>
                        <?php else: ?>
                        <p><?= nl2br(htmlspecialchars($msg['content'])) ?></p>
                        <?php endif; ?>
                        <p class="text-xs mt-1 <?= $msg['sender_id'] == $userId ? 'text-gray-300' : 'text-gray-500' ?>"><?= date('H:i', strtotime($msg['created_at'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Message Input -->
            <div class="p-3 border-t">
                <form id="message-form" class="flex space-x-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="conversation_id" value="<?= $activeConv ?>">
                    <input type="text" name="content" id="message-input" placeholder="Tulis pesan..." class="flex-1 border rounded-full px-4 py-2">
                    <button type="button" id="offer-btn" class="px-3 py-2 border rounded-full">💰 Tawar</button>
                    <button type="submit" class="bg-[#002F34] text-white px-4 py-2 rounded-full">Kirim</button>
                </form>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="flex-1 flex items-center justify-center text-gray-500">Pilih percakapan untuk memulai</div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
const convId = <?= $activeConv ?>;
let lastMessageId = <?= !empty($messages) ? $messages[count($messages)-1]['id'] : 0 ?>;
const userId = <?= $userId ?>;

// Auto-refresh every 3 seconds
setInterval(async () => {
    if (!convId) return;
    try {
        const res = await fetch(`<?= BASE_URL ?>api/messages.php?action=messages&conversation_id=${convId}`);
        const messages = await res.json();
        const newMessages = messages.filter(m => m.id > lastMessageId);
        if (newMessages.length > 0) {
            newMessages.forEach(msg => appendMessage(msg));
            lastMessageId = newMessages[newMessages.length-1].id;
        }
    } catch (e) {
        console.error('Auto-refresh error:', e);
    }
}, 3000);

function appendMessage(msg) {
    const container = document.getElementById('message-container');
    const isOwn = msg.sender_id == userId;
    const div = document.createElement('div');
    div.className = `flex ${isOwn ? 'justify-end' : 'justify-start'}`;
    
    let contentHtml = '';
    if (msg.type === 'offer') {
        contentHtml = `
            <p class="font-bold">Penawaran Harga: Rp ${Number(msg.offer_amount).toLocaleString('id')}</p>
            <p class="text-sm">${msg.content.replace(/\n/g, '<br>')}</p>
            ${!isOwn && msg.offer_status === 'pending' ? `
                <div class="mt-2 flex space-x-2">
                    <button onclick="respondOffer(${msg.id}, 'accepted')" class="bg-green-500 text-white px-2 py-1 rounded text-xs">Terima</button>
                    <button onclick="respondOffer(${msg.id}, 'rejected')" class="bg-red-500 text-white px-2 py-1 rounded text-xs">Tolak</button>
                </div>
            ` : `<p class="text-xs italic">Status: ${msg.offer_status.charAt(0).toUpperCase() + msg.offer_status.slice(1)}</p>`}
        `;
    } else {
        contentHtml = `<p>${msg.content.replace(/\n/g, '<br>')}</p>`;
    }
    
    div.innerHTML = `
        <div class="max-w-[70%] ${isOwn ? 'bg-[#002F34] text-white' : 'bg-gray-100'} rounded-lg p-3">
            ${contentHtml}
            <p class="text-xs mt-1 ${isOwn ? 'text-gray-300' : 'text-gray-500'}">${new Date(msg.created_at).toLocaleTimeString('id', {hour:'2-digit', minute:'2-digit'})}</p>
        </div>
    `;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

document.getElementById('message-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = document.getElementById('message-input');
    const content = input.value.trim();
    if (!content) return;
    
    const formData = new FormData(e.target);
    formData.append('action', 'send');
    formData.append('type', 'text');
    
    try {
        const res = await fetch('<?= BASE_URL ?>api/messages.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            input.value = '';
        } else {
            window.showToast(data.message || 'Gagal mengirim pesan', 'error');
        }
    } catch (err) {
        window.showToast('Terjadi kesalahan jaringan', 'error');
    }
});

// Offer
document.getElementById('offer-btn').addEventListener('click', () => {
    const amount = prompt('Masukkan jumlah penawaran (Rp):');
    if (amount && !isNaN(amount) && amount > 0) {
        const formData = new FormData();
        formData.append('action', 'send');
        formData.append('conversation_id', convId);
        formData.append('type', 'offer');
        formData.append('offer_amount', amount);
        formData.append('content', 'Menawar harga ' + formatRupiah(amount));
        formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
        fetch('<?= BASE_URL ?>api/messages.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (!data.success) window.showToast(data.message || 'Gagal mengirim penawaran', 'error');
            })
            .catch(() => window.showToast('Terjadi kesalahan jaringan', 'error'));
    } else if (amount) {
        window.showToast('Masukkan angka yang valid', 'error');
    }
});

async function respondOffer(msgId, status) {
    const formData = new FormData();
    formData.append('action', 'respond_offer');
    formData.append('message_id', msgId);
    formData.append('status', status);
    formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
    try {
        const res = await fetch('<?= BASE_URL ?>api/messages.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.showToast(status === 'accepted' ? 'Penawaran diterima' : 'Penawaran ditolak', 'success');
            // Refresh chat setelah sebentar
            setTimeout(() => location.reload(), 1000);
        } else {
            window.showToast(data.message || 'Gagal merespon penawaran', 'error');
        }
    } catch (e) {
        window.showToast('Terjadi kesalahan jaringan', 'error');
    }
}

function formatRupiah(price) {
    return 'Rp ' + Number(price).toLocaleString('id');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
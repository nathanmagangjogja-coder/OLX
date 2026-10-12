<?php
require_once __DIR__ . '/../config/database.php';

function formatRupiah($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function isAdmin() {
    if (!isLoggedIn()) return false;
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

function redirectIfNotAdmin($url = '/login.php') {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . ltrim($url, '/'));
        exit;
    }
}

function formatRupiahShort($price) {
    if ($price >= 1000000) {
        return 'Rp ' . round($price / 1000000, 1) . ' jt';
    } elseif ($price >= 1000) {
        return 'Rp ' . round($price / 1000, 1) . ' rb';
    }
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function timeAgoIndonesia($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' menit lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam lalu';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' hari lalu';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' bulan lalu';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' tahun lalu';
    }
}

function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}

function maskPhone($phone) {
    if (strlen($phone) >= 8) {
        return substr($phone, 0, 4) . '-xxxx-' . substr($phone, -4);
    }
    return $phone;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn($url = '/login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . ltrim($url, '/'));
        exit;
    }
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function resizeImage($source, $dest, $maxWidth = 1200, $maxHeight = 1200, $quality = 85) {
    list($width, $height, $type) = getimagesize($source);
    if (!$width || !$height) return false;
    
    // Hitung dimensi baru
    $ratio = $width / $height;
    if ($maxWidth / $maxHeight > $ratio) {
        $newHeight = (int) $maxHeight;
        $newWidth = (int) ($maxHeight * $ratio);
    } else {
        $newWidth = (int) $maxWidth;
        $newHeight = (int) ($maxWidth / $ratio);
    }
    
    // Pastikan minimal 1 pixel
    $newWidth = max(1, $newWidth);
    $newHeight = max(1, $newHeight);
    
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($source);
            imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagejpeg($newImage, $dest, $quality);
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($source);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagepng($newImage, $dest, 9);
            break;
        case IMAGETYPE_WEBP:
            $srcImage = imagecreatefromwebp($source);
            imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagewebp($newImage, $dest, $quality);
            break;
        default:
            return false;
    }
    
    imagedestroy($newImage);
    imagedestroy($srcImage);
    return true;
}

function generatePagination($total, $currentPage, $perPage = 20) {
    $totalPages = ceil($total / $perPage);
    $html = '<nav class="flex items-center justify-center space-x-2 mt-8">';
    
    if ($currentPage > 1) {
        $html .= '<a href="?page=' . ($currentPage - 1) . '" class="px-4 py-2 border rounded-lg hover:bg-gray-50">&laquo;</a>';
    }
    
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<a href="?page=1" class="px-4 py-2 border rounded-lg hover:bg-gray-50">1</a>';
        if ($start > 2) $html .= '<span class="px-2">...</span>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $currentPage ? 'bg-[#002F34] text-white' : 'hover:bg-gray-50';
        $html .= '<a href="?page=' . $i . '" class="px-4 py-2 border rounded-lg ' . $active . '">' . $i . '</a>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<span class="px-2">...</span>';
        $html .= '<a href="?page=' . $totalPages . '" class="px-4 py-2 border rounded-lg hover:bg-gray-50">' . $totalPages . '</a>';
    }
    
    if ($currentPage < $totalPages) {
        $html .= '<a href="?page=' . ($currentPage + 1) . '" class="px-4 py-2 border rounded-lg hover:bg-gray-50">&raquo;</a>';
    }
    
    $html .= '</nav>';
    return $html;
}

// Update last seen
if (isLoggedIn()) {
    $stmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}
?>
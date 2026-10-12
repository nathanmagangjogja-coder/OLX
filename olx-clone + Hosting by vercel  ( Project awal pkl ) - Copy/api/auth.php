<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Ambil action dari GET atau POST
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Logout bisa via GET
if ($action === 'logout') {
    session_destroy();
    header('Location: ' . BASE_URL);
    exit;
}

// Cek sesi (GET)
if ($action === 'check_session') {
    echo json_encode(['logged_in' => isLoggedIn()]);
    exit;
}

// Untuk POST request, verifikasi CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid. Silakan refresh halaman.']);
        exit;
    }
}

switch ($action) {
    case 'login':
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email dan password harus diisi']);
            break;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];

            // Update last_seen
            $upd = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
            $upd->execute([$user['id']]);

            // Tentukan redirect berdasarkan role
            $redirect = $_POST['redirect'] ?? '';
            if ($user['role'] === 'admin') {
                $target = BASE_URL . 'admin/';
            } else {
                $target = !empty($redirect) ? $redirect : BASE_URL . 'dashboard/';
            }

            echo json_encode(['success' => true, 'redirect' => $target]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Email atau password salah']);
        }
        break;

    case 'register':
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $terms = isset($_POST['terms']);

        $errors = [];
        if (strlen($username) < 3) $errors[] = 'Username minimal 3 karakter';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
        if (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter';
        if ($password !== $confirm) $errors[] = 'Konfirmasi password tidak cocok';
        if (!$terms) $errors[] = 'Anda harus menyetujui syarat dan ketentuan';

        // Cek username/email sudah ada
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username atau email sudah terdaftar';
        }

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode('. ', $errors)]);
            break;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$username, $email, $hashed, $fullName, $phone]);

        $userId = $pdo->lastInsertId();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;

        echo json_encode(['success' => true, 'redirect' => BASE_URL]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali']);
}
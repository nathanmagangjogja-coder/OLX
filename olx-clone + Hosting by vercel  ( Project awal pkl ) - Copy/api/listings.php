<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Login dulu']); break; }
            if (!verify_csrf($_POST['csrf_token']??'')) { echo json_encode(['success'=>false,'message'=>'Token invalid']); break; }
            
            $userId = $_SESSION['user_id'];
            $catId = intval($_POST['category_id']??0);
            $subcatId = intval($_POST['subcategory_id']??0);
            $title = trim($_POST['title']??'');
            $desc = trim($_POST['description']??'');
            $price = intval($_POST['price']??0);
            $priceType = $_POST['price_type']??'fixed';
            $cond = $_POST['condition_type']??'used';
            $provId = intval($_POST['province_id']??0);
            $cityId = intval($_POST['city_id']??0);
            $address = trim($_POST['address']??'');
            $package = $_POST['package_type']??'free';
            
            $errors = [];
            if ($catId<=0) $errors[] = 'Kategori harus dipilih';
            if ($subcatId<=0) $errors[] = 'Subkategori harus dipilih';
            if (empty($title)) $errors[] = 'Judul harus diisi';
            if (strlen($title)>70) $errors[] = 'Judul maks 70 karakter';
            if (empty($desc)) $errors[] = 'Deskripsi harus diisi';
            if ($provId<=0 || $cityId<=0) $errors[] = 'Lokasi lengkap harus dipilih';
            if (!empty($errors)) { echo json_encode(['success'=>false,'message'=>implode(', ',$errors)]); break; }
            
            $slug = generateSlug($title); $base = $slug; $c=1;
            while(true){ $chk = $pdo->prepare("SELECT id FROM listings WHERE slug=?"); $chk->execute([$slug]); if(!$chk->fetch())break; $slug=$base.'-'.$c++; }
            $exp = date('Y-m-d H:i:s', strtotime('+60 days'));
            
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO listings (user_id,category_id,subcategory_id,title,slug,description,price,price_type,condition_type,province_id,city_id,address,status,package_type,published_at,expires_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'active',?,NOW(),?,NOW(),NOW())");
                $stmt->execute([$userId,$catId,$subcatId,$title,$slug,$desc,$price,$priceType,$cond,$provId,$cityId,$address,$package,$exp]);
                $listingId = $pdo->lastInsertId();
                
                if (!empty($_FILES['images']['tmp_name']) && is_array($_FILES['images']['tmp_name'])) {
                    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR,0755,true);
                    $sort=1;
                    foreach ($_FILES['images']['tmp_name'] as $k => $tmp) {
                        if ($_FILES['images']['error'][$k]!==UPLOAD_ERR_OK) continue;
                        $mime = mime_content_type($tmp);
                        if (!in_array($mime,['image/jpeg','image/png','image/webp'])) continue;
                        if ($_FILES['images']['size'][$k] > MAX_FILE_SIZE) continue;
                        $ext = pathinfo($_FILES['images']['name'][$k], PATHINFO_EXTENSION);
                        $fname = uniqid().'_'.time().'.'.$ext;
                        $dest = UPLOAD_DIR.$fname;
                        if (resizeImage($tmp,$dest,1200,1200,85)) {
                            $isPrimary = ($sort===1)?1:0;
                            $pdo->prepare("INSERT INTO listing_images (listing_id,filename,is_primary,sort_order) VALUES (?,?,?,?)")->execute([$listingId,$fname,$isPrimary,$sort]);
                            $sort++;
                        }
                    }
                }
                $pdo->prepare("UPDATE users SET total_listings=total_listings+1 WHERE id=?")->execute([$userId]);
                $pdo->commit();
                echo json_encode(['success'=>true,'listing_id'=>$listingId]);
            } catch(Exception $e) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
            break;

        case 'update':
            if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Login dulu']); break; }
            if (!verify_csrf($_POST['csrf_token']??'')) { echo json_encode(['success'=>false,'message'=>'Token invalid']); break; }
            $listingId = intval($_POST['listing_id']??0);
            $chk = $pdo->prepare("SELECT id FROM listings WHERE id=? AND user_id=?");
            $chk->execute([$listingId, $_SESSION['user_id']]);
            if (!$chk->fetch()) { echo json_encode(['success'=>false,'message'=>'Iklan tidak ditemukan']); break; }
            
            $catId = intval($_POST['category_id']??0);
            $subcatId = intval($_POST['subcategory_id']??0);
            $title = trim($_POST['title']??'');
            $desc = trim($_POST['description']??'');
            $price = intval($_POST['price']??0);
            $priceType = $_POST['price_type']??'fixed';
            $cond = $_POST['condition_type']??'used';
            $provId = intval($_POST['province_id']??0);
            $cityId = intval($_POST['city_id']??0);
            $address = trim($_POST['address']??'');
            $package = $_POST['package_type']??'free';
            
            $errors = [];
            if ($catId<=0) $errors[] = 'Kategori harus dipilih';
            if ($subcatId<=0) $errors[] = 'Subkategori harus dipilih';
            if (empty($title)) $errors[] = 'Judul harus diisi';
            if (empty($desc)) $errors[] = 'Deskripsi harus diisi';
            if ($provId<=0 || $cityId<=0) $errors[] = 'Lokasi lengkap harus dipilih';
            if (!empty($errors)) { echo json_encode(['success'=>false,'message'=>implode(', ',$errors)]); break; }
            
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE listings SET category_id=?, subcategory_id=?, title=?, description=?, price=?, price_type=?, condition_type=?, province_id=?, city_id=?, address=?, package_type=?, updated_at=NOW() WHERE id=? AND user_id=?");
                $stmt->execute([$catId,$subcatId,$title,$desc,$price,$priceType,$cond,$provId,$cityId,$address,$package,$listingId,$_SESSION['user_id']]);
                
                $kept = json_decode($_POST['kept_images']??'[]', true);
                if (!empty($kept)) {
                    $in = implode(',', array_map('intval',$kept));
                    $pdo->prepare("DELETE FROM listing_images WHERE listing_id=? AND id NOT IN ($in)")->execute([$listingId]);
                } else {
                    $pdo->prepare("DELETE FROM listing_images WHERE listing_id=?")->execute([$listingId]);
                }
                
                if (!empty($_FILES['images']['tmp_name']) && is_array($_FILES['images']['tmp_name'])) {
                    $max = $pdo->prepare("SELECT MAX(sort_order) FROM listing_images WHERE listing_id=?");
                    $max->execute([$listingId]);
                    $sort = ($max->fetchColumn()?:0)+1;
                    foreach ($_FILES['images']['tmp_name'] as $k => $tmp) {
                        if ($_FILES['images']['error'][$k]!==UPLOAD_ERR_OK) continue;
                        $mime = mime_content_type($tmp);
                        if (!in_array($mime,['image/jpeg','image/png','image/webp'])) continue;
                        if ($_FILES['images']['size'][$k] > MAX_FILE_SIZE) continue;
                        $ext = pathinfo($_FILES['images']['name'][$k], PATHINFO_EXTENSION);
                        $fname = uniqid().'_'.time().'.'.$ext;
                        $dest = UPLOAD_DIR.$fname;
                        if (resizeImage($tmp,$dest,1200,1200,85)) {
                            $pdo->prepare("INSERT INTO listing_images (listing_id,filename,is_primary,sort_order) VALUES (?,?,0,?)")->execute([$listingId,$fname,$sort]);
                            $sort++;
                        }
                    }
                }
                // pastikan ada primary
                $prim = $pdo->prepare("SELECT id FROM listing_images WHERE listing_id=? AND is_primary=1");
                $prim->execute([$listingId]);
                if (!$prim->fetch()) {
                    $first = $pdo->prepare("SELECT id FROM listing_images WHERE listing_id=? ORDER BY sort_order LIMIT 1");
                    $first->execute([$listingId]);
                    if ($fid = $first->fetchColumn()) {
                        $pdo->prepare("UPDATE listing_images SET is_primary=1 WHERE id=?")->execute([$fid]);
                    }
                }
                $pdo->commit();
                echo json_encode(['success'=>true,'listing_id'=>$listingId]);
            } catch(Exception $e) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
            break;

        case 'mark_sold':
            if (!isLoggedIn()) { echo json_encode(['success'=>false]); break; }
            if (!verify_csrf($_POST['csrf_token']??'')) { echo json_encode(['success'=>false]); break; }
            $id = intval($_POST['listing_id']??0);
            $stmt = $pdo->prepare("UPDATE listings SET status='sold' WHERE id=? AND user_id=?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            echo json_encode(['success'=>$stmt->rowCount()>0]);
            break;

        case 'delete':
            if (!isLoggedIn()) { echo json_encode(['success'=>false]); break; }
            if (!verify_csrf($_POST['csrf_token']??'')) { echo json_encode(['success'=>false]); break; }
            $id = intval($_POST['listing_id']??0);
            $stmt = $pdo->prepare("DELETE FROM listings WHERE id=? AND user_id=?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            echo json_encode(['success'=>$stmt->rowCount()>0]);
            break;

        // ... case get_all, get_by_id, search, increment_view, track_phone, get_similar tetap sama seperti sebelumnya ...
        default:
            http_response_code(400);
            echo json_encode(['error'=>'Invalid action']);
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}
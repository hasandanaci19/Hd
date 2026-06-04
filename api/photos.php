<?php
/**
 * Fotoğraf Yükleme API
 */

require_once __DIR__ . '/../config/database.php';

class PhotoManager {

    /**
     * Fotoğraf yükle
     */
    public function uploadPhoto($tripId, $userId, $file, $caption = '', $routeId = null) {
        global $pdo;

        // Dosya doğrulaması
        if (!isset($file['tmp_name']) || !isset($file['name'])) {
            return ['success' => false, 'error' => 'Geçersiz dosya'];
        }

        // Dosya boyutu kontrolü
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return ['success' => false, 'error' => 'Dosya çok büyük (maksimum ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB)'];
        }

        // Dosya türü kontrolü
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Sadece resim dosyaları yüklenebilir'];
        }

        // Klasör oluştur
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        // Benzersiz dosya adı oluştur
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'trip_' . $tripId . '_' . time() . '_' . uniqid() . '.' . $extension;
        $filePath = UPLOAD_DIR . $fileName;

        // Dosyayı taşı
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'error' => 'Dosya yüklenirken hata oluştu'];
        }

        // Resmi veritabanına kaydet
        $stmt = $pdo->prepare("
            INSERT INTO trip_photos (trip_id, user_id, image_path, image_url, caption, route_id, taken_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $imageUrl = APP_URL . '/uploads/' . $fileName;
        $stmt->execute([$tripId, $userId, $filePath, $imageUrl, $caption, $routeId]);

        return [
            'success' => true,
            'data' => [
                'id' => $pdo->lastInsertId(),
                'image_url' => $imageUrl,
                'message' => 'Fotoğraf başarıyla yüklendi'
            ]
        ];
    }

    /**
     * Gezi fotoğraflarını listele
     */
    public function getTripPhotos($tripId) {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT tp.*, u.username, u.profile_image
            FROM trip_photos tp
            JOIN users u ON tp.user_id = u.id
            WHERE tp.trip_id = ?
            ORDER BY tp.uploaded_at DESC
        ");
        $stmt->execute([$tripId]);

        return [
            'success' => true,
            'data' => $stmt->fetchAll()
        ];
    }

    /**
     * Fotoğraf beğen
     */
    public function likePhoto($photoId) {
        global $pdo;

        $stmt = $pdo->prepare("
            UPDATE trip_photos
            SET likes_count = likes_count + 1
            WHERE id = ?
        ");
        $stmt->execute([$photoId]);

        return ['success' => true, 'message' => 'Fotoğraf beğenildi'];
    }

    /**
     * Fotoğraf sil
     */
    public function deletePhoto($photoId, $userId) {
        global $pdo;

        $stmt = $pdo->prepare("SELECT * FROM trip_photos WHERE id = ?");
        $stmt->execute([$photoId]);
        $photo = $stmt->fetch();

        if (!$photo) {
            return ['success' => false, 'error' => 'Fotoğraf bulunamadı'];
        }

        if ($photo['user_id'] != $userId) {
            return ['success' => false, 'error' => 'Yalnızca kendi fotoğraflarınızı silebilirsiniz'];
        }

        // Dosyayı sil
        if (file_exists($photo['image_path'])) {
            unlink($photo['image_path']);
        }

        // Veritabanından sil
        $stmt = $pdo->prepare("DELETE FROM trip_photos WHERE id = ?");
        $stmt->execute([$photoId]);

        return ['success' => true, 'message' => 'Fotoğraf silindi'];
    }
}

// API Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $photoManager = new PhotoManager();

    if ($action === 'upload') {
        $tripId = $_POST['trip_id'] ?? null;
        $userId = $_POST['user_id'] ?? null;
        $caption = $_POST['caption'] ?? '';
        $routeId = $_POST['route_id'] ?? null;

        if (!$tripId || !$userId || !isset($_FILES['image'])) {
            echo json_encode(['success' => false, 'error' => 'Gerekli parametreler eksik']);
            exit;
        }

        echo json_encode($photoManager->uploadPhoto($tripId, $userId, $_FILES['image'], $caption, $routeId));

    } elseif ($action === 'like') {
        $photoId = $_POST['photo_id'] ?? null;
        if (!$photoId) {
            echo json_encode(['success' => false, 'error' => 'Photo ID gerekli']);
            exit;
        }
        echo json_encode($photoManager->likePhoto($photoId));

    } elseif ($action === 'delete') {
        $photoId = $_POST['photo_id'] ?? null;
        $userId = $_POST['user_id'] ?? null;
        if (!$photoId || !$userId) {
            echo json_encode(['success' => false, 'error' => 'Photo ID ve User ID gerekli']);
            exit;
        }
        echo json_encode($photoManager->deletePhoto($photoId, $userId));
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? null;
    $photoManager = new PhotoManager();

    if ($action === 'list' && isset($_GET['trip_id'])) {
        echo json_encode($photoManager->getTripPhotos($_GET['trip_id']));
    }
}
?>
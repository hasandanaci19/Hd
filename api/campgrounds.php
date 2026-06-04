<?php
/**
 * Kamp Alanları API
 */

require_once __DIR__ . '/../config/database.php';

class CampgroundService {

    /**
     * Tüm kamp alanlarını listele
     */
    public function getAllCampgrounds($filters = []) {
        global $pdo;

        $query = "SELECT * FROM campgrounds WHERE 1=1";
        $params = [];

        if (!empty($filters['is_caravan_friendly'])) {
            $query .= " AND is_caravan_friendly = 1";
        }

        if (!empty($filters['is_family_friendly'])) {
            $query .= " AND is_family_friendly = 1";
        }

        if (!empty($filters['has_wifi'])) {
            $query .= " AND has_wifi = 1";
        }

        if (!empty($filters['min_rating'])) {
            $query .= " AND rating >= ?";
            $params[] = $filters['min_rating'];
        }

        if (!empty($filters['max_price'])) {
            $query .= " AND price_per_night <= ?";
            $params[] = $filters['max_price'];
        }

        $query .= " ORDER BY rating DESC LIMIT 50";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        return [
            'success' => true,
            'data' => $stmt->fetchAll()
        ];
    }

    /**
     * Konuma yakın kamp alanlarını bul
     */
    public function getNearestCampgrounds($latitude, $longitude, $radiusKm = 50) {
        global $pdo;

        $query = "
            SELECT *,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            FROM campgrounds
            HAVING distance < ?
            ORDER BY distance ASC
            LIMIT 20
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$latitude, $longitude, $latitude, $radiusKm]);

        return [
            'success' => true,
            'data' => $stmt->fetchAll()
        ];
    }

    /**
     * Kamp alanı detayları
     */
    public function getCampgroundDetails($campgroundId) {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT c.*, 
            ROUND(AVG(cr.rating), 2) as avg_rating,
            COUNT(cr.id) as total_reviews
            FROM campgrounds c
            LEFT JOIN campground_reviews cr ON c.id = cr.campground_id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$campgroundId]);
        $campground = $stmt->fetch();

        if (!$campground) {
            return [
                'success' => false,
                'error' => 'Kamp alanı bulunamadı'
            ];
        }

        // Yorumları al
        $stmt = $pdo->prepare("
            SELECT cr.*, u.username, u.profile_image
            FROM campground_reviews cr
            JOIN users u ON cr.user_id = u.id
            WHERE cr.campground_id = ?
            ORDER BY cr.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$campgroundId]);
        $reviews = $stmt->fetchAll();

        return [
            'success' => true,
            'campground' => $campground,
            'reviews' => $reviews
        ];
    }

    /**
     * Yorum ekle
     */
    public function addReview($campgroundId, $userId, $rating, $comment, $data = []) {
        global $pdo;

        $stmt = $pdo->prepare("
            INSERT INTO campground_reviews (
                campground_id, user_id, rating, title, comment,
                cleanliness_rating, service_rating, value_rating,
                is_caravan_friendly_review, is_family_friendly_review
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $campgroundId,
            $userId,
            $rating,
            $data['title'] ?? 'Yorum',
            $comment,
            $data['cleanliness_rating'] ?? null,
            $data['service_rating'] ?? null,
            $data['value_rating'] ?? null,
            $data['is_caravan_friendly_review'] ?? 0,
            $data['is_family_friendly_review'] ?? 0
        ]);

        // Kamp alanının ortalama puanını güncelle
        $this->updateCampgroundRating($campgroundId);

        return [
            'success' => true,
            'message' => 'Yorum başarıyla eklendi'
        ];
    }

    /**
     * Kamp alanı puanını güncelle
     */
    private function updateCampgroundRating($campgroundId) {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT ROUND(AVG(rating), 2) as avg_rating, COUNT(*) as count
            FROM campground_reviews
            WHERE campground_id = ?
        ");
        $stmt->execute([$campgroundId]);
        $result = $stmt->fetch();

        $stmt = $pdo->prepare("
            UPDATE campgrounds
            SET rating = ?, review_count = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $result['avg_rating'] ?? 0,
            $result['count'] ?? 0,
            $campgroundId
        ]);
    }

    /**
     * Aile dostu kamp alanları
     */
    public function getFamilyFriendlyCampgrounds($filters = []) {
        $filters['is_family_friendly'] = true;
        return $this->getAllCampgrounds($filters);
    }

    /**
     * Karavan uyumlu kamp alanları
     */
    public function getCaravanFriendlyCampgrounds($filters = []) {
        $filters['is_caravan_friendly'] = true;
        return $this->getAllCampgrounds($filters);
    }

    /**
     * Kamp alanı ekle (Admin)
     */
    public function addCampground($data) {
        global $pdo;

        $stmt = $pdo->prepare("
            INSERT INTO campgrounds (
                name, description, latitude, longitude, address, phone,
                website, email, price_per_night, has_electricity,
                has_water, has_wifi, is_caravan_friendly, is_family_friendly,
                max_capacity, image_url
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['latitude'],
            $data['longitude'],
            $data['address'] ?? null,
            $data['phone'] ?? null,
            $data['website'] ?? null,
            $data['email'] ?? null,
            $data['price_per_night'] ?? null,
            $data['has_electricity'] ?? 0,
            $data['has_water'] ?? 0,
            $data['has_wifi'] ?? 0,
            $data['is_caravan_friendly'] ?? 0,
            $data['is_family_friendly'] ?? 0,
            $data['max_capacity'] ?? null,
            $data['image_url'] ?? null
        ]);

        return [
            'success' => true,
            'id' => $pdo->lastInsertId()
        ];
    }
}

// API Endpoint
$campground = new CampgroundService();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? null;

    if ($action === 'list') {
        $filters = [
            'is_caravan_friendly' => $_GET['caravan_friendly'] ?? false,
            'is_family_friendly' => $_GET['family_friendly'] ?? false,
            'has_wifi' => $_GET['has_wifi'] ?? false,
            'min_rating' => $_GET['min_rating'] ?? null,
            'max_price' => $_GET['max_price'] ?? null
        ];
        echo json_encode($campground->getAllCampgrounds($filters));

    } elseif ($action === 'nearest') {
        $lat = $_GET['lat'] ?? null;
        $lon = $_GET['lon'] ?? null;
        $radius = $_GET['radius'] ?? 50;

        if (!$lat || !$lon) {
            echo json_encode(['success' => false, 'error' => 'Koordinatlar gerekli']);
            exit;
        }

        echo json_encode($campground->getNearestCampgrounds($lat, $lon, $radius));

    } elseif ($action === 'details') {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID gerekli']);
            exit;
        }

        echo json_encode($campground->getCampgroundDetails($id));

    } elseif ($action === 'family_friendly') {
        echo json_encode($campground->getFamilyFriendlyCampgrounds());

    } elseif ($action === 'caravan_friendly') {
        echo json_encode($campground->getCaravanFriendlyCampgrounds());
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;

    if ($action === 'add_review') {
        echo json_encode($campground->addReview(
            $data['campground_id'],
            $data['user_id'],
            $data['rating'],
            $data['comment'],
            $data
        ));
    }
}
?>

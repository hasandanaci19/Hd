<?php
/**
 * Uygunluk Puanlama Sistemi
 */

require_once __DIR__ . '/../config/database.php';

class SuitabilityScorer {

    /**
     * Tüm uygunluk puanlarını hesapla
     */
    public function calculateAllScores($tripId) {
        global $pdo;

        $trip = $this->getTrip($tripId);
        if (!$trip) {
            return ['success' => false, 'error' => 'Gezi bulunamadı'];
        }

        $familyScore = $this->calculateFamilyFriendlinessScore($tripId, $trip);
        $caravanScore = $this->calculateCaravanSuitabilityScore($tripId, $trip);
        $scenicScore = $this->calculateScenicRouteScore($tripId);
        $budgetScore = $this->calculateBudgetEfficiencyScore($tripId, $trip);

        $overallScore = ($familyScore + $caravanScore + $scenicScore + $budgetScore) / 4;

        $this->saveScores($tripId, [
            'family_friendliness_score' => $familyScore,
            'caravan_suitability_score' => $caravanScore,
            'scenic_route_score' => $scenicScore,
            'budget_efficiency_score' => $budgetScore,
            'overall_score' => $overallScore
        ]);

        return [
            'success' => true,
            'data' => [
                'family_friendliness_score' => $familyScore,
                'caravan_suitability_score' => $caravanScore,
                'scenic_route_score' => $scenicScore,
                'budget_efficiency_score' => $budgetScore,
                'overall_score' => round($overallScore, 2)
            ]
        ];
    }

    /**
     * Çocuklu aile uygunluğu puanla
     */
    private function calculateFamilyFriendlinessScore($tripId, $trip) {
        global $pdo;

        $score = 50;

        // Çocuk varsa başlangıç puanı
        if ($trip['has_children']) {
            $score = 60;
        }

        // Aile dostu kamp alanlarının yüzdesini kontrol et
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as family_friendly_camps
            FROM campgrounds
            WHERE is_family_friendly = 1
            AND latitude BETWEEN ? AND ?
            AND longitude BETWEEN ? AND ?
        ");
        $stmt->execute([
            min($trip['start_latitude'], $trip['end_latitude']) - 0.5,
            max($trip['start_latitude'], $trip['end_latitude']) + 0.5,
            min($trip['start_longitude'], $trip['end_longitude']) - 0.5,
            max($trip['start_longitude'], $trip['end_longitude']) + 0.5
        ]);
        $result = $stmt->fetch();
        if ($result['family_friendly_camps'] > 5) {
            $score += 20;
        }

        // Gezi süresi (çok kısa veya çok uzun uygun değil)
        $startDate = new DateTime($trip['start_date']);
        $endDate = new DateTime($trip['end_date']);
        $days = $endDate->diff($startDate)->days;
        if ($days > 2 && $days < 14) {
            $score += 15;
        }

        return min($score, 100);
    }

    /**
     * Karavan uygunluğu puanla
     */
    private function calculateCaravanSuitabilityScore($tripId, $trip) {
        global $pdo;

        $score = 50;

        // Araç tipi karavan ise
        if ($trip['vehicle_type'] === 'caravan') {
            $score = 70;

            // Karavan dostu kamp alanlarını kontrol et
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as caravan_camps
                FROM campgrounds
                WHERE is_caravan_friendly = 1
                AND has_water = 1
                AND has_electricity = 1
                AND latitude BETWEEN ? AND ?
                AND longitude BETWEEN ? AND ?
            ");
            $stmt->execute([
                min($trip['start_latitude'], $trip['end_latitude']) - 0.5,
                max($trip['start_latitude'], $trip['end_latitude']) + 0.5,
                min($trip['start_longitude'], $trip['end_longitude']) - 0.5,
                max($trip['start_longitude'], $trip['end_longitude']) + 0.5
            ]);
            $result = $stmt->fetch();
            if ($result['caravan_camps'] > 3) {
                $score += 25;
            }
        }

        // Mesafe kontrol et (çok uzun karavan yolculuğu zor)
        if ($trip['distance_km'] && $trip['distance_km'] < 500) {
            $score += 5;
        }

        return min($score, 100);
    }

    /**
     * Scenic (manzaralı) rota puanı
     */
    private function calculateScenicRouteScore($tripId) {
        global $pdo;

        $score = 50;

        // Rota üzerinde kaç durak noktası var?
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as stops FROM trip_routes
            WHERE trip_id = ?
        ");
        $stmt->execute([$tripId]);
        $result = $stmt->fetch();
        if ($result['stops'] > 5) {
            $score += 30;
        }

        return min($score, 100);
    }

    /**
     * Bütçe verimliliği puanı
     */
    private function calculateBudgetEfficiencyScore($tripId, $trip) {
        global $pdo;

        $score = 50;

        // Yakıt maliyeti hesapla
        $stmt = $pdo->prepare("
            SELECT SUM(total_cost) as total_fuel_cost FROM fuel_logs
            WHERE trip_id = ?
        ");
        $stmt->execute([$tripId]);
        $fuelResult = $stmt->fetch();
        $fuelCost = $fuelResult['total_fuel_cost'] ?? 0;

        // Bütçe ile karşılaştır
        if ($trip['budget'] && $fuelCost < ($trip['budget'] * 0.2)) {
            $score += 30;
        } elseif ($trip['budget'] && $fuelCost < ($trip['budget'] * 0.5)) {
            $score += 15;
        }

        // Aile dostu ucuz kamp alanları
        if ($trip['has_children']) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as cheap_family_camps FROM campgrounds
                WHERE is_family_friendly = 1
                AND price_per_night < 500
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            if ($result['cheap_family_camps'] > 3) {
                $score += 10;
            }
        }

        return min($score, 100);
    }

    /**
     * Gezi bilgisi al
     */
    private function getTrip($tripId) {
        global $pdo;

        $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
        $stmt->execute([$tripId]);
        return $stmt->fetch();
    }

    /**
     * Puanları veritabanına kaydet
     */
    private function saveScores($tripId, $scores) {
        global $pdo;

        $stmt = $pdo->prepare("
            INSERT INTO suitability_scores (trip_id, family_friendliness_score, caravan_suitability_score, scenic_route_score, budget_efficiency_score, overall_score)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            family_friendliness_score = ?, caravan_suitability_score = ?, scenic_route_score = ?, budget_efficiency_score = ?, overall_score = ?
        ");

        $stmt->execute([
            $tripId,
            $scores['family_friendliness_score'],
            $scores['caravan_suitability_score'],
            $scores['scenic_route_score'],
            $scores['budget_efficiency_score'],
            $scores['overall_score'],
            $scores['family_friendliness_score'],
            $scores['caravan_suitability_score'],
            $scores['scenic_route_score'],
            $scores['budget_efficiency_score'],
            $scores['overall_score']
        ]);
    }

    /**
     * Uygunluk puanlarını getir
     */
    public function getScores($tripId) {
        global $pdo;

        $stmt = $pdo->prepare("SELECT * FROM suitability_scores WHERE trip_id = ?");
        $stmt->execute([$tripId]);
        $scores = $stmt->fetch();

        if (!$scores) {
            // Puanları hesapla ve kaydet
            return $this->calculateAllScores($tripId);
        }

        return [
            'success' => true,
            'data' => $scores
        ];
    }
}

// API Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;

    $scorer = new SuitabilityScorer();

    if ($action === 'calculate') {
        $tripId = $data['trip_id'] ?? null;
        if (!$tripId) {
            echo json_encode(['success' => false, 'error' => 'Trip ID gerekli']);
            exit;
        }
        echo json_encode($scorer->calculateAllScores($tripId));
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? null;
    $scorer = new SuitabilityScorer();

    if ($action === 'get' && isset($_GET['trip_id'])) {
        echo json_encode($scorer->getScores($_GET['trip_id']));
    }
}
?>
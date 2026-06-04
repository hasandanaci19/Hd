<?php
/**
 * Yakıt Maliyeti Hesaplayıcısı API
 */

require_once __DIR__ . '/../config/database.php';

class FuelCalculator {
    
    // Araç tipine göre yakıt tüketimi (L/100km)
    private $fuelConsumption = [
        'car' => 8,
        'caravan' => 15,
        'motorcycle' => 4,
        'bike' => 0
    ];

    /**
     * Yakıt maliyeti hesapla
     */
    public function calculateFuel($distance, $vehicleType, $fuelPrice, $tripId = null) {
        if (!isset($this->fuelConsumption[$vehicleType])) {
            return [
                'success' => false,
                'error' => 'Geçersiz araç tipi'
            ];
        }

        $consumption = $this->fuelConsumption[$vehicleType];
        $fuelNeeded = ($distance / 100) * $consumption;
        $totalCost = $fuelNeeded * $fuelPrice;

        $result = [
            'success' => true,
            'data' => [
                'distance_km' => $distance,
                'vehicle_type' => $vehicleType,
                'fuel_consumption_per_100km' => $consumption,
                'fuel_needed_liters' => round($fuelNeeded, 2),
                'fuel_price_per_liter' => $fuelPrice,
                'total_cost' => round($totalCost, 2),
                'cost_per_km' => round($totalCost / $distance, 3)
            ]
        ];

        if ($tripId) {
            $this->saveFuelLog($tripId, $distance, $vehicleType, $fuelNeeded, $fuelPrice, $totalCost);
        }

        return $result;
    }

    /**
     * Tüm gezi rotası için yakıt maliyeti hesapla
     */
    public function calculateTripFuel($tripId) {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT tr.distance_from_previous, t.vehicle_type
            FROM trip_routes tr
            JOIN trips t ON tr.trip_id = t.id
            WHERE tr.trip_id = ? AND tr.distance_from_previous IS NOT NULL
        ");
        $stmt->execute([$tripId]);
        $routes = $stmt->fetchAll();

        if (empty($routes)) {
            return [
                'success' => false,
                'error' => 'Gezi rotası bulunamadı'
            ];
        }

        $totalDistance = 0;
        $vehicleType = $routes[0]['vehicle_type'];

        foreach ($routes as $route) {
            $totalDistance += $route['distance_from_previous'];
        }

        // Yakıt fiyatı varsayılan (gerçek uygulamada API'den alınabilir)
        $fuelPrice = 30; // TL/L

        return $this->calculateFuel($totalDistance, $vehicleType, $fuelPrice, $tripId);
    }

    /**
     * Yakıt maliyetini veritabanına kaydet
     */
    private function saveFuelLog($tripId, $distance, $vehicleType, $fuelNeeded, $fuelPrice, $totalCost) {
        global $pdo;

        $stmt = $pdo->prepare("
            INSERT INTO fuel_logs (trip_id, vehicle_type, distance_km, fuel_consumption_liter, fuel_price_per_liter, total_cost)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([$tripId, $vehicleType, $distance, $fuelNeeded, $fuelPrice, $totalCost]);
    }

    /**
     * Gezi için yakıt maliyeti geçmişini al
     */
    public function getTripFuelHistory($tripId) {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT * FROM fuel_logs
            WHERE trip_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$tripId]);

        return [
            'success' => true,
            'data' => $stmt->fetchAll()
        ];
    }

    /**
     * Karşılaştırmalı yakıt analizi
     */
    public function compareFuelCosts($distance, $fuelPrice) {
        $comparison = [];

        foreach ($this->fuelConsumption as $type => $consumption) {
            if ($type === 'bike') continue;

            $fuelNeeded = ($distance / 100) * $consumption;
            $cost = $fuelNeeded * $fuelPrice;

            $comparison[] = [
                'vehicle_type' => $type,
                'fuel_consumption_per_100km' => $consumption,
                'fuel_needed_liters' => round($fuelNeeded, 2),
                'total_cost' => round($cost, 2),
                'cost_per_km' => round($cost / $distance, 3)
            ];
        }

        usort($comparison, function($a, $b) {
            return $a['total_cost'] <=> $b['total_cost'];
        });

        return [
            'success' => true,
            'data' => $comparison
        ];
    }
}

// API Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;

    $fuel = new FuelCalculator();

    if ($action === 'calculate') {
        $distance = $data['distance'] ?? null;
        $vehicleType = $data['vehicle_type'] ?? null;
        $fuelPrice = $data['fuel_price'] ?? 30;
        $tripId = $data['trip_id'] ?? null;

        if (!$distance || !$vehicleType) {
            echo json_encode(['success' => false, 'error' => 'Mesafe ve araç tipi gerekli']);
            exit;
        }

        echo json_encode($fuel->calculateFuel($distance, $vehicleType, $fuelPrice, $tripId));

    } elseif ($action === 'compare') {
        $distance = $data['distance'] ?? null;
        $fuelPrice = $data['fuel_price'] ?? 30;

        if (!$distance) {
            echo json_encode(['success' => false, 'error' => 'Mesafe gerekli']);
            exit;
        }

        echo json_encode($fuel->compareFuelCosts($distance, $fuelPrice));

    } elseif ($action === 'trip_history') {
        $tripId = $data['trip_id'] ?? null;

        if (!$tripId) {
            echo json_encode(['success' => false, 'error' => 'Gezi ID gerekli']);
            exit;
        }

        echo json_encode($fuel->getTripFuelHistory($tripId));
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? null;
    $fuel = new FuelCalculator();

    if ($action === 'trip_total') {
        $tripId = $_GET['trip_id'] ?? null;

        if (!$tripId) {
            echo json_encode(['success' => false, 'error' => 'Gezi ID gerekli']);
            exit;
        }

        echo json_encode($fuel->calculateTripFuel($tripId));
    }
}
?>

<?php
/**
 * AI Rota Önerici - OpenAI GPT Entegrasyonu
 */

require_once __DIR__ . '/../config/database.php';

class AIRouteAdvisor {
    private $apiKey;
    private $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct() {
        $this->apiKey = OPENAI_API_KEY;
    }

    /**
     * Rota önerisi al
     */
    public function getSuggestions($tripData) {
        $prompt = $this->buildPrompt($tripData);
        $response = $this->callOpenAI($prompt);

        if ($response) {
            return [
                'success' => true,
                'data' => $response
            ];
        }

        return [
            'success' => false,
            'error' => 'Rota önerisi alınamadı'
        ];
    }

    /**
     * Prompt oluştur
     */
    private function buildPrompt($tripData) {
        $vehicleType = $tripData['vehicle_type'] ?? 'car';
        $hasChildren = $tripData['has_children'] ?? false;
        $startPoint = $tripData['start_point'] ?? '';
        $endPoint = $tripData['end_point'] ?? '';
        $distance = $tripData['distance'] ?? '';
        $duration = $tripData['duration'] ?? '';

        $familyInfo = $hasChildren ? 'Çocuklara uygun yerler ve dinlenme noktalarını öner.' : '';
        $vehicleInfo = '';

        if ($vehicleType === 'caravan') {
            $vehicleInfo = 'Karavan için uygun yolları ve hizmetleri öner.';
        } elseif ($vehicleType === 'motorcycle') {
            $vehicleInfo = 'Motosiklet için scenic ve eğlenceli rotalar öner.';
        }

        $prompt = "
        Aşağıdaki gezi için optimal bir rota öner:
        
        Başlangıç: {$startPoint}
        Bitiş: {$endPoint}
        Mesafe: {$distance} km
        Tahmini Süre: {$duration} saat
        Araç Tipi: {$vehicleType}
        
        {$vehicleInfo}
        {$familyInfo}
        
        Lütfen aşağıdaki bilgileri JSON formatında sağla:
        {
            \"waypoints\": [
                {
                    \"name\": \"Yer adı\",
                    \"description\": \"Kısa açıklama\",
                    \"stopDuration\": \"Dakika\",
                    \"highlights\": [\"Öne çıkan 1\", \"Öne çıkan 2\"]
                }
            ],
            \"totalDistance\": \"km\",
            \"estimatedTime\": \"Saat\",
            \"tips\": [\"İpucu 1\", \"İpucu 2\"],
            \"bestTime\": \"Ziyaret etmek için en iyi zaman\",
            \"warnings\": [\"Uyarı 1\"]
        }
        ";

        return trim($prompt);
    }

    /**
     * OpenAI API'sini çağır
     */
    private function callOpenAI($prompt) {
        $payload = json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sen bir gezi planı uzmanısın. Türkçe cevaplar ver. JSON formatında yanıt ver.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000
        ]);

        $ch = curl_init($this->baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);

        if (isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];
            
            // JSON kısmını çıkar
            preg_match('/\{.*\}/s', $content, $matches);
            if ($matches) {
                return json_decode($matches[0], true);
            }
        }

        return null;
    }

    /**
     * Rota tavsiyesini veritabanına kaydet
     */
    public function saveRouteSuggestion($tripId, $suggestionType, $data) {
        global $pdo;

        $stmt = $pdo->prepare("
            INSERT INTO ai_route_suggestions (trip_id, suggestion_type, waypoints, total_distance, estimated_duration_hours, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $tripId,
            $suggestionType,
            json_encode($data['waypoints'] ?? []),
            $data['totalDistance'] ?? null,
            $data['estimatedTime'] ?? null,
            json_encode($data['tips'] ?? [])
        ]);

        return $pdo->lastInsertId();
    }

    /**
     * Gezi için tüm rota önerilerini al
     */
    public function getRouteSuggestions($tripId) {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT * FROM ai_route_suggestions
            WHERE trip_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$tripId]);

        return [
            'success' => true,
            'data' => $stmt->fetchAll()
        ];
    }
}

// API Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;

    $advisor = new AIRouteAdvisor();

    if ($action === 'get_suggestions') {
        echo json_encode($advisor->getSuggestions($data));
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? null;
    $advisor = new AIRouteAdvisor();

    if ($action === 'trip_suggestions') {
        $tripId = $_GET['trip_id'] ?? null;
        if (!$tripId) {
            echo json_encode(['success' => false, 'error' => 'Trip ID gerekli']);
            exit;
        }
        echo json_encode($advisor->getRouteSuggestions($tripId));
    }
}
?>
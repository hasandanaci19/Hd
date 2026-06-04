<?php
/**
 * Hava Durumu API - OpenWeatherMap Entegrasyonu
 */

require_once __DIR__ . '/../config/database.php';

class WeatherService {
    private $apiKey;
    private $baseUrl = 'https://api.openweathermap.org/data/2.5';

    public function __construct() {
        $this->apiKey = OPENWEATHER_API_KEY;
    }

    /**
     * Konum için 5 günlük hava durumu tahmini al
     */
    public function getForecast($latitude, $longitude, $tripId = null) {
        $url = "{$this->baseUrl}/forecast?lat={$latitude}&lon={$longitude}&appid={$this->apiKey}&units=metric&lang=tr";

        $response = $this->makeRequest($url);

        if ($response && isset($response['list'])) {
            $forecast = $this->processForecast($response['list']);
            
            if ($tripId) {
                $this->saveForecast($tripId, $latitude, $longitude, $forecast);
            }

            return [
                'success' => true,
                'data' => $forecast
            ];
        }

        return [
            'success' => false,
            'error' => 'Hava durumu verisi alınamadı'
        ];
    }

    /**
     * Mevcut hava durumunu al
     */
    public function getCurrentWeather($latitude, $longitude) {
        $url = "{$this->baseUrl}/weather?lat={$latitude}&lon={$longitude}&appid={$this->apiKey}&units=metric&lang=tr";

        $response = $this->makeRequest($url);

        if ($response) {
            return [
                'success' => true,
                'data' => [
                    'temperature' => $response['main']['temp'],
                    'feels_like' => $response['main']['feels_like'],
                    'humidity' => $response['main']['humidity'],
                    'pressure' => $response['main']['pressure'],
                    'condition' => $response['weather'][0]['main'],
                    'description' => $response['weather'][0]['description'],
                    'wind_speed' => $response['wind']['speed'],
                    'wind_deg' => $response['wind']['deg'],
                    'clouds' => $response['clouds']['all'],
                    'visibility' => $response['visibility'] ?? null,
                    'rain' => $response['rain']['1h'] ?? 0,
                    'location' => $response['name'] . ', ' . $response['sys']['country']
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Hava durumu verisi alınamadı'
        ];
    }

    /**
     * Tahmin verilerini işle
     */
    private function processForecast($list) {
        $forecast = [];
        $processed_dates = [];

        foreach ($list as $item) {
            $date = date('Y-m-d', $item['dt']);

            if (!isset($processed_dates[$date])) {
                $processed_dates[$date] = [
                    'date' => $date,
                    'temps' => [],
                    'conditions' => [],
                    'humidity' => [],
                    'wind_speed' => [],
                    'rain_chance' => $item['pop'] * 100
                ];
            }

            $processed_dates[$date]['temps'][] = $item['main']['temp'];
            $processed_dates[$date]['humidity'][] = $item['main']['humidity'];
            $processed_dates[$date]['wind_speed'][] = $item['wind']['speed'];
            $processed_dates[$date]['conditions'][] = $item['weather'][0]['main'];
        }

        foreach ($processed_dates as $date => $data) {
            $forecast[] = [
                'date' => $date,
                'temp_min' => min($data['temps']),
                'temp_max' => max($data['temps']),
                'temp_avg' => round(array_sum($data['temps']) / count($data['temps']), 1),
                'humidity' => round(array_sum($data['humidity']) / count($data['humidity'])),
                'wind_speed' => round(array_sum($data['wind_speed']) / count($data['wind_speed']), 1),
                'condition' => $this->getMostCommonElement($data['conditions']),
                'precipitation_chance' => round($data['rain_chance'])
            ];
        }

        return $forecast;
    }

    /**
     * En sık görülen değer
     */
    private function getMostCommonElement($array) {
        $counts = array_count_values($array);
        return key($counts);
    }

    /**
     * Hava durumu tahminini veritabanına kaydet
     */
    private function saveForecast($tripId, $latitude, $longitude, $forecast) {
        global $pdo;

        foreach ($forecast as $day) {
            $stmt = $pdo->prepare("
                INSERT INTO weather_data (trip_id, latitude, longitude, date, temp_min, temp_max, temp_avg, condition, humidity, wind_speed, precipitation_chance)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                temp_min = ?, temp_max = ?, temp_avg = ?, condition = ?, humidity = ?, wind_speed = ?, precipitation_chance = ?
            ");

            $stmt->execute([
                $tripId, $latitude, $longitude, $day['date'],
                $day['temp_min'], $day['temp_max'], $day['temp_avg'],
                $day['condition'], $day['humidity'], $day['wind_speed'],
                $day['precipitation_chance'],
                $day['temp_min'], $day['temp_max'], $day['temp_avg'],
                $day['condition'], $day['humidity'], $day['wind_speed'],
                $day['precipitation_chance']
            ]);
        }
    }

    /**
     * HTTP İsteği Yap
     */
    private function makeRequest($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}

// API Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? null;
    $latitude = $_GET['lat'] ?? null;
    $longitude = $_GET['lon'] ?? null;
    $tripId = $_GET['trip_id'] ?? null;

    if (!$latitude || !$longitude) {
        echo json_encode(['success' => false, 'error' => 'Latitude ve longitude gerekli']);
        exit;
    }

    $weather = new WeatherService();

    if ($action === 'forecast') {
        echo json_encode($weather->getForecast($latitude, $longitude, $tripId));
    } elseif ($action === 'current') {
        echo json_encode($weather->getCurrentWeather($latitude, $longitude));
    } else {
        echo json_encode(['success' => false, 'error' => 'Geçersiz action']);
    }
}
?>

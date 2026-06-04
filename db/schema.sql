CREATE DATABASE IF NOT EXISTS gezi_haritasi;
USE gezi_haritasi;

-- Kullanıcılar Tablosu
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    profile_image VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Geziler Tablosu
CREATE TABLE IF NOT EXISTS trips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    start_point VARCHAR(255),
    end_point VARCHAR(255),
    start_latitude DECIMAL(10, 8),
    start_longitude DECIMAL(11, 8),
    end_latitude DECIMAL(10, 8),
    end_longitude DECIMAL(11, 8),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    vehicle_type ENUM('car', 'caravan', 'motorcycle', 'bike') DEFAULT 'car',
    has_children BOOLEAN DEFAULT FALSE,
    budget DECIMAL(10, 2),
    distance_km FLOAT,
    status ENUM('planning', 'ongoing', 'completed', 'cancelled') DEFAULT 'planning',
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_start_date (start_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gezi Rotaları Tablosu
CREATE TABLE IF NOT EXISTS trip_routes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    route_order INT NOT NULL,
    location_name VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    arrival_time TIME,
    departure_time TIME,
    notes TEXT,
    distance_from_previous FLOAT,
    estimated_duration_minutes INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_trip_id (trip_id),
    INDEX idx_order (route_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kamp Alanları Tablosu
CREATE TABLE IF NOT EXISTS campgrounds (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    address VARCHAR(255),
    phone VARCHAR(20),
    website VARCHAR(255),
    email VARCHAR(100),
    price_per_night DECIMAL(8, 2),
    has_electricity BOOLEAN DEFAULT FALSE,
    has_water BOOLEAN DEFAULT FALSE,
    has_wifi BOOLEAN DEFAULT FALSE,
    is_caravan_friendly BOOLEAN DEFAULT FALSE,
    is_family_friendly BOOLEAN DEFAULT FALSE,
    max_capacity INT,
    rating DECIMAL(3, 2) DEFAULT 0,
    review_count INT DEFAULT 0,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_location (latitude, longitude),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kamp Alanı Yorumları Tablosu
CREATE TABLE IF NOT EXISTS campground_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campground_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    comment TEXT,
    cleanliness_rating INT,
    service_rating INT,
    value_rating INT,
    is_caravan_friendly_review BOOLEAN,
    is_family_friendly_review BOOLEAN,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campground_id) REFERENCES campgrounds(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_campground_id (campground_id),
    INDEX idx_user_id (user_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hava Durumu Tablosu
CREATE TABLE IF NOT EXISTS weather_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    date DATE,
    temp_min DECIMAL(5, 2),
    temp_max DECIMAL(5, 2),
    temp_avg DECIMAL(5, 2),
    condition VARCHAR(100),
    humidity INT,
    wind_speed FLOAT,
    precipitation_chance INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_trip_id (trip_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yakıt Hesaplama Tablosu
CREATE TABLE IF NOT EXISTS fuel_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    vehicle_type ENUM('car', 'caravan', 'motorcycle', 'bike') NOT NULL,
    distance_km FLOAT NOT NULL,
    fuel_consumption_liter DECIMAL(8, 2),
    fuel_price_per_liter DECIMAL(6, 2),
    total_cost DECIMAL(8, 2),
    fuel_type ENUM('petrol', 'diesel', 'hybrid', 'electric') DEFAULT 'petrol',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_trip_id (trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gezi Fotoğrafları Tablosu
CREATE TABLE IF NOT EXISTS trip_photos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    route_id INT,
    user_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    image_url VARCHAR(255),
    caption TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    taken_at TIMESTAMP,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    likes_count INT DEFAULT 0,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES trip_routes(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_trip_id (trip_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Rota Önerileri Tablosu
CREATE TABLE IF NOT EXISTS ai_route_suggestions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    suggestion_type ENUM('scenic', 'fast', 'budget', 'family', 'adventure') DEFAULT 'scenic',
    waypoints JSON,
    total_distance FLOAT,
    estimated_duration_hours INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_trip_id (trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Suitability Scores Tablosu (Uygunluk Puanları)
CREATE TABLE IF NOT EXISTS suitability_scores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    family_friendliness_score INT DEFAULT 0,
    caravan_suitability_score INT DEFAULT 0,
    scenic_route_score INT DEFAULT 0,
    budget_efficiency_score INT DEFAULT 0,
    overall_score DECIMAL(4, 2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    UNIQUE KEY unique_trip (trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favoriler Tablosu
CREATE TABLE IF NOT EXISTS favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    trip_id INT,
    campground_id INT,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (campground_id) REFERENCES campgrounds(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- İstatistikler Tablosu
CREATE TABLE IF NOT EXISTS statistics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    total_distance_km FLOAT,
    total_duration_hours INT,
    average_speed_kmh FLOAT,
    total_fuel_cost DECIMAL(10, 2),
    avg_fuel_consumption_per_100km DECIMAL(5, 2),
    stops_count INT,
    photos_count INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    UNIQUE KEY unique_trip (trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
# İnteraktif Gezi Haritası - Kurulum Rehberi

## Sistem Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Apache 2.4 (mod_rewrite aktif)
- curl ve OpenSSL extensions

## Adım 1: Veritabanı Kurulumu

1. MySQL'de yeni bir veritabanı oluşturun:
```sql
CREATE DATABASE gezi_haritasi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. `db/schema.sql` dosyasını MySQL'e aktarın:
```bash
mysql -u root -p gezi_haritasi < db/schema.sql
```

## Adım 2: Konfigürasyon

1. `config/database.php` dosyasını açın ve aşağıdaki bilgileri girin:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'mysql_kullanici');
define('DB_PASS', 'mysql_sifresi');
define('DB_NAME', 'gezi_haritasi');
```

2. API anahtarlarını ekleyin:
```php
define('GOOGLE_MAPS_API_KEY', 'YOUR_GOOGLE_MAPS_API_KEY');
define('OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY');
define('OPENWEATHER_API_KEY', 'YOUR_OPENWEATHER_API_KEY');
```

## Adım 3: Upload Klasörü

Uploads klasörünü oluşturun:
```bash
mkdir -p uploads
chmod 755 uploads
```

## Adım 4: API Anahtarları Alma

### Google Maps API
1. https://console.cloud.google.com/ ziyaret edin
2. Yeni bir proje oluşturun
3. Maps JavaScript API'yi etkinleştirin
4. API anahtarı oluşturun

### OpenAI API
1. https://platform.openai.com/api-keys ziyaret edin
2. Hesap oluşturun veya giriş yapın
3. Yeni bir API anahtarı oluşturun

### OpenWeatherMap API
1. https://openweathermap.org/api ziyaret edin
2. Ücretsiz hesap oluşturun
3. API anahtarı alın

## Adım 5: Web Sunucusu Yapılandırması

### Apache için (.htaccess):
Proje kök dizinine `.htaccess` dosyası eklenmiştir.

Gerekli modülleri etkinleştirin:
```bash
a2enmod rewrite
systemctl restart apache2
```

## Adım 6: Başlatma

1. Web tarayıcınızda açın:
```
http://localhost/hd/public/index.php
```

2. Veya domain kurulduysa:
```
https://yourdomain.com
```

## Dosya Yapısı

```
hd/
├── api/
│   ├── weather.php          (Hava durumu)
│   ├── routes.php           (AI Rota önerici)
│   ├── fuel.php             (Yakıt hesaplayıcı)
│   ├── campgrounds.php      (Kamp alanları)
│   ├── suitability.php      (Uygunluk puanlama)
│   └── photos.php           (Resim yükleme)
├── config/
│   └── database.php         (Veritabanı konfigürasyonu)
├── db/
│   └── schema.sql           (Veritabanı şeması)
├── public/
│   ├── index.php            (Ana sayfa)
│   ├── css/
│   │   └── style.css        (CSS stilleri)
│   └── js/
│       └── app.js           (JavaScript uygulaması)
├── uploads/                 (Yüklenen fotoğraflar)
└── .htaccess                (Apache konfigürasyonu)
```

## API Endpoints

### Hava Durumu
- GET `/api/weather.php?action=forecast&lat=39&lon=35&trip_id=1`
- GET `/api/weather.php?action=current&lat=39&lon=35`

### Rota Önerici
- POST `/api/routes.php` (JSON body ile)
- GET `/api/routes.php?action=trip_suggestions&trip_id=1`

### Yakıt Hesaplayıcı
- POST `/api/fuel.php` (calculate, compare, trip_history actions)
- GET `/api/fuel.php?action=trip_total&trip_id=1`

### Kamp Alanları
- GET `/api/campgrounds.php?action=list`
- GET `/api/campgrounds.php?action=nearest&lat=39&lon=35&radius=50`
- GET `/api/campgrounds.php?action=details&id=1`
- POST `/api/campgrounds.php` (add_review action)

### Uygunluk Puanlama
- POST `/api/suitability.php` (calculate action)
- GET `/api/suitability.php?action=get&trip_id=1`

### Resim Yükleme
- POST `/api/photos.php` (upload, like, delete actions)
- GET `/api/photos.php?action=list&trip_id=1`

## Sorun Giderme

### "Veritabanı Hatası"
- MySQL servisinin çalışıp çalışmadığını kontrol edin
- Konfigürasyon dosyasındaki kimlik bilgilerini doğrulayın

### "API Hatası"
- API anahtarlarının doğru girişini kontrol edin
- İnternet bağlantısını test edin

### "Upload Hatası"
- `uploads/` klasörünün yazılabilir olduğunu kontrol edin
- Dosya boyutu limitini kontrol edin

## Güvenlik Notları

1. Production ortamında:
   - Tüm API anahtarlarını `.env` dosyasında saklayın
   - HTTPS kullanın
   - SQL injection koruması için prepared statements kullanın
   - CORS ayarlarını sınırlandırın

2. Database:
   - Güçlü parolalar kullanın
   - Düzenli yedeklemeler alın
   - Sadece gerekli izinleri verin

## İletişim & Destek

Sorun veya sorularınız için GitHub Issues'i kullanın.

---
Son güncelleme: 2026

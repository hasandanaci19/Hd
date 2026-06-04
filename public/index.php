<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İnteraktif Gezi Haritası - Gezi Planlayıcı</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://leaflet.js.org/examples/ivy/leaflet.css" />
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>🗺️ İnteraktif Gezi Haritası</h1>
            <nav class="navbar">
                <a href="#trips">Gezilerim</a>
                <a href="#campgrounds">Kamp Alanları</a>
                <a href="#planner">Planlayıcı</a>
                <a href="#account">Hesapla</a>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h2>Mükemmel Gezini Planla!</h2>
            <p>Yapay zekâ destekli rota önerileri, hava durumu ve daha fazlası</p>
            <button class="btn btn-primary" onclick="document.getElementById('planner').scrollIntoView()">Gezi Planlamaya Başla</button>
        </div>
    </section>

    <main class="container">
        <!-- Trip Planner Section -->
        <section id="planner" class="planner-section">
            <h2>Gezi Planla</h2>
            <div class="planner-form">
                <div class="form-group">
                    <label>Gezi Başlığı</label>
                    <input type="text" id="tripTitle" placeholder="Örn: Ege Turist Turu">
                </div>

                <div class="form-group">
                    <label>Başlangıç Noktası</label>
                    <input type="text" id="startPoint" placeholder="Başlangıç şehri">
                </div>

                <div class="form-group">
                    <label>Bitiş Noktası</label>
                    <input type="text" id="endPoint" placeholder="Bitiş şehri">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Başlangıç Tarihi</label>
                        <input type="date" id="startDate">
                    </div>
                    <div class="form-group">
                        <label>Bitiş Tarihi</label>
                        <input type="date" id="endDate">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Araç Tipi</label>
                        <select id="vehicleType">
                            <option value="car">Araba</option>
                            <option value="caravan">Karavan</option>
                            <option value="motorcycle">Motosiklet</option>
                            <option value="bike">Bisiklet</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="hasChildren">
                            Çocuklarla Seyahat Ediyorum
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Bütçe (TL)</label>
                    <input type="number" id="budget" placeholder="Tahmini bütçe">
                </div>

                <button class="btn btn-primary btn-large" onclick="createTrip()">Gezi Oluştur</button>
            </div>
        </section>

        <!-- Map Section -->
        <section id="map-section" class="map-section" style="display:none;">
            <h2>Gezi Haritası</h2>
            <div id="map" style="height: 500px; border-radius: 8px; margin-bottom: 20px;"></div>
            
            <div class="map-controls">
                <h3>Rota Önerileri</h3>
                <div class="route-suggestions">
                    <button class="btn" onclick="getAIRoute('scenic')">🌄 Manzaralı Rota</button>
                    <button class="btn" onclick="getAIRoute('budget')">💰 Ekonomik Rota</button>
                    <button class="btn" onclick="getAIRoute('family_friendly')">👨‍👩‍👧‍👦 Aile Dostu Rota</button>
                    <button class="btn" onclick="getAIRoute('adventure')">🎯 Macera Rotası</button>
                </div>
            </div>
        </section>

        <!-- Suitability Scores Section -->
        <section id="scores-section" class="scores-section" style="display:none;">
            <h2>Gezi Uygunluk Puanları</h2>
            <div class="scores-grid">
                <div class="score-card">
                    <h3>👨‍👩‍👧‍👦 Aile Uygunluğu</h3>
                    <div class="score-bar">
                        <div class="score-fill" id="familyScore"></div>
                    </div>
                    <p id="familyScoreText">-</p>
                </div>

                <div class="score-card">
                    <h3>🚐 Karavan Uygunluğu</h3>
                    <div class="score-bar">
                        <div class="score-fill" id="caravanScore"></div>
                    </div>
                    <p id="caravanScoreText">-</p>
                </div>

                <div class="score-card">
                    <h3>🌄 Manzara Kalitesi</h3>
                    <div class="score-bar">
                        <div class="score-fill" id="scenicScore"></div>
                    </div>
                    <p id="scenicScoreText">-</p>
                </div>

                <div class="score-card">
                    <h3>💰 Bütçe Verimliliği</h3>
                    <div class="score-bar">
                        <div class="score-fill" id="budgetScore"></div>
                    </div>
                    <p id="budgetScoreText">-</p>
                </div>
            </div>

            <div class="overall-score">
                <h3>Genel Puan</h3>
                <div class="large-score" id="overallScore">-</div>
            </div>
        </section>

        <!-- Weather Section -->
        <section id="weather-section" class="weather-section" style="display:none;">
            <h2>🌦️ Hava Durumu Tahmini</h2>
            <div id="weather-forecast" class="weather-grid"></div>
        </section>

        <!-- Campgrounds Section -->
        <section id="campgrounds" class="campgrounds-section">
            <h2>🏕️ Kamp Alanları</h2>
            <div class="filter-controls">
                <button class="filter-btn active" onclick="filterCampgrounds('all')">Tümü</button>
                <button class="filter-btn" onclick="filterCampgrounds('family')">👨‍👩‍👧 Aile Dostu</button>
                <button class="filter-btn" onclick="filterCampgrounds('caravan')">🚐 Karavan Dostu</button>
            </div>
            <div id="campgrounds-list" class="campgrounds-grid"></div>
        </section>
    </main>

    <footer class="footer">
        <p>&copy; 2026 İnteraktif Gezi Haritası. Tüm hakları saklıdır.</p>
    </footer>

    <script src="https://leaflet.js.org/examples/ivy/leaflet.js"></script>
    <script src="js/app.js"></script>
</body>
</html>

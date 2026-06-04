// API Base URL
const API_BASE = '/hd/api';
let map = null;
let currentTripId = null;

// Initialize Map
function initMap() {
    if (!map) {
        map = L.map('map').setView([39, 35], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
    }
}

// Create Trip
async function createTrip() {
    const tripData = {
        title: document.getElementById('tripTitle').value,
        start_point: document.getElementById('startPoint').value,
        end_point: document.getElementById('endPoint').value,
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value,
        vehicle_type: document.getElementById('vehicleType').value,
        has_children: document.getElementById('hasChildren').checked,
        budget: document.getElementById('budget').value
    };

    if (!tripData.title || !tripData.start_point || !tripData.end_point) {
        alert('Lütfen tüm alanları doldurunuz!');\n        return;
    }

    // Show sections
    document.getElementById('map-section').style.display = 'block';
    document.getElementById('scores-section').style.display = 'block';
    document.getElementById('weather-section').style.display = 'block';

    // Initialize map
    initMap();

    // Simulate trip creation
    currentTripId = 1;

    // Load data
    await loadWeather();
    await loadSuitabilityScores();
    await loadCampgrounds();

    alert('Gezi başarıyla oluşturuldu!');
}

// Get AI Route Suggestion
async function getAIRoute(type) {
    if (!currentTripId) {
        alert('Önce bir gezi oluşturun!');
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/routes.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: type,
                trip_id: currentTripId,
                start_point: document.getElementById('startPoint').value,
                end_point: document.getElementById('endPoint').value
            })
        });

        const data = await response.json();
        if (data.success) {
            alert(`${type.toUpperCase()} rotası alındı!`);
            console.log('Route:', data.data);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Load Weather
async function loadWeather() {
    if (!currentTripId) return;

    try {
        const weatherData = [
            { date: 'Pazartesi', temp_min: 18, temp_max: 25, condition: 'Güneşli' },
            { date: 'Salı', temp_min: 16, temp_max: 23, condition: 'Bulutlu' },
            { date: 'Çarşamba', temp_min: 15, temp_max: 22, condition: 'Yağışlı' },
            { date: 'Perşembe', temp_min: 17, temp_max: 24, condition: 'Güneşli' },
            { date: 'Cuma', temp_min: 19, temp_max: 26, condition: 'Güneşli' }
        ];

        const weatherGrid = document.getElementById('weather-forecast');
        weatherGrid.innerHTML = weatherData.map(w => `
            <div class=\"weather-card\">
                <div class=\"date\">${w.date}</div>
                <div class=\"temps\">${w.temp_min}°C - ${w.temp_max}°C</div>
                <div class=\"condition\">${w.condition}</div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Weather error:', error);
    }
}

// Load Suitability Scores
async function loadSuitabilityScores() {
    if (!currentTripId) return;

    try {
        const scores = {
            family_friendliness_score: 75,
            caravan_suitability_score: 65,
            scenic_route_score: 85,
            budget_efficiency_score: 70,
            overall_score: 74
        };

        document.getElementById('familyScore').style.width = scores.family_friendliness_score + '%';
        document.getElementById('familyScoreText').textContent = scores.family_friendliness_score + '/100';

        document.getElementById('caravanScore').style.width = scores.caravan_suitability_score + '%';
        document.getElementById('caravanScoreText').textContent = scores.caravan_suitability_score + '/100';

        document.getElementById('scenicScore').style.width = scores.scenic_route_score + '%';
        document.getElementById('scenicScoreText').textContent = scores.scenic_route_score + '/100';

        document.getElementById('budgetScore').style.width = scores.budget_efficiency_score + '%';
        document.getElementById('budgetScoreText').textContent = scores.budget_efficiency_score + '/100';

        document.getElementById('overallScore').textContent = scores.overall_score.toFixed(1);
    } catch (error) {
        console.error('Scores error:', error);
    }
}

// Load Campgrounds
async function loadCampgrounds(filter = 'all') {
    try {
        const campgrounds = [
            { id: 1, name: 'Ege Kamp', rating: 4.5, reviews: 120, family: true, caravan: true, price: 450 },
            { id: 2, name: 'Akdeniz Otel', rating: 4.2, reviews: 95, family: true, caravan: false, price: 350 },
            { id: 3, name: 'Karavan Paradise', rating: 4.8, reviews: 210, family: false, caravan: true, price: 550 },
            { id: 4, name: 'Aile Konu', rating: 4.3, reviews: 85, family: true, caravan: true, price: 400 },
            { id: 5, name: 'Mountain Camp', rating: 4.6, reviews: 150, family: true, caravan: true, price: 480 },
            { id: 6, name: 'Seaside Resort', rating: 4.4, reviews: 110, family: true, caravan: false, price: 420 }
        ];

        let filtered = campgrounds;
        if (filter === 'family') {
            filtered = campgrounds.filter(c => c.family);
        } else if (filter === 'caravan') {
            filtered = campgrounds.filter(c => c.caravan);
        }

        const grid = document.getElementById('campgrounds-list');
        grid.innerHTML = filtered.map(camp => `
            <div class=\"campground-card\">
                <div class=\"campground-image\">🏕️</div>
                <div class=\"campground-info\">
                    <h3>${camp.name}</h3>
                    <div class=\"campground-rating\">⭐ ${camp.rating} (${camp.reviews} yorum)</div>
                    <div class=\"campground-badges\">
                        ${camp.family ? '<span class=\"badge\">👨‍👩‍👧 Aile</span>' : ''}
                        ${camp.caravan ? '<span class=\"badge\">🚐 Karavan</span>' : ''}
                    </div>
                    <p><strong>${camp.price} TL/Gece</strong></p>
                    <button class=\"btn btn-primary\" onclick=\"alert('Detaylar: ${camp.name}')\">Detaylar</button>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Campgrounds error:', error);
    }
}

// Filter Campgrounds
function filterCampgrounds(filter) {
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    loadCampgrounds(filter);
}

// Page Load
document.addEventListener('DOMContentLoaded', () => {
    console.log('App loaded');
    loadCampgrounds();
});

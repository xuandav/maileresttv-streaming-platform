<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$pageTitle = 'Canales en Vivo';

// Get filter parameters - handle both old and new SEO-friendly URLs
$categoryFilter = '';
$countryFilter = '';
$searchQuery = '';

// Check for old URL format first
if (isset($_GET['category'])) {
    $categoryFilter = sanitizeInput($_GET['category']);
} elseif (isset($_GET['country'])) {
    $countryFilter = sanitizeInput($_GET['country']);
} elseif (isset($_GET['search'])) {
    $searchQuery = sanitizeInput($_GET['search']);
}

// Check for new SEO-friendly URL format
$requestUri = $_SERVER['REQUEST_URI'];
if (preg_match('/\/categoria\/([a-z0-9\-]+)\/?/', $requestUri, $matches)) {
    $categoryFilter = slugToCategory($matches[1]);
} elseif (preg_match('/\/pais\/([a-z0-9\-]+)\/?/', $requestUri, $matches)) {
    $countryFilter = slugToCountry($matches[1]);
} elseif (preg_match('/\/buscar\/([a-z0-9\-]+)\/?/', $requestUri, $matches)) {
    $searchQuery = str_replace('-', ' ', $matches[1]);
}

// Build query
$sql = "SELECT c.*, 
    (SELECT COUNT(*) FROM channel_subscriptions cs WHERE cs.channel_id = c.id) AS real_subscriber_count 
    FROM channels c WHERE c.is_active = 1";
$params = [];

if ($categoryFilter) {
    $sql .= " AND c.category = ?";
    $params[] = $categoryFilter;
}

if ($countryFilter) {
    $sql .= " AND c.country = ?";
    $params[] = $countryFilter;
}

if ($searchQuery) {
    $sql .= " AND (c.name LIKE ? OR c.description LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " ORDER BY real_subscriber_count DESC, c.name ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $channels = $stmt->fetchAll();
    
    // Get unique categories and countries for filters
    $categoriesStmt = $pdo->query("SELECT DISTINCT category FROM channels WHERE is_active = 1 ORDER BY category");
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $countriesStmt = $pdo->query("SELECT DISTINCT country FROM channels WHERE is_active = 1 ORDER BY country");
    $countries = $countriesStmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error = "Error al cargar los canales: " . $e->getMessage();
    $channels = [];
    $categories = [];
    $countries = [];
}

require_once '../includes/header.php';
?>

<main>
    <div class="main-layout">
        <?php include '../includes/side_menu.php'; ?>
        <section class="content-area">
            <div class="container">
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php
        // Obtener canales "Ahora viendo" - canales con actividad de chat en últimos 5 minutos
        $nowWatchingStmt = $pdo->prepare("
            SELECT DISTINCT c.* FROM channels c
            JOIN chat_messages cm ON c.id = cm.channel_id
            WHERE cm.created_at > datetime('now', '-5 minutes') AND c.is_active = 1
            ORDER BY c.subscriber_count DESC
            LIMIT 8
        ");
        $nowWatchingStmt->execute();
        $nowWatchingChannels = $nowWatchingStmt->fetchAll();

        // Obtener canales suscritos por el usuario (IP)
        $userIdentifier = $_SERVER['REMOTE_ADDR'];
        $subscribedStmt = $pdo->prepare("
            SELECT c.*, (SELECT COUNT(*) FROM channel_subscriptions cs2 WHERE cs2.channel_id = c.id) AS real_subscriber_count FROM channels c
            JOIN channel_subscriptions cs ON c.id = cs.channel_id
            WHERE cs.user_identifier = ? AND c.is_active = 1
            ORDER BY real_subscriber_count DESC
            LIMIT 8
        ");
        $subscribedStmt->execute([$userIdentifier]);
        $subscribedChannels = $subscribedStmt->fetchAll();
        ?>

        <?php if (!empty($nowWatchingChannels)): ?>
            <section class="now-watching-section">
                <h2>Ahora viendo</h2>
                <div class="channels-grid">
                <?php foreach ($nowWatchingChannels as $channel): ?>
                        <a href="<?php echo generateChannelUrl($channel); ?>" class="channel-card">
                            <div class="channel-thumbnail">
                                <?php if ($channel['thumbnail_url']): ?>
                                    <img src="<?php echo escape($channel['thumbnail_url']); ?>" alt="<?php echo escape($channel['name']); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="thumbnail-placeholder"><?php echo escape($channel['name']); ?></div>
                                <?php endif; ?>
                                <div class="stream-type-badge"><?php echo strtoupper($channel['stream_type']); ?></div>
                            </div>
                            <div class="channel-info">
                                <h3 class="channel-name"><?php echo escape($channel['name']); ?></h3>
                                <div class="channel-meta">
                                    <span class="channel-category"><?php echo escape($channel['category']); ?></span>
                                    <span class="subscriber-count"><?php echo formatSubscriberCount($channel['subscriber_count']); ?> suscriptores</span>
                                </div>
                                <div class="channel-meta">
                                    <span class="channel-country">📍 <?php echo escape($channel['country']); ?></span>
                                    <span class="stream-status live">🔴 En Vivo</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($subscribedChannels)): ?>
            <section class="subscribed-channels-section">
                <h2>Canales Suscritos</h2>
                <div class="channels-grid">
                    <?php foreach ($subscribedChannels as $channel): ?>
                        <a href="<?php echo generateChannelUrl($channel); ?>" class="channel-card">
                            <div class="channel-thumbnail">
                                <?php if ($channel['thumbnail_url']): ?>
                                    <img src="<?php echo escape($channel['thumbnail_url']); ?>" alt="<?php echo escape($channel['name']); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="thumbnail-placeholder"><?php echo escape($channel['name']); ?></div>
                                <?php endif; ?>
                                <div class="stream-type-badge"><?php echo strtoupper($channel['stream_type']); ?></div>
                            </div>
                            <div class="channel-info">
                                <h3 class="channel-name"><?php echo escape($channel['name']); ?></h3>
                                <div class="channel-meta">
                                    <span class="channel-category"><?php echo escape($channel['category']); ?></span>
                                    <span class="subscriber-count"><?php echo formatSubscriberCount($channel['subscriber_count']); ?> suscriptores</span>
                                </div>
                                <div class="channel-meta">
                                    <span class="channel-country">📍 <?php echo escape($channel['country']); ?></span>
                                    <span class="stream-status live">🔴 En Vivo</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Featured Channels Carousel -->
        <?php if (!$categoryFilter && !$countryFilter && !$searchQuery): ?>
            <section class="featured-carousel-section">
                <div class="carousel-container">
                    <div class="carousel-header">
                        <h2>Canales Destacados en Vivo</h2>
                        <div class="carousel-controls">
                            <button class="carousel-btn prev-btn" onclick="moveCarousel(-1)">‹</button>
                            <button class="carousel-btn next-btn" onclick="moveCarousel(1)">›</button>
                        </div>
                    </div>
                    
                    <div class="carousel-wrapper">
                        <div class="carousel-track" id="carousel-track">
                            <?php
                            // Get featured channels for carousel with real subscriber count
                            $featuredStmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM channel_subscriptions cs WHERE cs.channel_id = c.id) AS real_subscriber_count FROM channels c WHERE c.is_active = 1 ORDER BY real_subscriber_count DESC LIMIT 8");
                            $featuredChannels = $featuredStmt->fetchAll();
                            ?>
                            
                            <?php foreach ($featuredChannels as $index => $channel): ?>
                                <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <a href="<?php echo generateChannelUrl($channel); ?>" class="carousel-card">
                                        <div class="carousel-thumbnail">
                                            <?php if ($channel['thumbnail_url']): ?>
                                                <img src="<?php echo escape($channel['thumbnail_url']); ?>" 
                                                     alt="<?php echo escape($channel['name']); ?>"
                                                     loading="lazy">
                                            <?php else: ?>
                                                <div class="carousel-placeholder">
                                                    <?php echo strtoupper(substr($channel['name'], 0, 2)); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="live-indicator">
                                                <span class="live-dot"></span>
                                                EN VIVO
                                            </div>
                                            
                                            <div class="stream-type-badge">
                                                <?php echo strtoupper($channel['stream_type']); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="carousel-info">
                                            <h3 class="carousel-title"><?php echo escape($channel['name']); ?></h3>
                                            <div class="carousel-meta">
                                                <span class="carousel-category"><?php echo escape($channel['category']); ?></span>
                                                <span class="carousel-viewers"><?php echo number_format($channel['real_subscriber_count']); ?> suscriptores</span>
                                            </div>
                                            <div class="carousel-description">
                                                <?php echo escape(substr($channel['description'], 0, 100)) . (strlen($channel['description']) > 100 ? '...' : ''); ?>
                                            </div>
                                            <div class="carousel-country">
                                                📍 <?php echo escape($channel['country']); ?>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="carousel-indicators">
                        <?php for ($i = 0; $i < count($featuredChannels); $i++): ?>
                            <button class="carousel-indicator <?php echo $i === 0 ? 'active' : ''; ?>" 
                                    onclick="goToSlide(<?php echo $i; ?>)"></button>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
        
        <div class="channels-header">
            <h1 class="channels-title">Todos los Canales</h1>
            
            <div class="filters">
                <select id="category-filter" class="filter-select" onchange="filterByCategory(this.value)">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo escape($category); ?>" <?php echo $categoryFilter === $category ? 'selected' : ''; ?>>
                            <?php echo escape($category); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select id="country-filter" class="filter-select" onchange="filterByCountry(this.value)">
                    <option value="">Todos los países</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?php echo escape($country); ?>" <?php echo $countryFilter === $country ? 'selected' : ''; ?>>
                            <?php echo escape($country); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <?php if (empty($channels)): ?>
            <div class="no-channels">
                <h2>No se encontraron canales</h2>
                <p>No hay canales disponibles con los filtros seleccionados.</p>
                <a href="/index.php" class="btn-primary">Ver todos los canales</a>
            </div>
        <?php else: ?>
            <div class="channels-grid">
                <?php foreach ($channels as $channel): ?>
                    <a href="<?php echo generateChannelUrl($channel); ?>" 
                       class="channel-card" 
                       data-category="<?php echo escape($channel['category']); ?>"
                       data-country="<?php echo escape($channel['country']); ?>">
                        
                        <div class="channel-thumbnail">
                            <?php if ($channel['thumbnail_url']): ?>
                                <img src="<?php echo escape($channel['thumbnail_url']); ?>" 
                                     alt="<?php echo escape($channel['name']); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="thumbnail-placeholder">
                                    <?php echo escape($channel['name']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="stream-type-badge">
                                <?php echo strtoupper($channel['stream_type']); ?>
                            </div>
                        </div>
                        
                        <div class="channel-info">
                            <h3 class="channel-name"><?php echo escape($channel['name']); ?></h3>
                            
                            <div class="channel-meta">
                                <span class="channel-category"><?php echo escape($channel['category']); ?></span>
                                <span class="subscriber-count">
                                    <?php echo number_format($channel['real_subscriber_count']); ?> suscriptores
                                </span>
                            </div>
                            
                            <div class="channel-meta">
                                <span class="channel-country">📍 <?php echo escape($channel['country']); ?></span>
                                <span class="stream-status live">🔴 En Vivo</span>
                            </div>
                            
                            <?php if ($channel['description']): ?>
                                <p class="channel-description">
                                    <?php echo escape($channel['description']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Section -->
        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo count($channels); ?></h3>
                    <p>Canales Disponibles</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count($categories); ?></h3>
                    <p>Categorías</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count($countries); ?></h3>
                    <p>Países</p>
                </div>
                <div class="stat-card">
                    <h3>24/7</h3>
                    <p>Disponibilidad</p>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
// Carousel functionality
let currentSlide = 0;
let totalSlides = 0;
let autoPlayInterval;

function initializeCarousel() {
    const slides = document.querySelectorAll('.carousel-slide');
    totalSlides = slides.length;
    
    if (totalSlides > 0) {
        // Start autoplay
        startAutoPlay();
        
        // Add touch/swipe support
        addTouchSupport();
    }
}

function moveCarousel(direction) {
    const track = document.getElementById('carousel-track');
    const slides = document.querySelectorAll('.carousel-slide');
    const indicators = document.querySelectorAll('.carousel-indicator');
    
    if (slides.length === 0) return;
    
    // Remove active class from current slide and indicator
    slides[currentSlide].classList.remove('active');
    indicators[currentSlide].classList.remove('active');
    
    // Calculate new slide index
    currentSlide += direction;
    
    if (currentSlide >= totalSlides) {
        currentSlide = 0;
    } else if (currentSlide < 0) {
        currentSlide = totalSlides - 1;
    }
    
    // Add active class to new slide and indicator
    slides[currentSlide].classList.add('active');
    indicators[currentSlide].classList.add('active');
    
    // Move the track
    const translateX = -currentSlide * 100;
    track.style.transform = `translateX(${translateX}%)`;
    
    // Restart autoplay
    restartAutoPlay();
}

function goToSlide(slideIndex) {
    const direction = slideIndex - currentSlide;
    currentSlide = slideIndex - direction;
    moveCarousel(direction);
}

function startAutoPlay() {
    autoPlayInterval = setInterval(() => {
        moveCarousel(1);
    }, 5000); // Change slide every 5 seconds
}

function restartAutoPlay() {
    clearInterval(autoPlayInterval);
    startAutoPlay();
}

function addTouchSupport() {
    const track = document.getElementById('carousel-track');
    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    
    track.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        isDragging = true;
        clearInterval(autoPlayInterval);
    });
    
    track.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        currentX = e.touches[0].clientX;
    });
    
    track.addEventListener('touchend', () => {
        if (!isDragging) return;
        isDragging = false;
        
        const diffX = startX - currentX;
        
        if (Math.abs(diffX) > 50) { // Minimum swipe distance
            if (diffX > 0) {
                moveCarousel(1); // Swipe left - next slide
            } else {
                moveCarousel(-1); // Swipe right - previous slide
            }
        }
        
        startAutoPlay();
    });
}

// Filter functions
function filterByCategory(category) {
    const url = new URL(window.location);
    if (category) {
        url.searchParams.set('category', category);
    } else {
        url.searchParams.delete('category');
    }
    window.location.href = url.toString();
}

function filterByCountry(country) {
    const url = new URL(window.location);
    if (country) {
        url.searchParams.set('country', country);
    } else {
        url.searchParams.delete('country');
    }
    window.location.href = url.toString();
}

// Search functionality
function searchChannels() {
    const searchTerm = document.getElementById('search-input').value.trim();
    const url = new URL(window.location);
    
    if (searchTerm) {
        url.searchParams.set('search', searchTerm);
    } else {
        url.searchParams.delete('search');
    }
    
    window.location.href = url.toString();
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Initialize carousel
    initializeCarousel();
    
    // Add loading animation to channel cards
    const channelCards = document.querySelectorAll('.channel-card');
    channelCards.forEach((card, index) => {
        card.style.animationDelay = (index * 0.1) + 's';
        card.classList.add('fade-in');
    });
    
    // Update results count
    updateResultsCount();
});

function updateResultsCount() {
    const totalChannels = <?php echo count($channels); ?>;
    const resultsText = document.createElement('p');
    resultsText.className = 'results-count';
    resultsText.textContent = `Mostrando ${totalChannels} canales`;
    
    const channelsHeader = document.querySelector('.channels-header');
    if (channelsHeader && !channelsHeader.querySelector('.results-count')) {
        channelsHeader.appendChild(resultsText);
    }
}
</script>

<style>
/* Carousel Styles */
.featured-carousel-section {
    margin-bottom: 3rem;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.carousel-container {
    position: relative;
}

.carousel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem 2rem 1rem 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.carousel-header h2 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 600;
}

.carousel-controls {
    display: flex;
    gap: 0.5rem;
}

.carousel-btn {
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.carousel-btn:hover {
    background: rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.5);
    transform: scale(1.1);
}

.carousel-wrapper {
    overflow: hidden;
    position: relative;
}

.carousel-track {
    display: flex;
    transition: transform 0.5s ease;
    will-change: transform;
}

.carousel-slide {
    min-width: 100%;
    opacity: 0;
    transition: opacity 0.5s ease;
}

.carousel-slide.active {
    opacity: 1;
}

.carousel-card {
    display: flex;
    text-decoration: none;
    color: inherit;
    padding: 2rem;
    gap: 2rem;
    align-items: center;
    transition: all 0.3s ease;
}

.carousel-card:hover {
    background: rgba(0,0,0,0.02);
}

.carousel-thumbnail {
    position: relative;
    width: 400px;
    height: 225px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.carousel-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.carousel-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    font-weight: bold;
}

.live-indicator {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(231, 76, 60, 0.9);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.live-dot {
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.carousel-info {
    flex: 1;
    padding-left: 1rem;
}

.carousel-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #333;
    line-height: 1.2;
}

.carousel-meta {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1rem;
    align-items: center;
}

.carousel-category {
    background: #3498db;
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.carousel-viewers {
    color: #666;
    font-size: 1rem;
    font-weight: 500;
}

.carousel-description {
    color: #555;
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.carousel-country {
    color: #666;
    font-size: 1rem;
    font-weight: 500;
}

.carousel-indicators {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    padding: 1.5rem;
    background: #f8f9fa;
}

.carousel-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: none;
    background: #ddd;
    cursor: pointer;
    transition: all 0.3s ease;
}

.carousel-indicator.active {
    background: #e74c3c;
    transform: scale(1.2);
}

.carousel-indicator:hover {
    background: #bbb;
}

/* Existing styles */
.no-channels {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.no-channels h2 {
    color: #666;
    margin-bottom: 1rem;
}

.no-channels p {
    color: #999;
    margin-bottom: 2rem;
}

.stats-section {
    margin-top: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.stat-card h3 {
    font-size: 2rem;
    color: #e74c3c;
    margin-bottom: 0.5rem;
}

.stat-card p {
    color: #666;
    font-weight: 500;
}

.stream-status.live {
    color: #27ae60;
    font-weight: bold;
}

.thumbnail-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: white;
    font-weight: bold;
    text-align: center;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.fade-in {
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
    transform: translateY(20px);
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .carousel-card {
        flex-direction: column;
        text-align: center;
        padding: 1.5rem;
    }
    
    .carousel-thumbnail {
        width: 100%;
        max-width: 400px;
        height: 200px;
    }
    
    .carousel-title {
        font-size: 1.5rem;
    }
    
    .carousel-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .carousel-controls {
        display: none;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .carousel-header {
        padding: 1rem;
    }
    
    .carousel-header h2 {
        font-size: 1.4rem;
    }
    
    .carousel-card {
        padding: 1rem;
    }
    
    .carousel-title {
        font-size: 1.3rem;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>

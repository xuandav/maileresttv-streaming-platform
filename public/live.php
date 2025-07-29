<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Get channel from URL - handle both old and new SEO-friendly URLs
$channel = null;
$channelId = 0;


if (isset($_GET['channel_id'])) {
    // Old URL format: live.php?channel_id=1
    $channelId = (int)$_GET['channel_id'];
    error_log("DEBUG: Using old format with channel_id = " . $channelId);
    try {
        $stmt = $pdo->prepare("SELECT * FROM channels WHERE id = ? AND is_active = 1");
        $stmt->execute([$channelId]);
        $channel = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("DEBUG: Found channel by ID: " . ($channel ? $channel['name'] : 'NOT FOUND'));
    } catch (PDOException $e) {
        error_log("DEBUG: Database error: " . $e->getMessage());
    }
} elseif (isset($_GET['channel_slug'])) {
    // New SEO-friendly URL format: /en-vivo/canal-name
    $slug = $_GET['channel_slug'];
    error_log("DEBUG: Using slug format with slug = " . $slug);
    $channel = findChannelBySlug($pdo, $slug);
    if ($channel) {
        $channelId = $channel['id'];
        error_log("DEBUG: Found channel by slug: " . $channel['name'] . " (ID: " . $channelId . ")");
    } else {
        error_log("DEBUG: Channel NOT found by slug: " . $slug);
    }
} else {
    // Try to extract from REQUEST_URI as fallback
    $slug = extractChannelSlugFromUrl($_SERVER['REQUEST_URI']);
    error_log("DEBUG: Extracted slug from URI: " . ($slug ? $slug : 'NONE'));
    if ($slug) {
        $channel = findChannelBySlug($pdo, $slug);
        if ($channel) {
            $channelId = $channel['id'];
            error_log("DEBUG: Found channel by extracted slug: " . $channel['name'] . " (ID: " . $channelId . ")");
        } else {
            error_log("DEBUG: Channel NOT found by extracted slug: " . $slug);
        }
    }
}

if (!$channel) {
    error_log("DEBUG: No channel found, redirecting to index");
    header('Location: /index.php');
    exit;
}

error_log("DEBUG: Final channel: " . $channel['name'] . " (ID: " . $channelId . ")");

$pageTitle = $channel['name'];

require_once '../includes/header.php';
?>

<main class="live-page">
    <div class="main-layout">
        <aside class="side-menu">
            <?php include '../includes/side_menu.php'; ?>
        </aside>
        <section class="content-area">
            <div class="container live-container">
                <!-- Currently Playing Channel and Suggested Channels Carousel -->
                <section class="live-carousel-section">
                    <div class="live-carousel-container">
                        <div class="live-carousel-header">
                            <div class="current-channel-info">
                                <h2>Reproduciendo Ahora: <?php echo escape($channel['name']); ?></h2>
                                <p><?php echo escape($channel['category']); ?> • <?php echo escape($channel['country']); ?></p>
                            </div>
                            <div class="live-carousel-controls">
                                <button class="live-carousel-btn prev-btn" onclick="moveLiveCarousel(-1)">‹</button>
                                <button class="live-carousel-btn next-btn" onclick="moveLiveCarousel(1)">›</button>
                            </div>
                        </div>
                        
                        <div class="live-carousel-wrapper">
                            <div class="live-carousel-track" id="live-carousel-track">
                                <?php
                                // Get related channels for carousel
                                $relatedStmt = $pdo->prepare("
                                    SELECT * FROM channels 
                                    WHERE category = ? AND id != ? AND is_active = 1 
                                    ORDER BY subscriber_count DESC 
                                    LIMIT 8
                                ");
                                $relatedStmt->execute([$channel['category'], $channelId]);
                                $relatedChannels = $relatedStmt->fetchAll();
                                
                                // If not enough related channels, get more from other categories
                                if (count($relatedChannels) < 6) {
                                    $additionalStmt = $pdo->prepare("
                                        SELECT * FROM channels 
                                        WHERE id != ? AND is_active = 1 
                                        ORDER BY subscriber_count DESC 
                                        LIMIT ?
                                    ");
                                    $additionalStmt->execute([$channelId, 8 - count($relatedChannels)]);
                                    $additionalChannels = $additionalStmt->fetchAll();
                                    $relatedChannels = array_merge($relatedChannels, $additionalChannels);
                                }
                                ?>
                                
                                <?php foreach ($relatedChannels as $index => $related): ?>
                                    <div class="live-carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <a href="<?php echo generateChannelUrl($related); ?>" class="live-carousel-card">
                                            <div class="live-carousel-thumbnail">
                                                <?php if ($related['thumbnail_url']): ?>
                                                    <img src="<?php echo escape($related['thumbnail_url']); ?>" 
                                                         alt="<?php echo escape($related['name']); ?>"
                                                         loading="lazy">
                                                <?php else: ?>
                                                    <div class="live-carousel-placeholder">
                                                        <?php echo strtoupper(substr($related['name'], 0, 2)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="live-indicator-small">
                                                    <span class="live-dot-small"></span>
                                                    EN VIVO
                                                </div>
                                            </div>
                                            
                                            <div class="live-carousel-info">
                                                <h4><?php echo escape($related['name']); ?></h4>
                                                <p><?php echo escape($related['category']); ?></p>
                                                <span class="live-carousel-viewers"><?php echo formatSubscriberCount($related['subscriber_count']); ?> suscriptores</span>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="live-player-chat">
                    <!-- Video Player Section -->
                    <section class="video-section">
                        <?php echo getStreamEmbedCode($channel['stream_type'], $channel['stream_url'], $channel['name']); ?>
                    </section>

                    <!-- Simple Chat Section -->
                    <section class="simple-chat-section">
                        <div class="simple-chat-tabs">
                            <button class="simple-chat-tab active" data-tab="chat" onclick="switchSimpleTab('chat')">
                                Chat
                            </button>
                            <button class="simple-chat-tab" data-tab="recommend" onclick="switchSimpleTab('recommend')">
                                Recomendados
                            </button>
                        </div>

                        <!-- Simple Chat Content -->
                        <div class="simple-chat-content" id="simple-chat-tab">
                            <div class="simple-chat-messages" id="simple-chat-messages">
                                <!-- Messages will be loaded dynamically -->
                            </div>

                            <div class="simple-chat-input">
                                <form id="chat-form">
                                    <div class="chat-input-row">
                                        <input type="text" id="username" name="username" placeholder="Tu nombre..." required>
                                        <input type="text" id="message" name="message" placeholder="Escribe tu mensaje..." required>
                                        <button type="submit" class="send-btn">Enviar</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Recommend Tab Content -->
                        <div class="simple-chat-content hidden" id="simple-recommend-tab">
                            <div class="recommend-content">
                                <p>Los canales recomendados aparecerán aquí</p>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Subscribe and Social Media Buttons - Moved outside player -->
                <div class="player-actions">
                    <div class="subscribe-section">
                        <button class="subscribe-btn" id="subscribe-btn" onclick="toggleSubscription(<?php echo $channelId; ?>)">
                            <span class="subscribe-icon">🔔</span>
                            Suscribirse
                        </button>
                        <?php
                        // Obtener conteo real de suscriptores desde channel_subscriptions
                        $subCountStmt = $pdo->prepare("SELECT COUNT(*) FROM channel_subscriptions WHERE channel_id = ?");
                        $subCountStmt->execute([$channelId]);
                        $realSubscriberCount = $subCountStmt->fetchColumn();
                        ?>
                        <span class="subscriber-count" id="subscriber-count"><?php echo number_format($realSubscriberCount); ?> suscriptores</span>
                    </div>
                    
                    <div class="social-share">
                        <span class="share-label">Compartir:</span>
                        <button class="social-btn facebook" onclick="shareOnFacebook()" title="Compartir en Facebook">
                            <span class="social-icon">📘</span>
                        </button>
                        <button class="social-btn twitter" onclick="shareOnTwitter()" title="Compartir en Twitter">
                            <span class="social-icon">🐦</span>
                        </button>
                        <button class="social-btn whatsapp" onclick="shareOnWhatsApp()" title="Compartir en WhatsApp">
                            <span class="social-icon">💬</span>
                        </button>
                        <button class="social-btn telegram" onclick="shareOnTelegram()" title="Compartir en Telegram">
                            <span class="social-icon">✈️</span>
                        </button>
                        <button class="social-btn copy-link" onclick="copyLink()" title="Copiar enlace">
                            <span class="social-icon">🔗</span>
                        </button>
                    </div>
                </div>
    </div>
</main>

<!-- JavaScript Libraries -->
<script src="/assets/js/player.js"></script>
<script src="/assets/js/chat.js"></script>

<script>
// Live page carousel functionality
let liveCurrentSlide = 0;
let liveTotalSlides = 0;
let liveAutoPlayInterval;

function initializeLiveCarousel() {
    const slides = document.querySelectorAll('.live-carousel-slide');
    liveTotalSlides = slides.length;
    
    if (liveTotalSlides > 0) {
        // Start autoplay
        startLiveAutoPlay();
        
        // Add touch/swipe support
        addLiveTouchSupport();
    }
}

function moveLiveCarousel(direction) {
    const track = document.getElementById('live-carousel-track');
    const slides = document.querySelectorAll('.live-carousel-slide');
    
    if (slides.length === 0) return;
    
    // Remove active class from current slide
    slides[liveCurrentSlide].classList.remove('active');
    
    // Calculate new slide index
    liveCurrentSlide += direction;
    
    if (liveCurrentSlide >= liveTotalSlides) {
        liveCurrentSlide = 0;
    } else if (liveCurrentSlide < 0) {
        liveCurrentSlide = liveTotalSlides - 1;
    }
    
    // Add active class to new slide
    slides[liveCurrentSlide].classList.add('active');
    
    // Move the track
    const translateX = -liveCurrentSlide * (100 / Math.min(liveTotalSlides, 4));
    track.style.transform = `translateX(${translateX}%)`;
    
    // Restart autoplay
    restartLiveAutoPlay();
}

function startLiveAutoPlay() {
    liveAutoPlayInterval = setInterval(() => {
        moveLiveCarousel(1);
    }, 4000); // Change slide every 4 seconds
}

function restartLiveAutoPlay() {
    clearInterval(liveAutoPlayInterval);
    startLiveAutoPlay();
}

function addLiveTouchSupport() {
    const track = document.getElementById('live-carousel-track');
    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    
    track.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        isDragging = true;
        clearInterval(liveAutoPlayInterval);
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
                moveLiveCarousel(1); // Swipe left - next slide
            } else {
                moveLiveCarousel(-1); // Swipe right - previous slide
            }
        }
        
        startLiveAutoPlay();
    });
}

// Simple tab switching functionality
function switchSimpleTab(tabName) {
    // Remove active class from all tabs
    document.querySelectorAll('.simple-chat-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Hide all tab contents
    document.querySelectorAll('.simple-chat-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Activate selected tab
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    document.getElementById(`simple-${tabName}-tab`).classList.remove('hidden');
}

// Subscribe functionality
function subscribeToChannel(channelId) {
    // Here you can implement actual subscription logic
    const btn = document.querySelector('.subscribe-btn');
    const icon = btn.querySelector('.subscribe-icon');
    
    if (btn.classList.contains('subscribed')) {
        btn.classList.remove('subscribed');
        btn.innerHTML = '<span class="subscribe-icon">🔔</span>Suscribirse';
        showNotification('Te has desuscrito del canal');
    } else {
        btn.classList.add('subscribed');
        btn.innerHTML = '<span class="subscribe-icon">✅</span>Suscrito';
        showNotification('¡Te has suscrito al canal!');
    }
}

// Social sharing functions
function shareOnFacebook() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
}

function shareOnTwitter() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    window.open(`https://twitter.com/intent/tweet?url=${url}&text=${title}`, '_blank', 'width=600,height=400');
}

function shareOnWhatsApp() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    window.open(`https://wa.me/?text=${title} ${url}`, '_blank');
}

function shareOnTelegram() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    window.open(`https://t.me/share/url?url=${url}&text=${title}`, '_blank');
}

function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        showNotification('¡Enlace copiado al portapapeles!');
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = window.location.href;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('¡Enlace copiado al portapapeles!');
    });
}

function showNotification(message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Hide and remove notification
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => document.body.removeChild(notification), 300);
    }, 3000);
}

// Initialize page functionality
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!isset($error)): ?>
        const streamType = '<?php echo $channel['stream_type']; ?>';
        const streamUrl = '<?php echo addslashes($channel['stream_url']); ?>';
        const channelId = <?php echo $channelId; ?>;
        
        // Initialize player
        initializePlayer(streamType, streamUrl);
        
        // Initialize live carousel
        initializeLiveCarousel();
        
        // Initialize chat
        initializeChat(channelId);
        
        // Update page title with live indicator
        document.title = '🔴 <?php echo addslashes($channel['name']); ?> - <?php echo SITE_NAME; ?>';
    <?php endif; ?>
});

// Check subscription status on page load
document.addEventListener('DOMContentLoaded', function() {
    const channelId = <?php echo $channelId; ?>;
    const subscribeBtn = document.getElementById('subscribe-btn');
    const subscriberCountElem = document.getElementById('subscriber-count');

    fetch(`subscription_status.php?channel_id=${channelId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.subscribed) {
                subscribeBtn.classList.add('subscribed');
                subscribeBtn.innerHTML = '<span class="subscribe-icon">✅</span>Suscrito';
            }
        });

    window.toggleSubscription = function(channelId) {
        if (subscribeBtn.classList.contains('subscribed')) {
            // Unsubscribe
            fetch('/unsubscribe.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `channel_id=${channelId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    subscribeBtn.classList.remove('subscribed');
                    subscribeBtn.innerHTML = '<span class="subscribe-icon">🔔</span>Suscribirse';
                    updateSubscriberCount(-1);
                    showNotification(data.message);
                } else {
                    alert(data.error);
                }
            });
        } else {
            // Subscribe
            fetch('/subscribe.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `channel_id=${channelId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    subscribeBtn.classList.add('subscribed');
                    subscribeBtn.innerHTML = '<span class="subscribe-icon">✅</span>Suscrito';
                    updateSubscriberCount(1);
                    showNotification(data.message);
                } else {
                    alert(data.error);
                }
            });
        }
    };

    function updateSubscriberCount(change) {
        let countText = subscriberCountElem.textContent;
        let count = parseFloat(countText.replace(/[^0-9\.]/g, ''));
        if (isNaN(count)) count = 0;
        count += change;
        if (count < 0) count = 0;
        subscriberCountElem.textContent = count.toLocaleString() + ' suscriptores';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

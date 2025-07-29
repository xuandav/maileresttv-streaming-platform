<?php
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function processStreamUrl($streamType, $streamUrl) {
    switch($streamType) {
        case 'youtube':
            // Extract video ID from YouTube URL
            if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $streamUrl, $matches)) {
                return $matches[1];
            }
            return false;
            
        case 'twitch':
            // Extract channel name from Twitch URL
            if (preg_match('/twitch\.tv\/([^\/\n?#]+)/', $streamUrl, $matches)) {
                return $matches[1];
            }
            return false;
            
        case 'm3u':
        case 'rtmp':
            // Return URL as-is for M3U and RTMP
            return filter_var($streamUrl, FILTER_VALIDATE_URL) ? $streamUrl : false;
            
        default:
            return false;
    }
}

function validateStreamUrl($streamType, $streamUrl) {
    $processedUrl = processStreamUrl($streamType, $streamUrl);
    return $processedUrl !== false;
}

function getStreamEmbedCode($streamType, $streamUrl, $channelName) {
    $processedUrl = processStreamUrl($streamType, $streamUrl);
    
    if (!$processedUrl) {
        return "<div class='stream-error'>URL de stream inválida</div>";
    }
    
    switch($streamType) {
        case 'youtube':
            return "
            <div class='player-container'>
                <iframe 
                    src='https://www.youtube.com/embed/{$processedUrl}?autoplay=1&mute=1' 
                    frameborder='0' 
                    allowfullscreen
                    allow='autoplay; encrypted-media'>
                </iframe>
                <div class='player-logo'>
                    <img src='" . LOGO_PATH . "' alt='" . SITE_NAME . "'>
                </div>
            </div>";
            
        case 'twitch':
            $domain = parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost';
            return "
            <div class='player-container'>
                <iframe 
                    src='https://player.twitch.tv/?channel={$processedUrl}&parent={$domain}' 
                    frameborder='0' 
                    allowfullscreen
                    scrolling='no'>
                </iframe>
                <div class='player-logo'>
                    <img src='" . LOGO_PATH . "' alt='" . SITE_NAME . "'>
                </div>
            </div>";
            
        case 'm3u':
            return "
            <div class='player-container'>
                <video 
                    id='hls-player' 
                    class='video-js vjs-default-skin' 
                    controls 
                    preload='auto' 
                    width='" . DEFAULT_PLAYER_WIDTH . "' 
                    height='" . DEFAULT_PLAYER_HEIGHT . "'
                    data-setup='{}'>
                    <source src='{$processedUrl}' type='application/x-mpegURL'>
                    <p class='vjs-no-js'>
                        Para ver este video necesitas habilitar JavaScript y considerar actualizar a un 
                        <a href='https://videojs.com/html5-video-support/' target='_blank'>
                            navegador que soporte HTML5 video
                        </a>.
                    </p>
                </video>
                <div class='player-logo'>
                    <img src='" . LOGO_PATH . "' alt='" . SITE_NAME . "'>
                </div>
            </div>";
            
        case 'rtmp':
            return "
            <div class='player-container'>
                <video 
                    id='rtmp-player' 
                    class='video-js vjs-default-skin' 
                    controls 
                    preload='auto' 
                    width='" . DEFAULT_PLAYER_WIDTH . "' 
                    height='" . DEFAULT_PLAYER_HEIGHT . "'
                    data-setup='{}'>
                    <source src='{$processedUrl}' type='rtmp/mp4'>
                    <p class='vjs-no-js'>
                        Para ver este video necesitas habilitar JavaScript y un plugin compatible con RTMP.
                    </p>
                </video>
                <div class='player-logo'>
                    <img src='" . LOGO_PATH . "' alt='" . SITE_NAME . "'>
                </div>
            </div>";
            
        default:
            return "<div class='stream-error'>Formato de stream no soportado</div>";
    }
}

function formatSubscriberCount($count) {
    if ($count >= 1000000) {
        return number_format($count / 1000000, 1) . 'M';
    } elseif ($count >= 1000) {
        return number_format($count / 1000, 1) . 'K';
    }
    return number_format($count);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'hace ' . $time . ' segundos';
    if ($time < 3600) return 'hace ' . floor($time/60) . ' minutos';
    if ($time < 86400) return 'hace ' . floor($time/3600) . ' horas';
    if ($time < 2592000) return 'hace ' . floor($time/86400) . ' días';
    if ($time < 31536000) return 'hace ' . floor($time/2592000) . ' meses';
    return 'hace ' . floor($time/31536000) . ' años';
}

function sanitizeInput($input) {
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

function isValidAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isValidAdmin()) {
        header('Location: admin_login.php');
        exit;
    }
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate an SEO-friendly slug from a Spanish string
 * Handles Spanish accents and special characters
 *
 * @param string $string
 * @return string
 */
function generateSpanishSlug($string) {
    // Convert to lowercase
    $slug = mb_strtolower($string, 'UTF-8');
    
    // Replace Spanish accented characters
    $accents = array(
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ā' => 'a', 'ã' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'ē' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ī' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'ō' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ū' => 'u',
        'ñ' => 'n', 'ç' => 'c'
    );
    
    $slug = strtr($slug, $accents);
    
    // Remove any character that is not alphanumeric, space, or dash
    $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);
    
    // Replace multiple spaces or dashes with single dash
    $slug = preg_replace('/[\s\-]+/', '-', $slug);
    
    // Remove leading and trailing dashes
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Generate SEO-friendly URL for live channel
 *
 * @param array $channel Channel data with 'name'
 * @return string
 */
function generateChannelUrl($channel) {
    $slug = generateSpanishSlug($channel['name']);
    return "/en-vivo/{$slug}";
}

/**
 * Generate SEO-friendly URL for category
 *
 * @param string $category
 * @return string
 */
function generateCategoryUrl($category) {
    $slug = generateSpanishSlug($category);
    return "/categoria/{$slug}";
}

/**
 * Generate SEO-friendly URL for country
 *
 * @param string $country
 * @return string
 */
function generateCountryUrl($country) {
    $slug = generateSpanishSlug($country);
    return "/pais/{$slug}";
}

/**
 * Generate SEO-friendly URL for search
 *
 * @param string $searchTerm
 * @return string
 */
function generateSearchUrl($searchTerm) {
    $slug = generateSpanishSlug($searchTerm);
    return "/buscar/{$slug}";
}

/**
 * Extract channel slug from SEO-friendly URL
 *
 * @param string $uri Request URI
 * @return string|false Channel slug or false if not found
 */
function extractChannelSlugFromUrl($uri) {
    if (preg_match('/\/en-vivo\/([a-z0-9\-]+)\/?/', $uri, $matches)) {
        return $matches[1];
    }
    return false;
}

/**
 * Find channel by slug
 *
 * @param PDO $pdo Database connection
 * @param string $slug Channel slug
 * @return array|false Channel data or false if not found
 */
function findChannelBySlug($pdo, $slug) {
    try {
        // First, try to find by exact slug match
        $stmt = $pdo->prepare("SELECT * FROM channels WHERE is_active = 1");
        $stmt->execute();
        $channels = $stmt->fetchAll();
        
        foreach ($channels as $channel) {
            if (generateSpanishSlug($channel['name']) === $slug) {
                return $channel;
            }
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Convert category slug back to original format
 *
 * @param string $slug
 * @return string
 */
function slugToCategory($slug) {
    // Replace dashes with spaces and capitalize first letter of each word
    return ucwords(str_replace('-', ' ', $slug));
}

/**
 * Convert country slug back to original format
 *
 * @param string $slug
 * @return string
 */
function slugToCountry($slug) {
    // Handle special cases for Spanish country names
    $countryMap = array(
        'espana' => 'España',
        'mexico' => 'México',
        'argentina' => 'Argentina',
        'colombia' => 'Colombia',
        'chile' => 'Chile',
        'peru' => 'Perú',
        'venezuela' => 'Venezuela',
        'ecuador' => 'Ecuador',
        'bolivia' => 'Bolivia',
        'paraguay' => 'Paraguay',
        'uruguay' => 'Uruguay',
        'costa-rica' => 'Costa Rica',
        'panama' => 'Panamá',
        'guatemala' => 'Guatemala',
        'honduras' => 'Honduras',
        'el-salvador' => 'El Salvador',
        'nicaragua' => 'Nicaragua',
        'republica-dominicana' => 'República Dominicana',
        'puerto-rico' => 'Puerto Rico'
    );
    
    return isset($countryMap[$slug]) ? $countryMap[$slug] : ucwords(str_replace('-', ' ', $slug));
}
?>

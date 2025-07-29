<?php
// Simple router for PHP built-in server to handle SEO-friendly URLs

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Handle API endpoints that are being called from SEO-friendly URLs
if (preg_match('/^\/en-vivo\/([a-z0-9_\-]+\.php)/', $path, $matches)) {
    // API calls from channel pages: /en-vivo/get_chat.php -> /get_chat.php
    $apiFile = $matches[1];
    if (file_exists($apiFile)) {
        require $apiFile;
        return true;
    }
}

// Handle SEO-friendly URLs
if (preg_match('/^\/en-vivo\/([a-z0-9\-]+)\/?$/', $path, $matches)) {
    // Channel URL: /en-vivo/canal-slug
    $_GET['channel_slug'] = $matches[1];
    require 'live.php';
    return true;
} elseif (preg_match('/^\/categoria\/([a-z0-9\-]+)\/?$/', $path, $matches)) {
    // Category URL: /categoria/category-slug
    $_GET['category'] = $matches[1];
    require 'index.php';
    return true;
} elseif (preg_match('/^\/pais\/([a-z0-9\-]+)\/?$/', $path, $matches)) {
    // Country URL: /pais/country-slug
    $_GET['country'] = $matches[1];
    require 'index.php';
    return true;
} elseif (preg_match('/^\/buscar\/([a-z0-9\-]+)\/?$/', $path, $matches)) {
    // Search URL: /buscar/search-term
    $_GET['search'] = str_replace('-', ' ', $matches[1]);
    require 'index.php';
    return true;
}

// Let the server handle other requests normally
return false;
?>

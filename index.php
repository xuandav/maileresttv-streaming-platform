<?php
// Simple router to redirect all requests to the public folder
$requestUri = $_SERVER['REQUEST_URI'];

// If the request is already for the public folder, serve normally
if (strpos($requestUri, '/public/') === 0) {
    // Remove /public from URI and serve the file
    $file = __DIR__ . $requestUri;
    if (is_file($file)) {
        return false; // Let the server handle the request
    }
}

// Redirect all other requests to public folder
$redirectUri = '/public' . $requestUri;
header("Location: $redirectUri");
exit;
?>

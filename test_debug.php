<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

echo "Testing database connection and channels...\n";

try {
    $stmt = $pdo->query('SELECT id, name FROM channels WHERE is_active = 1 LIMIT 5');
    $channels = $stmt->fetchAll();
    
    echo "Found " . count($channels) . " active channels:\n";
    foreach ($channels as $channel) {
        $slug = generateSpanishSlug($channel['name']);
        $url = generateChannelUrl($channel);
        echo "- ID: " . $channel['id'] . ", Name: " . $channel['name'] . ", Slug: " . $slug . ", URL: " . $url . "\n";
    }
    
    // Test slug generation and finding
    if (!empty($channels)) {
        $testChannel = $channels[0];
        $testSlug = generateSpanishSlug($testChannel['name']);
        echo "\nTesting slug lookup for: " . $testChannel['name'] . " -> " . $testSlug . "\n";
        
        $foundChannel = findChannelBySlug($pdo, $testSlug);
        if ($foundChannel) {
            echo "SUCCESS: Found channel by slug: " . $foundChannel['name'] . "\n";
        } else {
            echo "ERROR: Could not find channel by slug\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>

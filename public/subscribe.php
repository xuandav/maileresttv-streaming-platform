<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $channelId = isset($_POST['channel_id']) ? (int)$_POST['channel_id'] : 0;
    $userIdentifier = $_SERVER['REMOTE_ADDR']; // Using IP as user identifier for simplicity

    if (!$channelId) {
        throw new Exception('ID de canal requerido');
    }

    // Check if already subscribed
    $stmt = $pdo->prepare("SELECT id FROM channel_subscriptions WHERE channel_id = ? AND user_identifier = ?");
    $stmt->execute([$channelId, $userIdentifier]);
    if ($stmt->fetch()) {
        throw new Exception('Ya estás suscrito a este canal');
    }

    // Insert subscription
    $insertStmt = $pdo->prepare("INSERT INTO channel_subscriptions (channel_id, user_identifier) VALUES (?, ?)");
    $insertStmt->execute([$channelId, $userIdentifier]);

    // Update subscriber count in channels table
    $updateStmt = $pdo->prepare("UPDATE channels SET subscriber_count = subscriber_count + 1 WHERE id = ?");
    $updateStmt->execute([$channelId]);

    echo json_encode(['success' => true, 'message' => 'Suscripción exitosa']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

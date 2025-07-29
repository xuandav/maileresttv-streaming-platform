<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $channelId = isset($_GET['channel_id']) ? (int)$_GET['channel_id'] : 0;
    $userIdentifier = $_SERVER['REMOTE_ADDR'];

    if (!$channelId) {
        throw new Exception('ID de canal requerido');
    }

    $stmt = $pdo->prepare("SELECT id FROM channel_subscriptions WHERE channel_id = ? AND user_identifier = ?");
    $stmt->execute([$channelId, $userIdentifier]);
    $subscribed = $stmt->fetch() ? true : false;

    echo json_encode(['success' => true, 'subscribed' => $subscribed]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

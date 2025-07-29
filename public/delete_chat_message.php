<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Simple admin check (for demo purposes, replace with real auth)
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
    if (!$messageId) {
        throw new Exception('ID de mensaje requerido');
    }

    $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE id = ?");
    $stmt->execute([$messageId]);

    echo json_encode(['success' => true, 'message' => 'Mensaje eliminado']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

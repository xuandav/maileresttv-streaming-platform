<?php
header('Content-Type: application/json');

require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Solo permitir solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener y validar entrada
    $channelId = isset($_POST['channel_id']) ? (int)$_POST['channel_id'] : 0;
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';

    if (!$channelId) {
        throw new Exception('ID de canal requerido');
    }

    if (empty($username)) {
        throw new Exception('El nombre no puede estar vacío');
    }

    if (empty($message)) {
        throw new Exception('El mensaje no puede estar vacío');
    }

    if (strlen($message) > 500) {
        throw new Exception('El mensaje es demasiado largo (máximo 500 caracteres)');
    }

    if (strlen($username) > 50) {
        throw new Exception('El nombre de usuario es demasiado largo (máximo 50 caracteres)');
    }

    // Verificar si el canal existe y está activo
    $channelStmt = $pdo->prepare("SELECT id FROM channels WHERE id = ? AND is_active = 1");
    $channelStmt->execute([$channelId]);
    if (!$channelStmt->fetch()) {
        throw new Exception('Canal no encontrado o inactivo');
    }

    // Limpiar mensajes antiguos (más de 10 días)
    $cleanupStmt = $pdo->prepare("DELETE FROM chat_messages WHERE created_at < datetime('now', '-10 days')");
    $cleanupStmt->execute();

    // Protección básica contra spam: verificar si el último mensaje es idéntico y reciente
    $lastMessageStmt = $pdo->prepare("
        SELECT message, created_at 
        FROM chat_messages 
        WHERE channel_id = ? AND username = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $lastMessageStmt->execute([$channelId, $username]);
    $lastMessage = $lastMessageStmt->fetch();
    
    if ($lastMessage && $lastMessage['message'] === $message) {
        $lastTime = strtotime($lastMessage['created_at']);
        if ((time() - $lastTime) < 30) {
            throw new Exception('Por favor, espera antes de enviar el mismo mensaje');
        }
    }

    // Limitación de velocidad: máximo 10 mensajes por minuto por canal
    $recentMessagesStmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM chat_messages 
        WHERE channel_id = ? AND created_at > datetime('now', '-1 minute')
    ");
    $recentMessagesStmt->execute([$channelId]);
    $recentCount = $recentMessagesStmt->fetchColumn();
    
    if ($recentCount >= 10) {
        throw new Exception('Demasiados mensajes. Por favor, espera un momento');
    }

    // Insertar nuevo mensaje
    $insertStmt = $pdo->prepare("
        INSERT INTO chat_messages (channel_id, username, message, created_at) 
        VALUES (?, ?, ?, datetime('now'))
    ");
    $insertStmt->execute([$channelId, $username, $message]);
    
    $messageId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado correctamente',
        'data' => [
            'id' => $messageId,
            'channel_id' => $channelId,
            'username' => $username,
            'message' => $message,
            'created_at' => date('c')
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Error de base de datos en post_chat.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor. Inténtalo de nuevo.'
    ]);
}
?>

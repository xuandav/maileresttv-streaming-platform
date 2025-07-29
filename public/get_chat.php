<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Solo permitir solicitudes GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener y validar entrada
    $channelId = isset($_GET['channel_id']) ? (int)$_GET['channel_id'] : 0;
    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 20; // Máximo 50 mensajes
    
    // Validación
    if (!$channelId) {
        throw new Exception('ID de canal requerido');
    }
    
    // Verificar si el canal existe y está activo
    $channelStmt = $pdo->prepare("SELECT id, name FROM channels WHERE id = ? AND is_active = 1");
    $channelStmt->execute([$channelId]);
    $channel = $channelStmt->fetch();
    
    if (!$channel) {
        throw new Exception('Canal no encontrado o inactivo');
    }
    
    // Construir consulta basada en si queremos mensajes nuevos o carga inicial
    if ($lastId > 0) {
        // Obtener solo mensajes nuevos después del último ID
        $sql = "
            SELECT id, channel_id, username, message, created_at 
            FROM chat_messages 
            WHERE channel_id = ? AND id > ? 
            ORDER BY created_at ASC, id ASC
            LIMIT ?
        ";
        $params = [$channelId, $lastId, $limit];
    } else {
        // Obtener mensajes recientes para carga inicial
        $sql = "
            SELECT id, channel_id, username, message, created_at 
            FROM chat_messages 
            WHERE channel_id = ? 
            ORDER BY created_at DESC, id DESC
            LIMIT ?
        ";
        $params = [$channelId, $limit];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si esta fue una carga inicial, invertir el orden para que los mensajes más nuevos estén abajo
    if ($lastId === 0) {
        $messages = array_reverse($messages);
    }
    
    // Formatear timestamps para mostrar
    foreach ($messages as &$message) {
        $message['formatted_time'] = date('d/m/Y H:i', strtotime($message['created_at']));
        $message['timestamp'] = strtotime($message['created_at']);
    }
    
    // Obtener conteo total de mensajes para este canal
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM chat_messages WHERE channel_id = ?");
    $countStmt->execute([$channelId]);
    $totalMessages = $countStmt->fetchColumn();
    
    // Obtener conteo de usuarios en línea (aproximado - usuarios que enviaron mensajes en los últimos 5 minutos)
    $onlineStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT username) as online_count 
        FROM chat_messages 
        WHERE channel_id = ? AND created_at > datetime('now', '-5 minutes')
    ");
    $onlineStmt->execute([$channelId]);
    $onlineCount = $onlineStmt->fetchColumn();
    
    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'channel' => [
            'id' => $channel['id'],
            'name' => $channel['name']
        ],
        'stats' => [
            'total_messages' => (int)$totalMessages,
            'online_users' => (int)$onlineCount,
            'messages_returned' => count($messages)
        ],
        'pagination' => [
            'last_id' => count($messages) > 0 ? end($messages)['id'] : $lastId,
            'has_more' => count($messages) === $limit
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'messages' => [],
        'stats' => [
            'total_messages' => 0,
            'online_users' => 0,
            'messages_returned' => 0
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error de base de datos en get_chat.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor. Inténtalo de nuevo.',
        'messages' => [],
        'stats' => [
            'total_messages' => 0,
            'online_users' => 0,
            'messages_returned' => 0
        ]
    ]);
}

// Función auxiliar para formatear tiempo transcurrido (si no está ya definida en functions.php)
if (!function_exists('formatTimeAgo')) {
    function formatTimeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'hace ' . $time . 's';
        if ($time < 3600) return 'hace ' . floor($time/60) . 'm';
        if ($time < 86400) return 'hace ' . floor($time/3600) . 'h';
        if ($time < 2592000) return 'hace ' . floor($time/86400) . ' días';
        if ($time < 31536000) return 'hace ' . floor($time/2592000) . ' meses';
        return 'hace ' . floor($time/31536000) . ' años';
    }
}
?>

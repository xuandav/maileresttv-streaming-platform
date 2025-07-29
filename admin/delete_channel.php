<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check admin authentication
requireAdmin();

$error = '';
$success = '';

// Get channel ID
$channelId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$channelId) {
    header('Location: manage_channels.php?error=invalid_id');
    exit;
}

try {
    // Get channel information before deletion
    $stmt = $pdo->prepare("SELECT name FROM channels WHERE id = ?");
    $stmt->execute([$channelId]);
    $channel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$channel) {
        header('Location: manage_channels.php?error=channel_not_found');
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete related chat messages first (due to foreign key constraint)
    $deleteChatStmt = $pdo->prepare("DELETE FROM chat_messages WHERE channel_id = ?");
    $deleteChatStmt->execute([$channelId]);
    $deletedMessages = $deleteChatStmt->rowCount();
    
    // Delete the channel
    $deleteChannelStmt = $pdo->prepare("DELETE FROM channels WHERE id = ?");
    $deleteChannelStmt->execute([$channelId]);
    
    if ($deleteChannelStmt->rowCount() > 0) {
        // Commit transaction
        $pdo->commit();
        
        // Redirect with success message
        $successMessage = urlencode("Canal '{$channel['name']}' eliminado exitosamente. También se eliminaron {$deletedMessages} mensajes de chat asociados.");
        header("Location: manage_channels.php?success=" . $successMessage);
        exit;
    } else {
        // Rollback transaction
        $pdo->rollBack();
        header('Location: manage_channels.php?error=delete_failed');
        exit;
    }
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Delete channel error: " . $e->getMessage());
    
    // Check if it's a foreign key constraint error
    if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
        $errorMessage = urlencode("No se puede eliminar el canal porque tiene datos relacionados. Contacta al administrador del sistema.");
    } else {
        $errorMessage = urlencode("Error al eliminar el canal. Por favor, inténtalo de nuevo.");
    }
    
    header("Location: manage_channels.php?error=" . $errorMessage);
    exit;
}
?>

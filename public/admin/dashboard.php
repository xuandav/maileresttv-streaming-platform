<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check admin authentication
requireAdmin();

$pageTitle = 'Dashboard - Panel de Administración';

try {
    // Get statistics
    $stats = [];
    
    // Total channels
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM channels");
    $stats['total_channels'] = $stmt->fetch()['total'];
    
    // Active channels
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM channels WHERE is_active = 1");
    $stats['active_channels'] = $stmt->fetch()['active'];
    
    // Total chat messages
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chat_messages");
    $stats['total_messages'] = $stmt->fetch()['total'];
    
    // Messages today
    $stmt = $pdo->query("SELECT COUNT(*) as today FROM chat_messages WHERE DATE(created_at) = CURDATE()");
    $stats['messages_today'] = $stmt->fetch()['today'];
    
    // Top channels by subscribers
    $stmt = $pdo->query("
        SELECT name, subscriber_count, category, country 
        FROM channels 
        WHERE is_active = 1 
        ORDER BY subscriber_count DESC 
        LIMIT 5
    ");
    $topChannels = $stmt->fetchAll();
    
    // Recent channels
    $stmt = $pdo->query("
        SELECT name, category, country, created_at 
        FROM channels 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentChannels = $stmt->fetchAll();
    
    // Channel distribution by category
    $stmt = $pdo->query("
        SELECT category, COUNT(*) as count 
        FROM channels 
        WHERE is_active = 1 
        GROUP BY category 
        ORDER BY count DESC
    ");
    $categoryStats = $stmt->fetchAll();
    
    // Channel distribution by country
    $stmt = $pdo->query("
        SELECT country, COUNT(*) as count 
        FROM channels 
        WHERE is_active = 1 
        GROUP BY country 
        ORDER BY count DESC
    ");
    $countryStats = $stmt->fetchAll();
    
    // Recent chat activity
    $stmt = $pdo->query("
        SELECT cm.message, cm.username, cm.created_at, c.name as channel_name
        FROM chat_messages cm
        JOIN channels c ON cm.channel_id = c.id
        ORDER BY cm.created_at DESC
        LIMIT 10
    ");
    $recentMessages = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Error al cargar las estadísticas.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../public/assets/css/styles.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="container">
                <div class="admin-header-content">
                    <div class="admin-logo">
                        <h1><?php echo SITE_NAME; ?> - Admin</h1>
                    </div>
                    <nav class="admin-nav">
                        <a href="dashboard.php" class="active">Dashboard</a>
                        <a href="manage_channels.php">Gestionar Canales</a>
                        <a href="add_channel.php">Agregar Canal</a>
                        <a href="../public/index.php" target="_blank">Ver Sitio</a>
                        <a href="admin_login.php?logout=1" class="logout-btn">Cerrar Sesión</a>
                    </nav>
                </div>
            </div>
        </header>

        <main class="admin-main">
            <div class="container">
                <div class="dashboard-header">
                    <h2>Dashboard</h2>
                    <p>Bienvenido, <?php echo escape($_SESSION['admin_username']); ?>. Aquí tienes un resumen de tu plataforma.</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📺</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['total_channels']); ?></h3>
                            <p>Total Canales</p>
                            <small><?php echo $stats['active_channels']; ?> activos</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">💬</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['total_messages']); ?></h3>
                            <p>Mensajes de Chat</p>
                            <small><?php echo $stats['messages_today']; ?> hoy</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🔴</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['active_channels']; ?></h3>
                            <p>Canales Activos</p>
                            <small>En transmisión</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <h3><?php echo count($categoryStats); ?></h3>
                            <p>Categorías</p>
                            <small>Diferentes tipos</small>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Content Grid -->
                <div class="dashboard-grid">
                    <!-- Top Channels -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Canales Más Populares</h3>
                            <a href="manage_channels.php" class="card-action">Ver todos</a>
                        </div>
                        <div class="card-content">
                            <?php if (empty($topChannels)): ?>
                                <p class="no-data">No hay canales disponibles.</p>
                            <?php else: ?>
                                <div class="channel-list">
                                    <?php foreach ($topChannels as $channel): ?>
                                        <div class="channel-item">
                                            <div class="channel-info">
                                                <h4><?php echo escape($channel['name']); ?></h4>
                                                <p><?php echo escape($channel['category']); ?> • <?php echo escape($channel['country']); ?></p>
                                            </div>
                                            <div class="channel-stats">
                                                <span class="subscriber-count">
                                                    <?php echo formatSubscriberCount($channel['subscriber_count']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Channels -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Canales Recientes</h3>
                            <a href="add_channel.php" class="card-action">Agregar nuevo</a>
                        </div>
                        <div class="card-content">
                            <?php if (empty($recentChannels)): ?>
                                <p class="no-data">No hay canales recientes.</p>
                            <?php else: ?>
                                <div class="channel-list">
                                    <?php foreach ($recentChannels as $channel): ?>
                                        <div class="channel-item">
                                            <div class="channel-info">
                                                <h4><?php echo escape($channel['name']); ?></h4>
                                                <p><?php echo escape($channel['category']); ?> • <?php echo escape($channel['country']); ?></p>
                                            </div>
                                            <div class="channel-stats">
                                                <small><?php echo timeAgo($channel['created_at']); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Category Distribution -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Distribución por Categorías</h3>
                        </div>
                        <div class="card-content">
                            <?php if (empty($categoryStats)): ?>
                                <p class="no-data">No hay datos de categorías.</p>
                            <?php else: ?>
                                <div class="stats-list">
                                    <?php foreach ($categoryStats as $stat): ?>
                                        <div class="stats-item">
                                            <span class="stats-label"><?php echo escape($stat['category']); ?></span>
                                            <span class="stats-value"><?php echo $stat['count']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Country Distribution -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Distribución por Países</h3>
                        </div>
                        <div class="card-content">
                            <?php if (empty($countryStats)): ?>
                                <p class="no-data">No hay datos de países.</p>
                            <?php else: ?>
                                <div class="stats-list">
                                    <?php foreach ($countryStats as $stat): ?>
                                        <div class="stats-item">
                                            <span class="stats-label">📍 <?php echo escape($stat['country']); ?></span>
                                            <span class="stats-value"><?php echo $stat['count']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Chat Activity -->
                    <div class="dashboard-card full-width">
                        <div class="card-header">
                            <h3>Actividad Reciente del Chat</h3>
                        </div>
                        <div class="card-content">
                            <?php if (empty($recentMessages)): ?>
                                <p class="no-data">No hay mensajes recientes.</p>
                            <?php else: ?>
                                <div class="messages-list">
                                    <?php foreach ($recentMessages as $message): ?>
                                        <div class="message-item">
                                            <div class="message-info">
                                                <strong><?php echo escape($message['username']); ?></strong>
                                                <span class="channel-name">en <?php echo escape($message['channel_name']); ?></span>
                                                <span class="message-time"><?php echo timeAgo($message['created_at']); ?></span>
                                            </div>
                                            <div class="message-content">
                                                <?php echo escape($message['message']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Acciones Rápidas</h3>
                    <div class="actions-grid">
                        <a href="add_channel.php" class="action-btn">
                            <div class="action-icon">➕</div>
                            <div class="action-text">
                                <h4>Agregar Canal</h4>
                                <p>Añadir un nuevo canal a la plataforma</p>
                            </div>
                        </a>
                        
                        <a href="manage_channels.php" class="action-btn">
                            <div class="action-icon">⚙️</div>
                            <div class="action-text">
                                <h4>Gestionar Canales</h4>
                                <p>Editar o eliminar canales existentes</p>
                            </div>
                        </a>
                        
                        <a href="../public/index.php" target="_blank" class="action-btn">
                            <div class="action-icon">👁️</div>
                            <div class="action-text">
                                <h4>Ver Sitio</h4>
                                <p>Visitar la página pública</p>
                            </div>
                        </a>
                        
                        <a href="#" onclick="refreshStats()" class="action-btn">
                            <div class="action-icon">🔄</div>
                            <div class="action-text">
                                <h4>Actualizar Stats</h4>
                                <p>Refrescar las estadísticas</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Auto-refresh stats every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);

        // Refresh stats function
        function refreshStats() {
            location.reload();
        }

        // Add loading animation to action buttons
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.getAttribute('href') !== '#') {
                    this.style.opacity = '0.7';
                    this.style.transform = 'scale(0.95)';
                }
            });
        });

        // Real-time clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-ES');
            const dateString = now.toLocaleDateString('es-ES');
            
            let clockElement = document.getElementById('admin-clock');
            if (!clockElement) {
                clockElement = document.createElement('div');
                clockElement.id = 'admin-clock';
                clockElement.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: rgba(0,0,0,0.8);
                    color: white;
                    padding: 0.5rem 1rem;
                    border-radius: 6px;
                    font-size: 0.9rem;
                    z-index: 1000;
                `;
                document.body.appendChild(clockElement);
            }
            
            clockElement.innerHTML = `${timeString}<br><small>${dateString}</small>`;
        }

        // Update clock every second
        updateClock();
        setInterval(updateClock, 1000);
    </script>

    <style>
        .admin-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .admin-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .admin-nav a {
            color: #bdc3c7;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .logout-btn {
            background: #e74c3c !important;
            color: white !important;
        }

        .logout-btn:hover {
            background: #c0392b !important;
        }

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-header h2 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .dashboard-header p {
            color: #666;
            margin: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .stat-content h3 {
            font-size: 2rem;
            margin: 0;
            color: #333;
        }

        .stat-content p {
            margin: 0.25rem 0;
            color: #666;
            font-weight: 500;
        }

        .stat-content small {
            color: #999;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .dashboard-card.full-width {
            grid-column: 1 / -1;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            margin: 0;
            color: #333;
        }

        .card-action {
            color: #3498db;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .card-action:hover {
            text-decoration: underline;
        }

        .card-content {
            padding: 1.5rem;
        }

        .no-data {
            text-align: center;
            color: #999;
            font-style: italic;
            margin: 0;
        }

        .channel-list,
        .stats-list,
        .messages-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .channel-item,
        .stats-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .channel-info h4 {
            margin: 0 0 0.25rem 0;
            color: #333;
        }

        .channel-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .subscriber-count {
            font-weight: bold;
            color: #e74c3c;
        }

        .stats-label {
            font-weight: 500;
            color: #333;
        }

        .stats-value {
            background: #3498db;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .message-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #3498db;
        }

        .message-info {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .channel-name {
            color: #666;
        }

        .message-time {
            color: #999;
            margin-left: auto;
        }

        .message-content {
            color: #333;
            line-height: 1.4;
        }

        .quick-actions {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .quick-actions h3 {
            margin-bottom: 1.5rem;
            color: #333;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: #ecf0f1;
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: 2rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 10px;
        }

        .action-text h4 {
            margin: 0 0 0.25rem 0;
            color: #333;
        }

        .action-text p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .admin-header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .admin-nav {
                flex-wrap: wrap;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>

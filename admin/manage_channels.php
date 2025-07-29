<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check admin authentication
requireAdmin();

$pageTitle = 'Gestionar Canales - Panel de Administración';

// Handle bulk actions
if ($_POST && isset($_POST['bulk_action']) && isset($_POST['selected_channels'])) {
    $action = $_POST['bulk_action'];
    $selectedChannels = $_POST['selected_channels'];
    
    if (!empty($selectedChannels) && is_array($selectedChannels)) {
        $placeholders = str_repeat('?,', count($selectedChannels) - 1) . '?';
        
        try {
            switch ($action) {
                case 'activate':
                    $stmt = $pdo->prepare("UPDATE channels SET is_active = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedChannels);
                    $success = count($selectedChannels) . " canales activados correctamente.";
                    break;
                    
                case 'deactivate':
                    $stmt = $pdo->prepare("UPDATE channels SET is_active = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedChannels);
                    $success = count($selectedChannels) . " canales desactivados correctamente.";
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM channels WHERE id IN ($placeholders)");
                    $stmt->execute($selectedChannels);
                    $success = count($selectedChannels) . " canales eliminados correctamente.";
                    break;
            }
        } catch (PDOException $e) {
            $error = "Error al realizar la acción: " . $e->getMessage();
        }
    }
}

// Get filter and search parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$countryFilter = isset($_GET['country']) ? sanitizeInput($_GET['country']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$streamTypeFilter = isset($_GET['stream_type']) ? sanitizeInput($_GET['stream_type']) : '';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    // Build query
    $whereConditions = [];
    $params = [];
    
    if ($search) {
        $whereConditions[] = "(name LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($categoryFilter) {
        $whereConditions[] = "category = ?";
        $params[] = $categoryFilter;
    }
    
    if ($countryFilter) {
        $whereConditions[] = "country = ?";
        $params[] = $countryFilter;
    }
    
    if ($statusFilter !== '') {
        $whereConditions[] = "is_active = ?";
        $params[] = $statusFilter;
    }
    
    if ($streamTypeFilter) {
        $whereConditions[] = "stream_type = ?";
        $params[] = $streamTypeFilter;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM channels $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalChannels = $countStmt->fetch()['total'];
    $totalPages = ceil($totalChannels / $perPage);
    
    // Get channels
    $sql = "SELECT * FROM channels $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $channels = $stmt->fetchAll();
    
    // Get filter options
    $categoriesStmt = $pdo->query("SELECT DISTINCT category FROM channels ORDER BY category");
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $countriesStmt = $pdo->query("SELECT DISTINCT country FROM channels ORDER BY country");
    $countries = $countriesStmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    error_log("Manage channels error: " . $e->getMessage());
    $error = "Error al cargar los canales.";
    $channels = [];
    $categories = [];
    $countries = [];
    $totalChannels = 0;
    $totalPages = 0;
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
                        <a href="dashboard.php">Dashboard</a>
                        <a href="manage_channels.php" class="active">Gestionar Canales</a>
                        <a href="add_channel.php">Agregar Canal</a>
                        <a href="../public/index.php" target="_blank">Ver Sitio</a>
                        <a href="admin_login.php?logout=1" class="logout-btn">Cerrar Sesión</a>
                    </nav>
                </div>
            </div>
        </header>

        <main class="admin-main">
            <div class="container">
                <div class="page-header">
                    <h2>Gestionar Canales</h2>
                    <div class="page-actions">
                        <a href="add_channel.php" class="btn-primary">Agregar Nuevo Canal</a>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" class="filters-form">
                        <div class="filters-row">
                            <div class="filter-group">
                                <input type="text" 
                                       name="search" 
                                       placeholder="Buscar canales..." 
                                       value="<?php echo escape($search); ?>"
                                       class="filter-input">
                            </div>
                            
                            <div class="filter-group">
                                <select name="category" class="filter-select">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo escape($category); ?>" 
                                                <?php echo $categoryFilter === $category ? 'selected' : ''; ?>>
                                            <?php echo escape($category); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <select name="country" class="filter-select">
                                    <option value="">Todos los países</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?php echo escape($country); ?>" 
                                                <?php echo $countryFilter === $country ? 'selected' : ''; ?>>
                                            <?php echo escape($country); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <select name="status" class="filter-select">
                                    <option value="">Todos los estados</option>
                                    <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Activos</option>
                                    <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactivos</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <select name="stream_type" class="filter-select">
                                    <option value="">Todos los tipos</option>
                                    <option value="m3u" <?php echo $streamTypeFilter === 'm3u' ? 'selected' : ''; ?>>M3U</option>
                                    <option value="rtmp" <?php echo $streamTypeFilter === 'rtmp' ? 'selected' : ''; ?>>RTMP</option>
                                    <option value="youtube" <?php echo $streamTypeFilter === 'youtube' ? 'selected' : ''; ?>>YouTube</option>
                                    <option value="twitch" <?php echo $streamTypeFilter === 'twitch' ? 'selected' : ''; ?>>Twitch</option>
                                </select>
                            </div>
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn-primary">Filtrar</button>
                                <a href="manage_channels.php" class="btn-secondary">Limpiar</a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Results Info -->
                <div class="results-info">
                    <p>Mostrando <?php echo count($channels); ?> de <?php echo $totalChannels; ?> canales</p>
                </div>

                <?php if (empty($channels)): ?>
                    <div class="no-channels">
                        <h3>No se encontraron canales</h3>
                        <p>No hay canales que coincidan con los filtros seleccionados.</p>
                        <a href="add_channel.php" class="btn-primary">Agregar Primer Canal</a>
                    </div>
                <?php else: ?>
                    <!-- Bulk Actions -->
                    <form method="POST" id="bulk-form">
                        <div class="bulk-actions">
                            <div class="bulk-select">
                                <input type="checkbox" id="select-all" onchange="toggleAllChannels()">
                                <label for="select-all">Seleccionar todos</label>
                            </div>
                            
                            <div class="bulk-controls">
                                <select name="bulk_action" id="bulk-action">
                                    <option value="">Acciones en lote</option>
                                    <option value="activate">Activar seleccionados</option>
                                    <option value="deactivate">Desactivar seleccionados</option>
                                    <option value="delete">Eliminar seleccionados</option>
                                </select>
                                <button type="button" onclick="executeBulkAction()" class="btn-secondary">Ejecutar</button>
                            </div>
                        </div>

                        <!-- Channels Table -->
                        <div class="channels-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="header-select-all" onchange="toggleAllChannels()">
                                        </th>
                                        <th>Canal</th>
                                        <th>Categoría</th>
                                        <th>País</th>
                                        <th>Tipo</th>
                                        <th>Suscriptores</th>
                                        <th>Estado</th>
                                        <th>Creado</th>
                                        <th width="150">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($channels as $channel): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" 
                                                       name="selected_channels[]" 
                                                       value="<?php echo $channel['id']; ?>"
                                                       class="channel-checkbox">
                                            </td>
                                            <td>
                                                <div class="channel-cell">
                                                    <div class="channel-thumbnail">
                                                        <?php if ($channel['thumbnail_url']): ?>
                                                            <img src="<?php echo escape($channel['thumbnail_url']); ?>" 
                                                                 alt="<?php echo escape($channel['name']); ?>"
                                                                 width="50" height="30">
                                                        <?php else: ?>
                                                            <div class="thumbnail-placeholder-small">
                                                                <?php echo strtoupper(substr($channel['name'], 0, 2)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="channel-details">
                                                        <strong><?php echo escape($channel['name']); ?></strong>
                                                        <?php if ($channel['description']): ?>
                                                            <small><?php echo escape(substr($channel['description'], 0, 50)) . '...'; ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo escape($channel['category']); ?></td>
                                            <td>📍 <?php echo escape($channel['country']); ?></td>
                                            <td>
                                                <span class="stream-type-badge <?php echo $channel['stream_type']; ?>">
                                                    <?php echo strtoupper($channel['stream_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatSubscriberCount($channel['subscriber_count']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $channel['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $channel['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($channel['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="../public<?php echo generateChannelUrl($channel); ?>" 
                                                       target="_blank" 
                                                       class="btn-view" 
                                                       title="Ver canal">👁️</a>
                                                    <a href="edit_channel.php?id=<?php echo $channel['id']; ?>" 
                                                       class="btn-edit" 
                                                       title="Editar">✏️</a>
                                                    <button type="button" 
                                                            onclick="deleteChannel(<?php echo $channel['id']; ?>, '<?php echo addslashes($channel['name']); ?>')" 
                                                            class="btn-delete" 
                                                            title="Eliminar">🗑️</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php
                            $queryParams = $_GET;
                            unset($queryParams['page']);
                            $baseUrl = 'manage_channels.php?' . http_build_query($queryParams);
                            $baseUrl .= $queryParams ? '&' : '?';
                            ?>
                            
                            <?php if ($page > 1): ?>
                                <a href="<?php echo $baseUrl; ?>page=<?php echo $page - 1; ?>" class="pagination-btn">« Anterior</a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <a href="<?php echo $baseUrl; ?>page=<?php echo $i; ?>" 
                                   class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="<?php echo $baseUrl; ?>page=<?php echo $page + 1; ?>" class="pagination-btn">Siguiente »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleAllChannels() {
            const selectAll = document.getElementById('select-all');
            const headerSelectAll = document.getElementById('header-select-all');
            const checkboxes = document.querySelectorAll('.channel-checkbox');
            
            // Sync both select-all checkboxes
            selectAll.checked = headerSelectAll.checked;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        function executeBulkAction() {
            const action = document.getElementById('bulk-action').value;
            const selectedChannels = document.querySelectorAll('.channel-checkbox:checked');
            
            if (!action) {
                alert('Por favor, selecciona una acción.');
                return;
            }
            
            if (selectedChannels.length === 0) {
                alert('Por favor, selecciona al menos un canal.');
                return;
            }
            
            let confirmMessage = '';
            switch (action) {
                case 'activate':
                    confirmMessage = `¿Estás seguro de que quieres activar ${selectedChannels.length} canales?`;
                    break;
                case 'deactivate':
                    confirmMessage = `¿Estás seguro de que quieres desactivar ${selectedChannels.length} canales?`;
                    break;
                case 'delete':
                    confirmMessage = `¿Estás seguro de que quieres eliminar ${selectedChannels.length} canales? Esta acción no se puede deshacer.`;
                    break;
            }
            
            if (confirm(confirmMessage)) {
                document.getElementById('bulk-form').submit();
            }
        }

        function deleteChannel(id, name) {
            if (confirm(`¿Estás seguro de que quieres eliminar el canal "${name}"? Esta acción no se puede deshacer.`)) {
                window.location.href = `delete_channel.php?id=${id}`;
            }
        }

        // Auto-submit form when filters change
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });

        // Search with debounce
        let searchTimeout;
        document.querySelector('input[name="search"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>

    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h2 {
            margin: 0;
            color: #333;
        }

        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filters-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .filter-input,
        .filter-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .filter-actions {
            display: flex;
            gap: 0.5rem;
        }

        .results-info {
            margin-bottom: 1rem;
            color: #666;
        }

        .bulk-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .bulk-select {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .bulk-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .channels-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .channels-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .channels-table th,
        .channels-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .channels-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .channels-table tr:hover {
            background: #f8f9fa;
        }

        .channel-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .channel-thumbnail {
            width: 50px;
            height: 30px;
            border-radius: 4px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .channel-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .thumbnail-placeholder-small {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .channel-details strong {
            display: block;
            margin-bottom: 0.25rem;
        }

        .channel-details small {
            color: #666;
            display: block;
        }

        .stream-type-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
        }

        .stream-type-badge.m3u { background: #3498db; }
        .stream-type-badge.rtmp { background: #e74c3c; }
        .stream-type-badge.youtube { background: #ff0000; }
        .stream-type-badge.twitch { background: #9146ff; }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-view,
        .btn-edit,
        .btn-delete {
            padding: 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
        }

        .btn-view:hover {
            background: #138496;
        }

        .btn-edit {
            background: #ffc107;
            color: #212529;
        }

        .btn-edit:hover {
            background: #e0a800;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }

        .pagination-btn:hover,
        .pagination-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .no-channels {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .no-channels h3 {
            color: #666;
            margin-bottom: 1rem;
        }

        .no-channels p {
            color: #999;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .filters-row {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .bulk-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .channels-table {
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>

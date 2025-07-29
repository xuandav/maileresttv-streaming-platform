<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check admin authentication
requireAdmin();

$pageTitle = 'Editar Canal - Panel de Administración';

$error = '';
$success = '';
$channel = null;

// Get channel ID
$channelId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$channelId) {
    header('Location: manage_channels.php');
    exit;
}

// Load channel data
try {
    $stmt = $pdo->prepare("SELECT * FROM channels WHERE id = ?");
    $stmt->execute([$channelId]);
    $channel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$channel) {
        $error = 'Canal no encontrado.';
    }
} catch (PDOException $e) {
    error_log("Edit channel load error: " . $e->getMessage());
    $error = 'Error al cargar el canal.';
}

// Handle form submission
if ($_POST && $channel) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido. Por favor, inténtalo de nuevo.';
    } else {
        $name = sanitizeInput($_POST['name']);
        $category = sanitizeInput($_POST['category']);
        $country = sanitizeInput($_POST['country']);
        $streamType = sanitizeInput($_POST['stream_type']);
        $streamUrl = sanitizeInput($_POST['stream_url']);
        $thumbnailUrl = sanitizeInput($_POST['thumbnail_url']);
        $description = sanitizeInput($_POST['description']);
        $subscriberCount = (int)$_POST['subscriber_count'];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (empty($name) || empty($category) || empty($country) || empty($streamType) || empty($streamUrl)) {
            $error = 'Todos los campos obligatorios deben ser completados.';
        } elseif (strlen($name) > 255) {
            $error = 'El nombre del canal es demasiado largo (máximo 255 caracteres).';
        } elseif (strlen($description) > 1000) {
            $error = 'La descripción es demasiado larga (máximo 1000 caracteres).';
        } elseif (!in_array($streamType, ['m3u', 'rtmp', 'youtube', 'twitch'])) {
            $error = 'Tipo de stream no válido.';
        } elseif (!validateStreamUrl($streamType, $streamUrl)) {
            $error = 'La URL del stream no es válida para el tipo seleccionado.';
        } elseif ($thumbnailUrl && !filter_var($thumbnailUrl, FILTER_VALIDATE_URL)) {
            $error = 'La URL de la miniatura no es válida.';
        } elseif ($subscriberCount < 0) {
            $error = 'El número de suscriptores no puede ser negativo.';
        } else {
            try {
                // Check if channel name already exists (excluding current channel)
                $checkStmt = $pdo->prepare("SELECT id FROM channels WHERE name = ? AND id != ?");
                $checkStmt->execute([$name, $channelId]);
                
                if ($checkStmt->fetch()) {
                    $error = 'Ya existe otro canal con ese nombre.';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE channels 
                        SET name = ?, category = ?, country = ?, stream_type = ?, stream_url = ?, 
                            thumbnail_url = ?, description = ?, subscriber_count = ?, is_active = ?, 
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $name, $category, $country, $streamType, $streamUrl, 
                        $thumbnailUrl, $description, $subscriberCount, $isActive, $channelId
                    ]);
                    
                    $success = 'Canal actualizado exitosamente.';
                    
                    // Reload channel data
                    $stmt = $pdo->prepare("SELECT * FROM channels WHERE id = ?");
                    $stmt->execute([$channelId]);
                    $channel = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                
            } catch (PDOException $e) {
                error_log("Edit channel update error: " . $e->getMessage());
                $error = 'Error al actualizar el canal. Por favor, inténtalo de nuevo.';
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();
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
                <div class="page-header">
                    <h2>Editar Canal</h2>
                    <?php if ($channel): ?>
                        <p>Editando: <strong><?php echo escape($channel['name']); ?></strong></p>
                    <?php endif; ?>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        <?php echo $success; ?>
                        <div class="success-actions">
                            <a href="../public<?php echo generateChannelUrl($channel); ?>" target="_blank" class="btn-secondary">Ver Canal</a>
                            <a href="manage_channels.php" class="btn-primary">Volver a la Lista</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($channel): ?>
                    <!-- Channel Preview -->
                    <div class="channel-preview">
                        <h3>Vista Previa del Canal</h3>
                        <div class="preview-card">
                            <div class="preview-thumbnail">
                                <?php if ($channel['thumbnail_url']): ?>
                                    <img src="<?php echo escape($channel['thumbnail_url']); ?>" 
                                         alt="<?php echo escape($channel['name']); ?>"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="thumbnail-placeholder" style="display: none;">
                                        <?php echo strtoupper(substr($channel['name'], 0, 2)); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="thumbnail-placeholder">
                                        <?php echo strtoupper(substr($channel['name'], 0, 2)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="stream-type-badge <?php echo $channel['stream_type']; ?>">
                                    <?php echo strtoupper($channel['stream_type']); ?>
                                </div>
                            </div>
                            <div class="preview-info">
                                <h4><?php echo escape($channel['name']); ?></h4>
                                <p><?php echo escape($channel['category']); ?> • <?php echo escape($channel['country']); ?></p>
                                <p class="subscriber-count"><?php echo formatSubscriberCount($channel['subscriber_count']); ?> suscriptores</p>
                                <div class="preview-status">
                                    <span class="status-badge <?php echo $channel['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $channel['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="preview-actions">
                                <a href="../public<?php echo generateChannelUrl($channel); ?>" target="_blank" class="btn-view">
                                    Ver Canal
                                </a>
                            </div>
                        </div>
                    </div>

                    <form method="POST" class="channel-form" id="channel-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="form-section">
                            <h3>Información Básica</h3>
                            
                            <div class="form-group">
                                <label for="name">Nombre del Canal *</label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo escape($channel['name']); ?>" 
                                       required 
                                       maxlength="255"
                                       placeholder="Ej: Canal Noticias España">
                                <small class="form-help">Nombre único e identificativo del canal</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="category">Categoría *</label>
                                    <select id="category" name="category" required>
                                        <option value="">Seleccionar categoría</option>
                                        <option value="Noticias" <?php echo $channel['category'] === 'Noticias' ? 'selected' : ''; ?>>Noticias</option>
                                        <option value="Deportes" <?php echo $channel['category'] === 'Deportes' ? 'selected' : ''; ?>>Deportes</option>
                                        <option value="Entretenimiento" <?php echo $channel['category'] === 'Entretenimiento' ? 'selected' : ''; ?>>Entretenimiento</option>
                                        <option value="Música" <?php echo $channel['category'] === 'Música' ? 'selected' : ''; ?>>Música</option>
                                        <option value="Gaming" <?php echo $channel['category'] === 'Gaming' ? 'selected' : ''; ?>>Gaming</option>
                                        <option value="Educación" <?php echo $channel['category'] === 'Educación' ? 'selected' : ''; ?>>Educación</option>
                                        <option value="Tecnología" <?php echo $channel['category'] === 'Tecnología' ? 'selected' : ''; ?>>Tecnología</option>
                                        <option value="Cultura" <?php echo $channel['category'] === 'Cultura' ? 'selected' : ''; ?>>Cultura</option>
                                        <option value="Infantil" <?php echo $channel['category'] === 'Infantil' ? 'selected' : ''; ?>>Infantil</option>
                                        <option value="Documentales" <?php echo $channel['category'] === 'Documentales' ? 'selected' : ''; ?>>Documentales</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="country">País *</label>
                                    <select id="country" name="country" required>
                                        <option value="">Seleccionar país</option>
                                        <option value="España" <?php echo $channel['country'] === 'España' ? 'selected' : ''; ?>>España</option>
                                        <option value="México" <?php echo $channel['country'] === 'México' ? 'selected' : ''; ?>>México</option>
                                        <option value="Argentina" <?php echo $channel['country'] === 'Argentina' ? 'selected' : ''; ?>>Argentina</option>
                                        <option value="Colombia" <?php echo $channel['country'] === 'Colombia' ? 'selected' : ''; ?>>Colombia</option>
                                        <option value="Chile" <?php echo $channel['country'] === 'Chile' ? 'selected' : ''; ?>>Chile</option>
                                        <option value="Perú" <?php echo $channel['country'] === 'Perú' ? 'selected' : ''; ?>>Perú</option>
                                        <option value="Venezuela" <?php echo $channel['country'] === 'Venezuela' ? 'selected' : ''; ?>>Venezuela</option>
                                        <option value="Ecuador" <?php echo $channel['country'] === 'Ecuador' ? 'selected' : ''; ?>>Ecuador</option>
                                        <option value="Bolivia" <?php echo $channel['country'] === 'Bolivia' ? 'selected' : ''; ?>>Bolivia</option>
                                        <option value="Uruguay" <?php echo $channel['country'] === 'Uruguay' ? 'selected' : ''; ?>>Uruguay</option>
                                        <option value="Paraguay" <?php echo $channel['country'] === 'Paraguay' ? 'selected' : ''; ?>>Paraguay</option>
                                        <option value="Costa Rica" <?php echo $channel['country'] === 'Costa Rica' ? 'selected' : ''; ?>>Costa Rica</option>
                                        <option value="Panamá" <?php echo $channel['country'] === 'Panamá' ? 'selected' : ''; ?>>Panamá</option>
                                        <option value="Guatemala" <?php echo $channel['country'] === 'Guatemala' ? 'selected' : ''; ?>>Guatemala</option>
                                        <option value="Honduras" <?php echo $channel['country'] === 'Honduras' ? 'selected' : ''; ?>>Honduras</option>
                                        <option value="El Salvador" <?php echo $channel['country'] === 'El Salvador' ? 'selected' : ''; ?>>El Salvador</option>
                                        <option value="Nicaragua" <?php echo $channel['country'] === 'Nicaragua' ? 'selected' : ''; ?>>Nicaragua</option>
                                        <option value="República Dominicana" <?php echo $channel['country'] === 'República Dominicana' ? 'selected' : ''; ?>>República Dominicana</option>
                                        <option value="Puerto Rico" <?php echo $channel['country'] === 'Puerto Rico' ? 'selected' : ''; ?>>Puerto Rico</option>
                                        <option value="Cuba" <?php echo $channel['country'] === 'Cuba' ? 'selected' : ''; ?>>Cuba</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" 
                                           name="is_active" 
                                           <?php echo $channel['is_active'] ? 'checked' : ''; ?>>
                                    Canal activo
                                </label>
                                <small class="form-help">Los canales inactivos no aparecerán en el sitio público</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Configuración del Stream</h3>
                            
                            <div class="form-group">
                                <label for="stream_type">Tipo de Stream *</label>
                                <select id="stream_type" name="stream_type" required onchange="updateStreamUrlPlaceholder()">
                                    <option value="">Seleccionar tipo</option>
                                    <option value="m3u" <?php echo $channel['stream_type'] === 'm3u' ? 'selected' : ''; ?>>M3U/HLS (.m3u8)</option>
                                    <option value="rtmp" <?php echo $channel['stream_type'] === 'rtmp' ? 'selected' : ''; ?>>RTMP</option>
                                    <option value="youtube" <?php echo $channel['stream_type'] === 'youtube' ? 'selected' : ''; ?>>YouTube</option>
                                    <option value="twitch" <?php echo $channel['stream_type'] === 'twitch' ? 'selected' : ''; ?>>Twitch</option>
                                </select>
                                <small class="form-help">Tipo de formato del stream</small>
                            </div>

                            <div class="form-group">
                                <label for="stream_url">URL del Stream *</label>
                                <input type="url" 
                                       id="stream_url" 
                                       name="stream_url" 
                                       value="<?php echo escape($channel['stream_url']); ?>" 
                                       required>
                                <small class="form-help" id="stream-url-help">URL del stream según el tipo seleccionado</small>
                            </div>

                            <div class="stream-test-section">
                                <button type="button" onclick="testStream()" class="btn-test" id="test-btn">
                                    Probar Stream
                                </button>
                                <div id="test-result" class="test-result"></div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Información Adicional</h3>
                            
                            <div class="form-group">
                                <label for="thumbnail_url">URL de Miniatura</label>
                                <input type="url" 
                                       id="thumbnail_url" 
                                       name="thumbnail_url" 
                                       value="<?php echo escape($channel['thumbnail_url']); ?>"
                                       placeholder="https://ejemplo.com/imagen.jpg">
                                <small class="form-help">URL de la imagen que se mostrará como miniatura del canal</small>
                            </div>

                            <div class="form-group">
                                <label for="subscriber_count">Número de Suscriptores</label>
                                <input type="number" 
                                       id="subscriber_count" 
                                       name="subscriber_count" 
                                       value="<?php echo $channel['subscriber_count']; ?>" 
                                       min="0"
                                       placeholder="0">
                                <small class="form-help">Número aproximado de suscriptores del canal</small>
                            </div>

                            <div class="form-group">
                                <label for="description">Descripción</label>
                                <textarea id="description" 
                                          name="description" 
                                          rows="4" 
                                          maxlength="1000"
                                          placeholder="Describe el contenido y características del canal..."><?php echo escape($channel['description']); ?></textarea>
                                <small class="form-help">Descripción que aparecerá en la página del canal (máximo 1000 caracteres)</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Información del Sistema</h3>
                            <div class="system-info">
                                <div class="info-item">
                                    <strong>ID del Canal:</strong> <?php echo $channel['id']; ?>
                                </div>
                                <div class="info-item">
                                    <strong>Creado:</strong> <?php echo date('d/m/Y H:i', strtotime($channel['created_at'])); ?>
                                </div>
                                <div class="info-item">
                                    <strong>Última actualización:</strong> <?php echo date('d/m/Y H:i', strtotime($channel['updated_at'])); ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary" id="submit-btn">
                                Actualizar Canal
                            </button>
                            <a href="manage_channels.php" class="btn-secondary">Cancelar</a>
                            <a href="../public<?php echo generateChannelUrl($channel); ?>" target="_blank" class="btn-view">
                                Ver Canal
                            </a>
                            <button type="button" onclick="confirmDelete()" class="btn-delete">
                                Eliminar Canal
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function updateStreamUrlPlaceholder() {
            const streamType = document.getElementById('stream_type').value;
            const streamUrlInput = document.getElementById('stream_url');
            const helpText = document.getElementById('stream-url-help');
            
            const placeholders = {
                'm3u': 'https://ejemplo.com/stream.m3u8',
                'rtmp': 'rtmp://live.ejemplo.com/live/stream_key',
                'youtube': 'https://www.youtube.com/watch?v=VIDEO_ID',
                'twitch': 'https://www.twitch.tv/canal_nombre'
            };
            
            const helpTexts = {
                'm3u': 'URL del archivo M3U8 para streams HLS (HTTP Live Streaming)',
                'rtmp': 'URL RTMP del servidor de streaming con la clave de transmisión',
                'youtube': 'URL completa del video o canal de YouTube',
                'twitch': 'URL del canal de Twitch'
            };
            
            if (placeholders[streamType]) {
                streamUrlInput.placeholder = placeholders[streamType];
                helpText.textContent = helpTexts[streamType];
            }
            
            // Clear test result
            document.getElementById('test-result').innerHTML = '';
        }
        
        function testStream() {
            const streamType = document.getElementById('stream_type').value;
            const streamUrl = document.getElementById('stream_url').value;
            const testResult = document.getElementById('test-result');
            const testBtn = document.getElementById('test-btn');
            
            if (!streamType || !streamUrl) {
                testResult.innerHTML = '<div class="test-error">Por favor selecciona el tipo de stream e ingresa la URL</div>';
                return;
            }
            
            testBtn.textContent = 'Probando...';
            testBtn.disabled = true;
            testResult.innerHTML = '<div class="test-loading">Verificando stream...</div>';
            
            // Basic URL validation
            try {
                const url = new URL(streamUrl);
                let isValid = false;
                let message = '';
                
                switch(streamType) {
                    case 'm3u':
                        isValid = streamUrl.includes('.m3u8') || streamUrl.includes('m3u');
                        message = isValid ? 'URL M3U8 válida' : 'La URL debe contener .m3u8 para streams HLS';
                        break;
                    case 'rtmp':
                        isValid = streamUrl.startsWith('rtmp://');
                        message = isValid ? 'URL RTMP válida' : 'La URL debe comenzar con rtmp://';
                        break;
                    case 'youtube':
                        isValid = streamUrl.includes('youtube.com') || streamUrl.includes('youtu.be');
                        message = isValid ? 'URL de YouTube válida' : 'La URL debe ser de YouTube';
                        break;
                    case 'twitch':
                        isValid = streamUrl.includes('twitch.tv');
                        message = isValid ? 'URL de Twitch válida' : 'La URL debe ser de Twitch';
                        break;
                }
                
                setTimeout(() => {
                    if (isValid) {
                        testResult.innerHTML = `<div class="test-success">✓ ${message}</div>`;
                    } else {
                        testResult.innerHTML = `<div class="test-error">✗ ${message}</div>`;
                    }
                    
                    testBtn.textContent = 'Probar Stream';
                    testBtn.disabled = false;
                }, 1000);
                
            } catch (e) {
                testResult.innerHTML = '<div class="test-error">✗ URL no válida</div>';
                testBtn.textContent = 'Probar Stream';
                testBtn.disabled = false;
            }
        }
        
        function confirmDelete() {
            const channelName = '<?php echo addslashes($channel['name'] ?? ''); ?>';
            if (confirm(`¿Estás seguro de que quieres eliminar el canal "${channelName}"? Esta acción no se puede deshacer.`)) {
                window.location.href = `delete_channel.php?id=<?php echo $channelId; ?>`;
            }
        }
        
        // Form validation
        document.getElementById('channel-form').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.textContent = 'Actualizando Canal...';
            submitBtn.disabled = true;
        });
        
        // Character counter for description
        const descriptionTextarea = document.getElementById('description');
        const maxLength = 1000;
        
        function updateCharacterCount() {
            const remaining = maxLength - descriptionTextarea.value.length;
            let counter = document.getElementById('char-counter');
            
            if (!counter) {
                counter = document.createElement('small');
                counter.id = 'char-counter';
                counter.className = 'char-counter';
                descriptionTextarea.parentNode.appendChild(counter);
            }
            
            counter.textContent = `${remaining} caracteres restantes`;
            counter.style.color = remaining < 100 ? '#e74c3c' : '#666';
        }
        
        descriptionTextarea.addEventListener('input', updateCharacterCount);
        updateCharacterCount();
        
        // Initialize placeholder
        updateStreamUrlPlaceholder();
    </script>

    <style>
        .channel-preview {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .channel-preview h3 {
            margin: 0 0 1rem 0;
            color: #333;
        }

        .preview-card {
            display: flex;
            gap: 1rem;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .preview-thumbnail {
            position: relative;
            width: 120px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .preview-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .thumbnail-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .preview-info {
            flex: 1;
        }

        .preview-info h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }

        .preview-info p {
            margin: 0 0 0.25rem 0;
            color: #666;
            font-size: 0.9rem;
        }

        .preview-status {
            margin-top: 0.5rem;
        }

        .preview-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .system-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-item {
            font-size: 0.9rem;
        }

        .info-item strong {
            color: #333;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-weight: 500;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        @media (max-width: 768px) {
            .preview-card {
                flex-direction: column;
                text-align: center;
            }

            .preview-thumbnail {
                width: 100%;
                max-width: 200px;
            }

            .system-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>

<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check admin authentication
requireAdmin();

$pageTitle = 'Agregar Canal - Panel de Administración';

$error = '';
$success = '';

if ($_POST) {
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
                // Check if channel name already exists
                $checkStmt = $pdo->prepare("SELECT id FROM channels WHERE name = ?");
                $checkStmt->execute([$name]);
                
                if ($checkStmt->fetch()) {
                    $error = 'Ya existe un canal con ese nombre.';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO channels (name, category, country, stream_type, stream_url, thumbnail_url, description, subscriber_count, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    
                    $stmt->execute([
                        $name, $category, $country, $streamType, 
                        $streamUrl, $thumbnailUrl, $description, $subscriberCount
                    ]);
                    
                    $success = 'Canal agregado exitosamente.';
                    
                    // Clear form
                    $_POST = array();
                }
                
            } catch (PDOException $e) {
                error_log("Add channel error: " . $e->getMessage());
                $error = 'Error al agregar el canal. Por favor, inténtalo de nuevo.';
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
                        <a href="add_channel.php" class="active">Agregar Canal</a>
                        <a href="../public/index.php" target="_blank">Ver Sitio</a>
                        <a href="admin_login.php?logout=1" class="logout-btn">Cerrar Sesión</a>
                    </nav>
                </div>
            </div>
        </header>

        <main class="admin-main">
            <div class="container">
                <div class="page-header">
                    <h2>Agregar Nuevo Canal</h2>
                    <p>Completa la información del canal para agregarlo a la plataforma.</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        <?php echo $success; ?>
                        <div class="success-actions">
                            <a href="add_channel.php" class="btn-secondary">Agregar Otro Canal</a>
                            <a href="manage_channels.php" class="btn-primary">Ver Todos los Canales</a>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="channel-form" id="channel-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-section">
                        <h3>Información Básica</h3>
                        
                        <div class="form-group">
                            <label for="name">Nombre del Canal *</label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo escape($_POST['name'] ?? ''); ?>" 
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
                                    <option value="Noticias" <?php echo ($_POST['category'] ?? '') === 'Noticias' ? 'selected' : ''; ?>>Noticias</option>
                                    <option value="Deportes" <?php echo ($_POST['category'] ?? '') === 'Deportes' ? 'selected' : ''; ?>>Deportes</option>
                                    <option value="Entretenimiento" <?php echo ($_POST['category'] ?? '') === 'Entretenimiento' ? 'selected' : ''; ?>>Entretenimiento</option>
                                    <option value="Música" <?php echo ($_POST['category'] ?? '') === 'Música' ? 'selected' : ''; ?>>Música</option>
                                    <option value="Gaming" <?php echo ($_POST['category'] ?? '') === 'Gaming' ? 'selected' : ''; ?>>Gaming</option>
                                    <option value="Educación" <?php echo ($_POST['category'] ?? '') === 'Educación' ? 'selected' : ''; ?>>Educación</option>
                                    <option value="Tecnología" <?php echo ($_POST['category'] ?? '') === 'Tecnología' ? 'selected' : ''; ?>>Tecnología</option>
                                    <option value="Cultura" <?php echo ($_POST['category'] ?? '') === 'Cultura' ? 'selected' : ''; ?>>Cultura</option>
                                    <option value="Infantil" <?php echo ($_POST['category'] ?? '') === 'Infantil' ? 'selected' : ''; ?>>Infantil</option>
                                    <option value="Documentales" <?php echo ($_POST['category'] ?? '') === 'Documentales' ? 'selected' : ''; ?>>Documentales</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="country">País *</label>
                                <select id="country" name="country" required>
                                    <option value="">Seleccionar país</option>
                                    <option value="España" <?php echo ($_POST['country'] ?? '') === 'España' ? 'selected' : ''; ?>>España</option>
                                    <option value="México" <?php echo ($_POST['country'] ?? '') === 'México' ? 'selected' : ''; ?>>México</option>
                                    <option value="Argentina" <?php echo ($_POST['country'] ?? '') === 'Argentina' ? 'selected' : ''; ?>>Argentina</option>
                                    <option value="Colombia" <?php echo ($_POST['country'] ?? '') === 'Colombia' ? 'selected' : ''; ?>>Colombia</option>
                                    <option value="Chile" <?php echo ($_POST['country'] ?? '') === 'Chile' ? 'selected' : ''; ?>>Chile</option>
                                    <option value="Perú" <?php echo ($_POST['country'] ?? '') === 'Perú' ? 'selected' : ''; ?>>Perú</option>
                                    <option value="Venezuela" <?php echo ($_POST['country'] ?? '') === 'Venezuela' ? 'selected' : ''; ?>>Venezuela</option>
                                    <option value="Ecuador" <?php echo ($_POST['country'] ?? '') === 'Ecuador' ? 'selected' : ''; ?>>Ecuador</option>
                                    <option value="Bolivia" <?php echo ($_POST['country'] ?? '') === 'Bolivia' ? 'selected' : ''; ?>>Bolivia</option>
                                    <option value="Uruguay" <?php echo ($_POST['country'] ?? '') === 'Uruguay' ? 'selected' : ''; ?>>Uruguay</option>
                                    <option value="Paraguay" <?php echo ($_POST['country'] ?? '') === 'Paraguay' ? 'selected' : ''; ?>>Paraguay</option>
                                    <option value="Costa Rica" <?php echo ($_POST['country'] ?? '') === 'Costa Rica' ? 'selected' : ''; ?>>Costa Rica</option>
                                    <option value="Panamá" <?php echo ($_POST['country'] ?? '') === 'Panamá' ? 'selected' : ''; ?>>Panamá</option>
                                    <option value="Guatemala" <?php echo ($_POST['country'] ?? '') === 'Guatemala' ? 'selected' : ''; ?>>Guatemala</option>
                                    <option value="Honduras" <?php echo ($_POST['country'] ?? '') === 'Honduras' ? 'selected' : ''; ?>>Honduras</option>
                                    <option value="El Salvador" <?php echo ($_POST['country'] ?? '') === 'El Salvador' ? 'selected' : ''; ?>>El Salvador</option>
                                    <option value="Nicaragua" <?php echo ($_POST['country'] ?? '') === 'Nicaragua' ? 'selected' : ''; ?>>Nicaragua</option>
                                    <option value="República Dominicana" <?php echo ($_POST['country'] ?? '') === 'República Dominicana' ? 'selected' : ''; ?>>República Dominicana</option>
                                    <option value="Puerto Rico" <?php echo ($_POST['country'] ?? '') === 'Puerto Rico' ? 'selected' : ''; ?>>Puerto Rico</option>
                                    <option value="Cuba" <?php echo ($_POST['country'] ?? '') === 'Cuba' ? 'selected' : ''; ?>>Cuba</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Configuración del Stream</h3>
                        
                        <div class="form-group">
                            <label for="stream_type">Tipo de Stream *</label>
                            <select id="stream_type" name="stream_type" required onchange="updateStreamUrlPlaceholder()">
                                <option value="">Seleccionar tipo</option>
                                <option value="m3u" <?php echo ($_POST['stream_type'] ?? '') === 'm3u' ? 'selected' : ''; ?>>M3U/HLS (.m3u8)</option>
                                <option value="rtmp" <?php echo ($_POST['stream_type'] ?? '') === 'rtmp' ? 'selected' : ''; ?>>RTMP</option>
                                <option value="youtube" <?php echo ($_POST['stream_type'] ?? '') === 'youtube' ? 'selected' : ''; ?>>YouTube</option>
                                <option value="twitch" <?php echo ($_POST['stream_type'] ?? '') === 'twitch' ? 'selected' : ''; ?>>Twitch</option>
                            </select>
                            <small class="form-help">Selecciona el formato del stream que vas a agregar</small>
                        </div>

                        <div class="form-group">
                            <label for="stream_url">URL del Stream *</label>
                            <input type="url" 
                                   id="stream_url" 
                                   name="stream_url" 
                                   value="<?php echo escape($_POST['stream_url'] ?? ''); ?>" 
                                   required
                                   placeholder="Selecciona primero el tipo de stream">
                            <small class="form-help" id="stream-url-help">Ingresa la URL del stream según el tipo seleccionado</small>
                        </div>

                        <div class="stream-test-section">
                            <button type="button" onclick="testStream()" class="btn-test" id="test-btn" disabled>
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
                                   value="<?php echo escape($_POST['thumbnail_url'] ?? ''); ?>"
                                   placeholder="https://ejemplo.com/imagen.jpg">
                            <small class="form-help">URL de la imagen que se mostrará como miniatura del canal</small>
                        </div>

                        <div class="form-group">
                            <label for="subscriber_count">Número de Suscriptores</label>
                            <input type="number" 
                                   id="subscriber_count" 
                                   name="subscriber_count" 
                                   value="<?php echo escape($_POST['subscriber_count'] ?? '0'); ?>" 
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
                                      placeholder="Describe el contenido y características del canal..."><?php echo escape($_POST['description'] ?? ''); ?></textarea>
                            <small class="form-help">Descripción que aparecerá en la página del canal (máximo 1000 caracteres)</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary" id="submit-btn">
                            Agregar Canal
                        </button>
                        <a href="manage_channels.php" class="btn-secondary">Cancelar</a>
                        <button type="button" onclick="resetForm()" class="btn-warning">Limpiar Formulario</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function updateStreamUrlPlaceholder() {
            const streamType = document.getElementById('stream_type').value;
            const streamUrlInput = document.getElementById('stream_url');
            const helpText = document.getElementById('stream-url-help');
            const testBtn = document.getElementById('test-btn');
            
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
                testBtn.disabled = false;
            } else {
                streamUrlInput.placeholder = 'Selecciona primero el tipo de stream';
                helpText.textContent = 'Ingresa la URL del stream según el tipo seleccionado';
                testBtn.disabled = true;
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
        
        function resetForm() {
            if (confirm('¿Estás seguro de que quieres limpiar el formulario? Se perderán todos los datos ingresados.')) {
                document.getElementById('channel-form').reset();
                document.getElementById('test-result').innerHTML = '';
                updateStreamUrlPlaceholder();
            }
        }
        
        // Form validation
        document.getElementById('channel-form').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.textContent = 'Agregando Canal...';
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
        
        // Auto-save form data to localStorage
        function saveFormData() {
            const formData = new FormData(document.getElementById('channel-form'));
            const data = {};
            for (let [key, value] of formData.entries()) {
                if (key !== 'csrf_token') {
                    data[key] = value;
                }
            }
            localStorage.setItem('channel_form_data', JSON.stringify(data));
        }
        
        function loadFormData() {
            const savedData = localStorage.getItem('channel_form_data');
            if (savedData) {
                const data = JSON.parse(savedData);
                for (let [key, value] of Object.entries(data)) {
                    const field = document.querySelector(`[name="${key}"]`);
                    if (field) {
                        field.value = value;
                    }
                }
                updateStreamUrlPlaceholder();
            }
        }
        
        // Save form data on input
        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('input', saveFormData);
            field.addEventListener('change', saveFormData);
        });
        
        // Load saved data on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!$success): ?>
                loadFormData();
            <?php else: ?>
                localStorage.removeItem('channel_form_data');
            <?php endif; ?>
        });
        
        // Clear saved data on successful submission
        <?php if ($success): ?>
            localStorage.removeItem('channel_form_data');
        <?php endif; ?>
    </script>

    <style>
        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h2 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .page-header p {
            color: #666;
            margin: 0;
        }

        .channel-form {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .form-section {
            padding: 2rem;
            border-bottom: 1px solid #eee;
        }

        .form-section:last-of-type {
            border-bottom: none;
        }

        .form-section h3 {
            margin: 0 0 1.5rem 0;
            color: #333;
            font-size: 1.2rem;
        }

        .success-actions {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .stream-test-section {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .test-result {
            margin-top: 1rem;
        }

        .test-success {
            color: #27ae60;
            font-weight: 500;
        }

        .test-error {
            color: #e74c3c;
            font-weight: 500;
        }

        .test-loading {
            color: #3498db;
            font-weight: 500;
        }

        .btn-warning {
            background: #f39c12;
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

        .btn-warning:hover {
            background: #e67e22;
        }

        .char-counter {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .form-section {
                padding: 1.5rem;
            }

            .success-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>

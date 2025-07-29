<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$pageTitle = '404 - Página No Encontrada';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .error-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }
        
        .error-content {
            background: white;
            padding: 3rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #e74c3c;
            margin: 0;
            line-height: 1;
        }
        
        .error-title {
            font-size: 2rem;
            color: #333;
            margin: 1rem 0;
            font-weight: 600;
        }
        
        .error-message {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-home {
            background: #3498db;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-home:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-search {
            background: #95a5a6;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-search:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .suggested-channels {
            margin-top: 3rem;
            text-align: left;
        }
        
        .suggested-title {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .channels-mini-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .mini-channel-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }
        
        .mini-channel-card:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .mini-thumbnail {
            width: 40px;
            height: 24px;
            border-radius: 4px;
            overflow: hidden;
            flex-shrink: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .mini-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .mini-channel-info h4 {
            margin: 0;
            font-size: 0.9rem;
            color: #333;
        }
        
        .mini-channel-info p {
            margin: 0;
            font-size: 0.8rem;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .error-content {
                padding: 2rem;
                margin: 1rem;
            }
            
            .error-code {
                font-size: 4rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-actions {
                flex-direction: column;
            }
            
            .channels-mini-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="error-container">
                <div class="error-content">
                    <h1 class="error-code">404</h1>
                    <h2 class="error-title">Canal No Encontrado</h2>
                    <p class="error-message">
                        Lo sentimos, el canal que buscas no existe o ha sido eliminado. 
                        Puede que el enlace esté roto o que el canal ya no esté disponible.
                    </p>
                    
                    <div class="error-actions">
                        <a href="/index.php" class="btn-home">🏠 Ir a Inicio</a>
                        <a href="/index.php#search" class="btn-search">🔍 Buscar Canales</a>
                    </div>
                    
                    <?php
                    // Get some popular channels to suggest
                    try {
                        $stmt = $pdo->query("
                            SELECT c.*, (SELECT COUNT(*) FROM channel_subscriptions cs WHERE cs.channel_id = c.id) AS real_subscriber_count 
                            FROM channels c 
                            WHERE c.is_active = 1 
                            ORDER BY real_subscriber_count DESC 
                            LIMIT 6
                        ");
                        $suggestedChannels = $stmt->fetchAll();
                        
                        if (!empty($suggestedChannels)):
                    ?>
                        <div class="suggested-channels">
                            <h3 class="suggested-title">Canales Populares</h3>
                            <div class="channels-mini-grid">
                                <?php foreach ($suggestedChannels as $channel): ?>
                                    <a href="<?php echo generateChannelUrl($channel); ?>" class="mini-channel-card">
                                        <div class="mini-thumbnail">
                                            <?php if ($channel['thumbnail_url']): ?>
                                                <img src="<?php echo escape($channel['thumbnail_url']); ?>" 
                                                     alt="<?php echo escape($channel['name']); ?>">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($channel['name'], 0, 2)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mini-channel-info">
                                            <h4><?php echo escape($channel['name']); ?></h4>
                                            <p><?php echo escape($channel['category']); ?> • <?php echo escape($channel['country']); ?></p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php 
                        endif;
                    } catch (PDOException $e) {
                        // Silently handle database errors
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php require_once '../includes/footer.php'; ?>
    
    <script>
        // Add smooth scroll to search if hash is present
        if (window.location.hash === '#search') {
            setTimeout(() => {
                const searchBar = document.getElementById('search-bar');
                if (searchBar) {
                    searchBar.classList.remove('hidden');
                    document.getElementById('search-input').focus();
                }
            }, 500);
        }
    </script>
</body>
</html>

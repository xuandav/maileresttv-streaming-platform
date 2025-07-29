<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="description" content="<?php echo SITE_NAME; ?> - Canales de televisión en vivo en español" />
    <meta name="keywords" content="television, streaming, canales, español, en vivo" />
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME . ' - Televisión en Vivo'; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css" />
    <link rel="stylesheet" href="/assets/css/live-carousel.css" />
    <link rel="stylesheet" href="/assets/css/simple-chat.css" />
    <link href="https://vjs.zencdn.net/8.6.1/video-js.css" rel="stylesheet" />
    <link rel="icon" type="image/png" href="/assets/images/favicon.png" />
</head>
<body>
    <header class="site-header new-header">
        <div class="header-left">
            <button class="menu-toggle" aria-label="Abrir menú lateral">☰</button>
            <a href="/index.php" class="logo-link">
                <img src="/assets/images/logo.png" alt="<?php echo SITE_NAME; ?>" class="logo-img" />
                <span class="logo-text"><?php echo SITE_NAME; ?></span>
            </a>
        </div>
        <div class="header-center">
            <input type="search" placeholder="Puede buscar videos, canales y en vivo." class="search-input" />
        </div>
        <div class="header-right">
            <button class="btn-creator-studio">Creator Studio</button>
            <button class="btn-add">+</button>
            <button class="btn-profile" aria-label="Perfil de usuario"></button>
        </div>
        <nav class="submenu">
            <ul>
                <li class="submenu-item active"><a href="#">TOP 100</a></li>
                <li class="submenu-item"><a href="#">drama</a></li>
                <li class="submenu-item"><a href="#">Diversión</a></li>
                <li class="submenu-item"><a href="#">deportivo</a></li>
                <li class="submenu-item"><a href="#">noticia</a></li>
            </ul>
        </nav>
    </header>
</body>
</html>

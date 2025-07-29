<?php
require_once __DIR__ . '/../config/config.php';

try {
    // Create database directory if it doesn't exist
    $dbDir = dirname(DB_PATH);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    
    $dsn = "sqlite:" . DB_PATH;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, null, null, $options);
    
    // Enable foreign key constraints for SQLite
    $pdo->exec("PRAGMA foreign_keys = ON");
    
    // Create tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS channels (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL,
            country VARCHAR(100) NOT NULL,
            stream_type TEXT CHECK(stream_type IN ('m3u', 'rtmp', 'youtube', 'twitch')) NOT NULL,
            stream_url TEXT NOT NULL,
            thumbnail_url VARCHAR(500),
            description TEXT,
            subscriber_count INTEGER DEFAULT 0,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            channel_id INTEGER NOT NULL,
            username VARCHAR(100) NOT NULL DEFAULT 'Anónimo',
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            last_login DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default admin user if not exists
    $adminCheck = $pdo->query("SELECT COUNT(*) as count FROM admin_users WHERE username = 'Juan'");
    if ($adminCheck->fetch()['count'] == 0) {
        $pdo->exec("INSERT INTO admin_users (username, password_hash) VALUES ('Juan', '" . password_hash('admin123456', PASSWORD_DEFAULT) . "')");
    }
    
    // Insert sample data if channels table is empty
    $channelCheck = $pdo->query("SELECT COUNT(*) as count FROM channels");
    if ($channelCheck->fetch()['count'] == 0) {
        $sampleChannels = [
            ['Canal Noticias España', 'Noticias', 'España', 'm3u', 'https://rtvelivestream.akamaized.net/rtvesec/la1/la1_main.m3u8', 'Noticias en directo desde España las 24 horas del día', 125000, 'https://via.placeholder.com/320x180/FF6B6B/FFFFFF?text=Noticias+ES'],
            ['Deportes Live México', 'Deportes', 'México', 'youtube', 'https://www.youtube.com/watch?v=jNQXAC9IVRw', 'Deportes en vivo desde México - Fútbol, béisbol y más', 89000, 'https://via.placeholder.com/320x180/4ECDC4/FFFFFF?text=Deportes+MX'],
            ['Canal Entretenimiento', 'Entretenimiento', 'Argentina', 'twitch', 'https://www.twitch.tv/elrubius', 'Entretenimiento y gaming en español', 45000, 'https://via.placeholder.com/320x180/45B7D1/FFFFFF?text=Gaming+AR'],
            ['Música Latina 24/7', 'Música', 'Colombia', 'm3u', 'https://streaming.radiostreamlive.com/radioacktiva_devices', 'La mejor música latina las 24 horas', 67000, 'https://via.placeholder.com/320x180/F7DC6F/FFFFFF?text=Música+CO'],
            ['Noticias Internacional', 'Noticias', 'Chile', 'rtmp', 'rtmp://live.example.com/live/news_chile', 'Noticias internacionales desde Chile', 34000, 'https://via.placeholder.com/320x180/BB8FCE/FFFFFF?text=Noticias+CL'],
            ['Canal Cultural Perú', 'Educación', 'Perú', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Contenido cultural y educativo peruano', 28000, 'https://via.placeholder.com/320x180/85C1E9/FFFFFF?text=Cultural+PE'],
            ['Deportes España', 'Deportes', 'España', 'm3u', 'https://rtvelivestream.akamaized.net/rtvesec/tdp/tdp_main.m3u8', 'Deportes españoles en directo', 156000, 'https://via.placeholder.com/320x180/58D68D/FFFFFF?text=Deportes+ES'],
            ['Canal Familiar', 'Entretenimiento', 'México', 'youtube', 'https://www.youtube.com/watch?v=M7lc1UVf-VE', 'Entretenimiento para toda la familia', 92000, 'https://via.placeholder.com/320x180/F8C471/FFFFFF?text=Familiar+MX'],
            ['Radio Visual Argentina', 'Música', 'Argentina', 'rtmp', 'rtmp://live.example.com/live/radio_ar', 'Radio con video en vivo desde Buenos Aires', 41000, 'https://via.placeholder.com/320x180/EC7063/FFFFFF?text=Radio+AR'],
            ['Tech News España', 'Tecnología', 'España', 'twitch', 'https://www.twitch.tv/ibai', 'Noticias de tecnología y gaming', 73000, 'https://via.placeholder.com/320x180/AED6F1/FFFFFF?text=Tech+ES']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO channels (name, category, country, stream_type, stream_url, description, subscriber_count, thumbnail_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($sampleChannels as $channel) {
            $stmt->execute($channel);
        }
        
        // Insert sample chat messages
        $sampleMessages = [
            [1, 'Carlos_Madrid', '¡Excelente cobertura de las noticias!'],
            [1, 'Ana_BCN', 'Muy buen canal, siempre actualizado'],
            [1, 'Miguel_ES', 'Gracias por mantenernos informados'],
            [2, 'Futbol_Fan', '¡Qué golazo!'],
            [2, 'Deportista_MX', 'Excelente transmisión del partido'],
            [3, 'Gamer_AR', 'Me encanta este streamer'],
            [3, 'Viewer123', 'Muy entretenido el contenido'],
            [4, 'MusicLover', 'Esta canción está increíble'],
            [4, 'Salsa_Fan', 'La mejor música latina aquí']
        ];
        
        $chatStmt = $pdo->prepare("INSERT INTO chat_messages (channel_id, username, message) VALUES (?, ?, ?)");
        foreach ($sampleMessages as $message) {
            $chatStmt->execute($message);
        }
    }
    
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        die("Error de conexión a la base de datos: " . $e->getMessage());
    } else {
        die("Error de conexión a la base de datos. Por favor, inténtalo más tarde.");
    }
}
?>

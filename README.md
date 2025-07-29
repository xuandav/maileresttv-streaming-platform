# MailerestTV - Plataforma de Streaming en Español

MailerestTV es una plataforma completa de streaming en vivo que soporta múltiples formatos de video (M3U, RTMP, YouTube, Twitch) con chat en tiempo real y panel de administración.

## Características

- ✅ **Múltiples formatos de stream**: M3U/HLS, RTMP, YouTube, Twitch
- ✅ **Chat en tiempo real** con polling AJAX
- ✅ **Panel de administración** completo
- ✅ **Diseño responsive** y moderno
- ✅ **Filtrado por categorías y países**
- ✅ **Búsqueda de canales**
- ✅ **Player personalizado** con logo del sitio
- ✅ **Base de datos MySQL** para gestión de canales
- ✅ **Seguridad** con validación y sanitización

## Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, PDO_MySQL, mbstring

## Instalación

### 1. Clonar/Descargar el proyecto

```bash
# Si tienes git
git clone [URL_DEL_REPOSITORIO] maileresttv
cd maileresttv

# O descargar y extraer el ZIP
```

### 2. Configurar la base de datos

1. Crear una base de datos MySQL:
```sql
CREATE DATABASE maileresttv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importar el esquema:
```bash
mysql -u tu_usuario -p maileresttv < sql/schema.sql
```

### 3. Configurar la aplicación

1. Editar `config/config.php` con tus datos:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASSWORD', 'tu_contraseña');
define('DB_NAME', 'maileresttv');
```

2. Configurar permisos de archivos:
```bash
chmod 755 public/
chmod 644 public/assets/css/styles.css
chmod 644 public/assets/js/*.js
```

### 4. Configurar servidor web

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Seguridad
<Files "config.php">
    Order allow,deny
    Deny from all
</Files>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /path/to/maileresttv/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /config/ {
        deny all;
    }
}
```

## Uso

### Acceso Público
- **URL principal**: `http://tu-dominio.com/`
- **Ver canal**: `http://tu-dominio.com/live.php?channel_id=1`

### Panel de Administración
- **URL admin**: `http://tu-dominio.com/admin/admin_login.php`
- **Usuario**: Juan
- **Contraseña**: admin123456

### Funcionalidades del Admin

1. **Dashboard**: Estadísticas y resumen
2. **Gestionar Canales**: Ver, editar, eliminar canales
3. **Agregar Canal**: Crear nuevos canales
4. **Filtros**: Por categoría, país, tipo de stream
5. **Acciones en lote**: Activar/desactivar/eliminar múltiples canales

## Estructura del Proyecto

```
maileresttv/
├── config/
│   └── config.php              # Configuración general
├── includes/
│   ├── db_connect.php          # Conexión a base de datos
│   ├── functions.php           # Funciones auxiliares
│   ├── header.php              # Header común
│   └── footer.php              # Footer común
├── public/
│   ├── index.php               # Página principal
│   ├── live.php                # Página de canal en vivo
│   ├── post_chat.php           # API para enviar mensajes
│   ├── get_chat.php            # API para obtener mensajes
│   └── assets/
│       ├── css/styles.css      # Estilos CSS
│       ├── js/
│       │   ├── app.js          # JavaScript principal
│       │   ├── chat.js         # Funcionalidad de chat
│       │   └── player.js       # Reproductor de video
│       └── images/
│           └── logo.png        # Logo del sitio
├── admin/
│   ├── admin_login.php         # Login de administrador
│   ├── dashboard.php           # Panel principal
│   ├── manage_channels.php     # Gestión de canales
│   ├── add_channel.php         # Agregar canal
│   ├── edit_channel.php        # Editar canal
│   └── delete_channel.php      # Eliminar canal
├── sql/
│   └── schema.sql              # Esquema de base de datos
└── README.md                   # Este archivo
```

## Tipos de Stream Soportados

### M3U/HLS (.m3u8)
```
Ejemplo: https://ejemplo.com/stream.m3u8
Uso: Streams HTTP Live Streaming
```

### RTMP
```
Ejemplo: rtmp://live.ejemplo.com/live/stream_key
Uso: Real-Time Messaging Protocol
```

### YouTube
```
Ejemplo: https://www.youtube.com/watch?v=VIDEO_ID
Uso: Videos y streams de YouTube
```

### Twitch
```
Ejemplo: https://www.twitch.tv/canal_nombre
Uso: Streams de Twitch
```

## Personalización

### Cambiar el Logo
1. Reemplazar `public/assets/images/logo.png`
2. Actualizar `LOGO_PATH` en `config/config.php`

### Modificar Estilos
- Editar `public/assets/css/styles.css`
- Los colores principales están definidos como variables CSS

### Agregar Categorías/Países
- Editar los arrays en `admin/add_channel.php` y `admin/edit_channel.php`
- Las opciones se cargan dinámicamente desde la base de datos

## Seguridad

### Medidas Implementadas
- ✅ Validación y sanitización de inputs
- ✅ Prepared statements para prevenir SQL injection
- ✅ Protección CSRF en formularios
- ✅ Validación de sesiones de admin
- ✅ Escape de output para prevenir XSS
- ✅ Rate limiting básico en chat
- ✅ Filtro de palabras prohibidas

### Recomendaciones Adicionales
- Usar HTTPS en producción
- Configurar firewall
- Mantener PHP y MySQL actualizados
- Hacer backups regulares de la base de datos
- Monitorear logs de errores

## Troubleshooting

### Error de conexión a base de datos
```
Verificar:
- Credenciales en config/config.php
- Que MySQL esté ejecutándose
- Que la base de datos exista
```

### Chat no funciona
```
Verificar:
- Permisos de archivos PHP
- Logs de errores del servidor
- Consola del navegador para errores JS
```

### Videos no cargan
```
Verificar:
- URLs de stream válidas
- Formato correcto según tipo
- Conectividad a servidores externos
```

### Panel de admin no accesible
```
Verificar:
- Credenciales de login
- Sesiones PHP habilitadas
- Permisos de archivos
```

## Desarrollo

### Agregar nuevos tipos de stream
1. Actualizar enum en `sql/schema.sql`
2. Agregar validación en `includes/functions.php`
3. Implementar reproductor en `public/assets/js/player.js`
4. Actualizar formularios de admin

### Personalizar chat
- Modificar `public/assets/js/chat.js`
- Ajustar polling interval
- Agregar emojis o funciones adicionales

## Licencia

Este proyecto está bajo licencia MIT. Ver archivo LICENSE para más detalles.

## Soporte

Para soporte técnico o reportar bugs:
- Crear un issue en el repositorio
- Contactar al desarrollador

## Changelog

### v1.0.0 (2024)
- Implementación inicial
- Soporte para M3U, RTMP, YouTube, Twitch
- Panel de administración completo
- Chat en tiempo real
- Diseño responsive

---

**MailerestTV** - Tu plataforma de streaming en español 📺

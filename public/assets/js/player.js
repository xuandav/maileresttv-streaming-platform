// Video Player functionality for MailerestTV

// Initialize player based on stream type
function initializePlayer(streamType, streamUrl) {
    console.log(`Initializing ${streamType} player with URL: ${streamUrl}`);
    
    switch(streamType) {
        case 'm3u':
            initializeHLSPlayer(streamUrl);
            break;
        case 'rtmp':
            initializeRTMPPlayer(streamUrl);
            break;
        case 'youtube':
        case 'twitch':
            // These are handled by iframes, just add logo positioning
            positionPlayerLogo();
            handleIframePlayer(streamType);
            break;
        default:
            showStreamError('Tipo de stream no soportado: ' + streamType);
    }
    
    // Initialize common player features
    initializePlayerControls();
    monitorStreamHealth();
}

// Initialize HLS player for M3U streams
function initializeHLSPlayer(streamUrl) {
    const video = document.getElementById('hls-player');
    if (!video) {
        console.error('HLS player element not found');
        return;
    }
    
    if (Hls.isSupported()) {
        const hls = new Hls({
            debug: false,
            enableWorker: true,
            lowLatencyMode: true,
            backBufferLength: 90,
            maxBufferLength: 30,
            maxMaxBufferLength: 600,
            maxBufferSize: 60 * 1000 * 1000,
            maxBufferHole: 0.5,
            highBufferWatchdogPeriod: 2,
            nudgeOffset: 0.1,
            nudgeMaxRetry: 3,
            maxFragLookUpTolerance: 0.25,
            liveSyncDurationCount: 3,
            liveMaxLatencyDurationCount: Infinity,
            liveDurationInfinity: false,
            enableWebVTT: false,
            enableIMSC1: false,
            enableCEA708Captions: false,
            stretchShortVideoTrack: false,
            maxAudioFramesDrift: 1,
            forceKeyFrameOnDiscontinuity: true,
            abrEwmaFastLive: 3.0,
            abrEwmaSlowLive: 9.0,
            abrEwmaFastVoD: 3.0,
            abrEwmaSlowVoD: 9.0,
            abrEwmaDefaultEstimate: 5e5,
            abrBandWidthFactor: 0.95,
            abrBandWidthUpFactor: 0.7,
            abrMaxWithRealBitrate: false,
            maxStarvationDelay: 4,
            maxLoadingDelay: 4,
            minAutoBitrate: 0
        });
        
        hls.loadSource(streamUrl);
        hls.attachMedia(video);
        
        hls.on(Hls.Events.MANIFEST_PARSED, function(event, data) {
            console.log('HLS manifest parsed, found ' + data.levels.length + ' quality levels');
            
            // Try to autoplay
            const playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    console.log('HLS autoplay started');
                    showStreamStatus('Reproduciendo en vivo', 'success');
                }).catch(error => {
                    console.log('HLS autoplay prevented:', error);
                    showPlayButton();
                    showStreamStatus('Haz clic para reproducir', 'info');
                });
            }
        });
        
        hls.on(Hls.Events.LEVEL_SWITCHED, function(event, data) {
            console.log('Quality level switched to: ' + data.level);
            updateQualityIndicator(hls.levels[data.level]);
        });
        
        hls.on(Hls.Events.ERROR, function(event, data) {
            console.error('HLS Error:', data);
            
            if (data.fatal) {
                switch(data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        console.log('Fatal network error encountered, trying to recover');
                        showStreamStatus('Error de red, reintentando...', 'warning');
                        hls.startLoad();
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        console.log('Fatal media error encountered, trying to recover');
                        showStreamStatus('Error de media, reintentando...', 'warning');
                        hls.recoverMediaError();
                        break;
                    default:
                        console.log('Fatal error, cannot recover');
                        showStreamError('Error fatal en el stream. Por favor, recarga la página.');
                        break;
                }
            } else {
                console.warn('Non-fatal HLS error:', data);
            }
        });
        
        // Store HLS instance for later use
        video.hlsInstance = hls;
        
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        // Native HLS support (Safari)
        console.log('Using native HLS support');
        video.src = streamUrl;
        
        video.addEventListener('loadedmetadata', function() {
            const playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    console.log('Native HLS autoplay started');
                    showStreamStatus('Reproduciendo en vivo', 'success');
                }).catch(error => {
                    console.log('Native HLS autoplay prevented:', error);
                    showPlayButton();
                    showStreamStatus('Haz clic para reproducir', 'info');
                });
            }
        });
        
        video.addEventListener('error', function(e) {
            console.error('Native HLS error:', e);
            showStreamError('Error al cargar el stream HLS.');
        });
        
    } else {
        showStreamError('Tu navegador no soporta streams HLS. Por favor, usa un navegador moderno.');
        return;
    }
    
    // Add common video event listeners
    addVideoEventListeners(video);
    positionPlayerLogo();
}

// Initialize RTMP player
function initializeRTMPPlayer(streamUrl) {
    const video = document.getElementById('rtmp-player');
    if (!video) {
        console.error('RTMP player element not found');
        return;
    }
    
    // For RTMP, we'll try to use Video.js with flash fallback
    if (typeof videojs !== 'undefined') {
        const player = videojs('rtmp-player', {
            techOrder: ['flash', 'html5'],
            sources: [{
                type: 'rtmp/mp4',
                src: streamUrl
            }],
            flash: {
                swf: 'assets/libs/video-js.swf'
            },
            autoplay: true,
            muted: true
        });
        
        player.ready(function() {
            console.log('RTMP player ready');
            
            player.play().then(() => {
                console.log('RTMP autoplay started');
                showStreamStatus('Reproduciendo en vivo', 'success');
            }).catch(error => {
                console.log('RTMP autoplay prevented:', error);
                showPlayButton();
                showStreamStatus('Haz clic para reproducir', 'info');
            });
        });
        
        player.on('error', function(error) {
            console.error('RTMP player error:', error);
            showStreamError('Error al cargar el stream RTMP. Verifica que tengas Flash habilitado o usa un stream compatible.');
        });
        
        // Store player instance
        video.videojsInstance = player;
        
    } else {
        // Fallback to basic HTML5 video
        console.warn('Video.js not available, using basic HTML5 video for RTMP');
        video.src = streamUrl;
        
        video.addEventListener('error', function(e) {
            console.error('Basic RTMP error:', e);
            showStreamError('Error al cargar el stream RTMP. Este formato requiere plugins adicionales.');
        });
    }
    
    positionPlayerLogo();
}

// Handle iframe players (YouTube, Twitch)
function handleIframePlayer(streamType) {
    const playerContainer = document.querySelector('.player-container');
    const iframe = playerContainer?.querySelector('iframe');
    
    if (!iframe) {
        console.error('Iframe player not found');
        return;
    }
    
    // Add load event listener
    iframe.addEventListener('load', function() {
        console.log(`${streamType} iframe loaded`);
        showStreamStatus('Cargando...', 'info');
        
        // Hide status after a delay
        setTimeout(() => {
            hideStreamStatus();
        }, 3000);
    });
    
    iframe.addEventListener('error', function() {
        console.error(`${streamType} iframe error`);
        showStreamError(`Error al cargar el contenido de ${streamType.charAt(0).toUpperCase() + streamType.slice(1)}.`);
    });
    
    // Add specific handling for each platform
    if (streamType === 'youtube') {
        handleYouTubePlayer(iframe);
    } else if (streamType === 'twitch') {
        handleTwitchPlayer(iframe);
    }
}

// Handle YouTube player specifics
function handleYouTubePlayer(iframe) {
    // YouTube iframe API integration could go here
    console.log('YouTube player initialized');
    
    // Add message listener for YouTube events
    window.addEventListener('message', function(event) {
        if (event.origin !== 'https://www.youtube.com') return;
        
        try {
            const data = JSON.parse(event.data);
            if (data.event === 'video-progress') {
                // Handle YouTube progress events
                console.log('YouTube progress:', data);
            }
        } catch (e) {
            // Ignore non-JSON messages
        }
    });
}

// Handle Twitch player specifics
function handleTwitchPlayer(iframe) {
    console.log('Twitch player initialized');
    
    // Add message listener for Twitch events
    window.addEventListener('message', function(event) {
        if (event.origin !== 'https://player.twitch.tv') return;
        
        try {
            const data = JSON.parse(event.data);
            console.log('Twitch event:', data);
        } catch (e) {
            // Ignore non-JSON messages
        }
    });
}

// Add common video event listeners
function addVideoEventListeners(video) {
    video.addEventListener('loadstart', () => {
        console.log('Video load started');
        showStreamStatus('Cargando stream...', 'info');
    });
    
    video.addEventListener('loadeddata', () => {
        console.log('Video data loaded');
        showStreamStatus('Stream cargado', 'success');
    });
    
    video.addEventListener('canplay', () => {
        console.log('Video can start playing');
        hideStreamStatus();
    });
    
    video.addEventListener('playing', () => {
        console.log('Video started playing');
        showStreamStatus('Reproduciendo en vivo', 'success');
        setTimeout(hideStreamStatus, 2000);
    });
    
    video.addEventListener('pause', () => {
        console.log('Video paused');
        showStreamStatus('Pausado', 'warning');
    });
    
    video.addEventListener('waiting', () => {
        console.log('Video buffering');
        showStreamStatus('Cargando...', 'info');
    });
    
    video.addEventListener('stalled', () => {
        console.log('Video stalled');
        showStreamStatus('Conexión lenta...', 'warning');
    });
    
    video.addEventListener('error', (e) => {
        console.error('Video error:', e);
        showStreamError('Error al reproducir el video.');
    });
}

// Position player logo
function positionPlayerLogo() {
    const logo = document.querySelector('.player-logo');
    if (!logo) return;
    
    // Position logo in bottom-right corner
    logo.style.position = 'absolute';
    logo.style.bottom = '20px';
    logo.style.right = '20px';
    logo.style.zIndex = '1000';
    logo.style.opacity = '0.8';
    logo.style.transition = 'opacity 0.3s ease';
    logo.style.pointerEvents = 'none';
    
    // Hide logo on hover
    const playerContainer = document.querySelector('.player-container');
    if (playerContainer) {
        playerContainer.addEventListener('mouseenter', () => {
            logo.style.opacity = '0.4';
        });
        
        playerContainer.addEventListener('mouseleave', () => {
            logo.style.opacity = '0.8';
        });
    }
}

// Initialize player controls
function initializePlayerControls() {
    addFullscreenSupport();
    addKeyboardControls();
    addVolumeControls();
}

// Add fullscreen support
function addFullscreenSupport() {
    const playerContainer = document.querySelector('.player-container');
    if (!playerContainer) return;
    
    // Add fullscreen button
    const fullscreenBtn = document.createElement('button');
    fullscreenBtn.className = 'fullscreen-btn';
    fullscreenBtn.innerHTML = '⛶';
    fullscreenBtn.title = 'Pantalla completa';
    
    fullscreenBtn.addEventListener('click', () => {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            playerContainer.requestFullscreen().catch(err => {
                console.error('Error entering fullscreen:', err);
            });
        }
    });
    
    playerContainer.appendChild(fullscreenBtn);
    
    // Handle fullscreen changes
    document.addEventListener('fullscreenchange', () => {
        if (document.fullscreenElement) {
            fullscreenBtn.innerHTML = '⛷';
            fullscreenBtn.title = 'Salir de pantalla completa';
        } else {
            fullscreenBtn.innerHTML = '⛶';
            fullscreenBtn.title = 'Pantalla completa';
        }
    });
}

// Add keyboard controls
function addKeyboardControls() {
    document.addEventListener('keydown', (e) => {
        const video = document.querySelector('video');
        if (!video || document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
            return;
        }
        
        switch(e.key) {
            case ' ':
            case 'k':
                e.preventDefault();
                if (video.paused) {
                    video.play();
                } else {
                    video.pause();
                }
                break;
            case 'f':
                e.preventDefault();
                const playerContainer = document.querySelector('.player-container');
                if (playerContainer) {
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    } else {
                        playerContainer.requestFullscreen();
                    }
                }
                break;
            case 'm':
                e.preventDefault();
                video.muted = !video.muted;
                break;
            case 'ArrowUp':
                e.preventDefault();
                video.volume = Math.min(1, video.volume + 0.1);
                break;
            case 'ArrowDown':
                e.preventDefault();
                video.volume = Math.max(0, video.volume - 0.1);
                break;
        }
    });
}

// Add volume controls
function addVolumeControls() {
    const video = document.querySelector('video');
    if (!video) return;
    
    // Create volume indicator
    const volumeIndicator = document.createElement('div');
    volumeIndicator.className = 'volume-indicator';
    volumeIndicator.style.cssText = `
        position: absolute;
        top: 20px;
        left: 20px;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 0.5rem;
        border-radius: 4px;
        font-size: 0.9rem;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 1001;
        pointer-events: none;
    `;
    
    const playerContainer = document.querySelector('.player-container');
    if (playerContainer) {
        playerContainer.appendChild(volumeIndicator);
    }
    
    // Update volume indicator
    function updateVolumeIndicator() {
        const volume = Math.round(video.volume * 100);
        volumeIndicator.textContent = video.muted ? 'Silenciado' : `Volumen: ${volume}%`;
        volumeIndicator.style.opacity = '1';
        
        setTimeout(() => {
            volumeIndicator.style.opacity = '0';
        }, 1500);
    }
    
    video.addEventListener('volumechange', updateVolumeIndicator);
}

// Show stream error
function showStreamError(message) {
    const playerContainer = document.querySelector('.player-container');
    if (!playerContainer) return;
    
    playerContainer.innerHTML = `
        <div class="stream-error">
            <h3>Error de Reproducción</h3>
            <p>${message}</p>
            <button onclick="location.reload()" class="retry-btn">Recargar Página</button>
        </div>
    `;
}

// Show play button overlay
function showPlayButton() {
    const playerContainer = document.querySelector('.player-container');
    if (!playerContainer || playerContainer.querySelector('.play-button-overlay')) return;
    
    const playButton = document.createElement('div');
    playButton.className = 'play-button-overlay';
    playButton.innerHTML = '<button class="play-btn">▶ Reproducir</button>';
    
    playButton.addEventListener('click', function() {
        const video = playerContainer.querySelector('video');
        const iframe = playerContainer.querySelector('iframe');
        
        if (video) {
            video.play().then(() => {
                playButton.remove();
            }).catch(err => {
                console.error('Play error:', err);
            });
        } else if (iframe) {
            // For iframes, just remove the overlay
            playButton.remove();
        }
    });
    
    playerContainer.appendChild(playButton);
}

// Stream status indicator
function showStreamStatus(message, type = 'info') {
    let statusIndicator = document.querySelector('.stream-status');
    
    if (!statusIndicator) {
        statusIndicator = document.createElement('div');
        statusIndicator.className = 'stream-status';
        statusIndicator.style.cssText = `
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 1rem 2rem;
            border-radius: 6px;
            font-size: 1rem;
            z-index: 1002;
            pointer-events: none;
            transition: opacity 0.3s ease;
        `;
        
        const playerContainer = document.querySelector('.player-container');
        if (playerContainer) {
            playerContainer.appendChild(statusIndicator);
        }
    }
    
    statusIndicator.textContent = message;
    statusIndicator.style.opacity = '1';
    
    // Set color based on type
    switch(type) {
        case 'success':
            statusIndicator.style.background = 'rgba(39, 174, 96, 0.9)';
            break;
        case 'warning':
            statusIndicator.style.background = 'rgba(243, 156, 18, 0.9)';
            break;
        case 'error':
            statusIndicator.style.background = 'rgba(231, 76, 60, 0.9)';
            break;
        default:
            statusIndicator.style.background = 'rgba(52, 152, 219, 0.9)';
    }
}

// Hide stream status
function hideStreamStatus() {
    const statusIndicator = document.querySelector('.stream-status');
    if (statusIndicator) {
        statusIndicator.style.opacity = '0';
    }
}

// Update quality indicator
function updateQualityIndicator(level) {
    let qualityIndicator = document.querySelector('.quality-indicator');
    
    if (!qualityIndicator) {
        qualityIndicator = document.createElement('div');
        qualityIndicator.className = 'quality-indicator';
        qualityIndicator.style.cssText = `
            position: absolute;
            top: 20px;
            right: 80px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            z-index: 1001;
            pointer-events: none;
        `;
        
        const playerContainer = document.querySelector('.player-container');
        if (playerContainer) {
            playerContainer.appendChild(qualityIndicator);
        }
    }
    
    if (level) {
        qualityIndicator.textContent = `${level.height}p`;
    }
}

// Monitor stream health
function monitorStreamHealth() {
    const video = document.querySelector('video');
    if (!video) return;
    
    let lastCurrentTime = 0;
    let stallCount = 0;
    
    const healthCheck = setInterval(() => {
        if (video.paused || video.ended) return;
        
        const currentTime = video.currentTime;
        
        if (currentTime === lastCurrentTime) {
            stallCount++;
            console.warn(`Stream may be stalled (${stallCount})`);
            
            if (stallCount >= 3) {
                showStreamStatus('Stream interrumpido, reintentando...', 'warning');
                
                // Try to recover
                if (video.hlsInstance) {
                    video.hlsInstance.startLoad();
                } else {
                    video.load();
                }
                
                stallCount = 0;
            }
        } else {
            stallCount = 0;
            lastCurrentTime = currentTime;
        }
    }, 5000);
    
    // Clean up interval when video ends or page unloads
    video.addEventListener('ended', () => clearInterval(healthCheck));
    window.addEventListener('beforeunload', () => clearInterval(healthCheck));
}

// Add player control styles
const playerStyle = document.createElement('style');
playerStyle.textContent = `
    .fullscreen-btn {
        position: absolute;
        bottom: 20px;
        left: 20px;
        background: rgba(0,0,0,0.7);
        color: white;
        border: none;
        padding: 0.5rem;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1.2rem;
        z-index: 1001;
        transition: background-color 0.3s ease;
    }
    
    .fullscreen-btn:hover {
        background: rgba(0,0,0,0.9);
    }
    
    .player-container:fullscreen .fullscreen-btn {
        bottom: 40px;
        left: 40px;
    }
`;
document.head.appendChild(playerStyle);

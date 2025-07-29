// Sistema de Chat Simple para MailerestTV
let chatTimer;
let currentChannelId;

// Inicializar chat
function initializeChat(channelId) {
    currentChannelId = channelId;
    console.log('Inicializando chat para canal:', channelId);
    
    setupChatForm();
    loadMessages();
    startPolling();
}

// Configurar formulario de chat
function setupChatForm() {
    const form = document.getElementById('chat-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });
}

// Enviar mensaje
function sendMessage() {
    const usernameInput = document.getElementById('username');
    const messageInput = document.getElementById('message');
    const sendBtn = document.querySelector('.send-btn');
    
    if (!usernameInput || !usernameInput.value.trim()) {
        alert('Por favor, ingresa tu nombre');
        usernameInput.focus();
        return;
    }
    
    if (!messageInput || !messageInput.value.trim()) {
        alert('Por favor, escribe un mensaje');
        messageInput.focus();
        return;
    }
    
    const username = usernameInput.value.trim();
    const message = messageInput.value.trim();
    
    // Deshabilitar formulario
    sendBtn.disabled = true;
    sendBtn.textContent = 'Enviando...';
    
    // Enviar al servidor
    const formData = new FormData();
    formData.append('channel_id', currentChannelId);
    formData.append('username', username);
    formData.append('message', message);
    
    fetch('/post_chat.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            loadMessages(); // Recargar mensajes inmediatamente
        } else {
            alert('Error: ' + (data.error || 'No se pudo enviar el mensaje'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    })
    .finally(() => {
        // Re-habilitar formulario
        sendBtn.disabled = false;
        sendBtn.textContent = 'Enviar';
    });
}

// Cargar mensajes
function loadMessages() {
    if (!currentChannelId) return;
    
    fetch(`/get_chat.php?channel_id=${currentChannelId}&last_id=0`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.messages) {
            displayMessages(data.messages);
            updateChatCount(data.messages.length);
        }
    })
    .catch(error => {
        console.error('Error cargando mensajes:', error);
    });
}

// Mostrar mensajes
function displayMessages(messages) {
    const chatContainer = document.getElementById('simple-chat-messages');
    if (!chatContainer) return;
    
    // Limpiar mensajes existentes
    chatContainer.innerHTML = '';
    
    if (messages.length === 0) {
        chatContainer.innerHTML = `
            <div class="chat-welcome">
                <h4>¡Bienvenido al chat!</h4>
                <p>Sé el primero en comentar sobre esta transmisión.</p>
            </div>
        `;
        return;
    }
    
    // Agregar mensajes
        messages.forEach(message => {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'simple-message';
            
            // Calcular tiempo transcurrido
            const timeAgo = getTimeAgo(message.created_at);
            
            let deleteButtonHtml = '';
            // Mostrar botón eliminar si es admin (simulado con variable isAdmin)
            if (window.isAdmin) {
                deleteButtonHtml = `<button class="delete-btn" data-message-id="${message.id}" title="Eliminar mensaje">🗑️</button>`;
            }
            
            messageDiv.innerHTML = `
                <span class="simple-username">${escapeHtml(message.username)}</span>
                <span class="simple-text">${escapeHtml(message.message)}</span>
                <span class="simple-time" data-timestamp="${message.created_at}">${timeAgo}</span>
                ${deleteButtonHtml}
            `;
            
            chatContainer.appendChild(messageDiv);
        });

        // Añadir event listeners para botones eliminar
        if (window.isAdmin) {
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const messageId = this.getAttribute('data-message-id');
                    if (confirm('¿Estás seguro de eliminar este mensaje?')) {
                        deleteChatMessage(messageId);
                    }
                });
            });
        }
    
    // Desplazar hacia abajo
    chatContainer.scrollTop = chatContainer.scrollHeight;
    
    // Actualizar timestamps
    updateTimestamps();
}

// Actualizar contador de chat
function updateChatCount(count) {
    const countElement = document.getElementById('chat-count');
    if (countElement) {
        countElement.textContent = count;
    }
}

// Iniciar polling para nuevos mensajes
function startPolling() {
    // Limpiar cualquier timer existente
    if (chatTimer) {
        clearInterval(chatTimer);
    }
    
    // Hacer polling cada 3 segundos
    chatTimer = setInterval(() => {
        loadMessages();
        updateTimestamps();
    }, 3000);
}

// Detener polling
function stopPolling() {
    if (chatTimer) {
        clearInterval(chatTimer);
        chatTimer = null;
    }
}

// Actualizar timestamps de mensajes existentes
function updateTimestamps() {
    const timeElements = document.querySelectorAll('.simple-time[data-timestamp]');
    timeElements.forEach(timeElement => {
        const timestamp = timeElement.getAttribute('data-timestamp');
        if (timestamp) {
            const timeAgo = getTimeAgo(timestamp);
            timeElement.textContent = timeAgo;
        }
    });
}

// Obtener string de tiempo transcurrido mejorado
function getTimeAgo(timestamp) {
    const now = new Date();
    const messageTime = new Date(timestamp);
    const diffInSeconds = Math.floor((now - messageTime) / 1000);
    
        if (diffInSeconds < 10) {
            return 'ahora mismo';
        } else if (diffInSeconds < 60) {
            return `hace ${diffInSeconds} segundos`;
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return minutes === 1 ? 'hace 1 minuto' : `hace ${minutes} minutos`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return hours === 1 ? 'hace 1 hora' : `hace ${hours} horas`;
        } else if (diffInSeconds < 604800) {
            const days = Math.floor(diffInSeconds / 86400);
            return days === 1 ? 'hace 1 día' : `hace ${days} días`;
        } else {
            // Para mensajes más antiguos, mostrar fecha
            return messageTime.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
}

// Escapar HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Cambio de pestañas (mantener funcionalidad existente)
function switchTab(tabName) {
    // Remover clase activa de todas las pestañas
    document.querySelectorAll('.chat-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Ocultar todo el contenido de pestañas
    document.querySelectorAll('.chat-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Activar pestaña seleccionada
    const selectedTab = document.querySelector(`[data-tab="${tabName}"]`);
    const selectedContent = document.getElementById(`${tabName}-tab`);
    
    if (selectedTab) selectedTab.classList.add('active');
    if (selectedContent) selectedContent.classList.remove('hidden');
}

// Limpiar cuando se descarga la página
window.addEventListener('beforeunload', stopPolling);

// Pausar polling cuando la página está oculta
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopPolling();
    } else if (currentChannelId) {
        startPolling();
    }
});

// Función para eliminar mensaje de chat
function deleteChatMessage(messageId) {
    fetch('delete_chat_message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `message_id=${messageId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Recargar mensajes después de eliminar
            loadMessages();
            showNotification(data.message);
        } else {
            alert(data.error);
        }
    })
    .catch(error => {
        console.error('Error eliminando mensaje:', error);
        alert('Error de conexión');
    });
}

// Actualizar timestamps cada minuto cuando la página está visible
setInterval(() => {
    if (!document.hidden && currentChannelId) {
        updateTimestamps();
    }
}, 60000); // Cada minuto

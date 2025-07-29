// Global App JavaScript for MailerestTV

function toggleSideMenu() {
    const sideMenu = document.querySelector('.side-menu');
    if (sideMenu) {
        sideMenu.classList.toggle('active');
    }
}

// Initialize app when DOM is loaded
function initializeApp() {
    initializeFilters();
    initializeSearch();
    initializeModals();
    initializeChannelCards();
}

// Filter functionality for channels
function initializeFilters() {
    const categoryFilter = document.getElementById('category-filter');
    const countryFilter = document.getElementById('country-filter');
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            filterChannels('category', this.value);
        });
    }
    
    if (countryFilter) {
        countryFilter.addEventListener('change', function() {
            filterChannels('country', this.value);
        });
    }
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                searchChannels();
            } else {
                // Real-time search with debounce
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    searchChannels();
                }, 300);
            }
        });
    }
}

// Toggle search bar visibility
function toggleSearch() {
    const searchBar = document.getElementById('search-bar');
    const searchInput = document.getElementById('search-input');
    
    if (searchBar.classList.contains('hidden')) {
        searchBar.classList.remove('hidden');
        searchInput.focus();
    } else {
        searchBar.classList.add('hidden');
        searchInput.value = '';
        // Reset search results
        showAllChannels();
    }
}

// Filter channels by category or country
function filterChannels(filterType, filterValue) {
    const channelCards = document.querySelectorAll('.channel-card');
    
    channelCards.forEach(card => {
        const cardValue = card.dataset[filterType];
        
        if (!filterValue || cardValue === filterValue) {
            card.style.display = 'block';
            card.style.animation = 'fadeIn 0.3s ease';
        } else {
            card.style.display = 'none';
        }
    });
    
    updateResultsCount();
}

// Search channels by name or description
function searchChannels() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const channelCards = document.querySelectorAll('.channel-card');
    
    if (!searchTerm) {
        showAllChannels();
        return;
    }
    
    channelCards.forEach(card => {
        const channelName = card.querySelector('.channel-name').textContent.toLowerCase();
        const channelDescription = card.querySelector('.channel-description')?.textContent.toLowerCase() || '';
        
        if (channelName.includes(searchTerm) || channelDescription.includes(searchTerm)) {
            card.style.display = 'block';
            card.style.animation = 'fadeIn 0.3s ease';
        } else {
            card.style.display = 'none';
        }
    });
    
    updateResultsCount();
}

// Show all channels
function showAllChannels() {
    const channelCards = document.querySelectorAll('.channel-card');
    channelCards.forEach(card => {
        card.style.display = 'block';
        card.style.animation = 'fadeIn 0.3s ease';
    });
    updateResultsCount();
}

// Update results count
function updateResultsCount() {
    const visibleCards = document.querySelectorAll('.channel-card[style*="block"], .channel-card:not([style*="none"])');
    const totalCards = document.querySelectorAll('.channel-card');
    
    let resultsText = document.getElementById('results-count');
    if (!resultsText) {
        resultsText = document.createElement('p');
        resultsText.id = 'results-count';
        resultsText.className = 'results-count';
        
        const channelsHeader = document.querySelector('.channels-header');
        if (channelsHeader) {
            channelsHeader.appendChild(resultsText);
        }
    }
    
    const visibleCount = visibleCards.length;
    const totalCount = totalCards.length;
    
    if (visibleCount === totalCount) {
        resultsText.textContent = `Mostrando ${totalCount} canales`;
    } else {
        resultsText.textContent = `Mostrando ${visibleCount} de ${totalCount} canales`;
    }
}

// Initialize channel cards with data attributes
function initializeChannelCards() {
    const channelCards = document.querySelectorAll('.channel-card');
    
    channelCards.forEach(card => {
        // Add hover effects
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
        
        // Add click tracking
        card.addEventListener('click', function(e) {
            const channelName = this.querySelector('.channel-name').textContent;
            console.log(`Clicked on channel: ${channelName}`);
            
            // Add loading state
            const thumbnail = this.querySelector('.channel-thumbnail');
            if (thumbnail) {
                thumbnail.style.opacity = '0.7';
                thumbnail.innerHTML += '<div class="loading"></div>';
            }
        });
    });
}

// Modal functionality
function initializeModals() {
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeModal();
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

// Show modal with content
function showModal(type) {
    const modalOverlay = document.getElementById('modal-overlay');
    const modalBody = document.getElementById('modal-body');
    
    let content = '';
    
    switch(type) {
        case 'about':
            content = `
                <h2>Acerca de MailerestTV</h2>
                <p>MailerestTV es tu plataforma de televisión en vivo en español. Ofrecemos una amplia variedad de canales de diferentes países y categorías.</p>
                <h3>Características:</h3>
                <ul>
                    <li>Canales en vivo de múltiples países</li>
                    <li>Soporte para M3U, RTMP, YouTube y Twitch</li>
                    <li>Chat en tiempo real</li>
                    <li>Interfaz moderna y responsive</li>
                </ul>
            `;
            break;
        case 'contact':
            content = `
                <h2>Contacto</h2>
                <p>¿Tienes alguna pregunta o sugerencia? ¡Nos encantaría escucharte!</p>
                <p><strong>Email:</strong> contacto@maileresttv.com</p>
                <p><strong>Teléfono:</strong> +34 900 123 456</p>
                <p><strong>Horario de atención:</strong> Lunes a Viernes, 9:00 - 18:00</p>
            `;
            break;
        case 'privacy':
            content = `
                <h2>Política de Privacidad</h2>
                <p>En MailerestTV respetamos tu privacidad y protegemos tus datos personales.</p>
                <h3>Información que recopilamos:</h3>
                <ul>
                    <li>Información de navegación</li>
                    <li>Preferencias de canales</li>
                    <li>Mensajes de chat (anónimos)</li>
                </ul>
                <h3>Uso de la información:</h3>
                <ul>
                    <li>Mejorar la experiencia del usuario</li>
                    <li>Proporcionar contenido personalizado</li>
                    <li>Mantener la seguridad del sitio</li>
                </ul>
            `;
            break;
        case 'terms':
            content = `
                <h2>Términos de Uso</h2>
                <p>Al usar MailerestTV, aceptas los siguientes términos:</p>
                <h3>Uso permitido:</h3>
                <ul>
                    <li>Ver contenido para uso personal</li>
                    <li>Participar en chats de manera respetuosa</li>
                    <li>Compartir enlaces a canales</li>
                </ul>
                <h3>Uso prohibido:</h3>
                <ul>
                    <li>Redistribuir contenido sin autorización</li>
                    <li>Usar lenguaje ofensivo en chats</li>
                    <li>Intentar hackear o dañar el sitio</li>
                </ul>
            `;
            break;
    }
    
    modalBody.innerHTML = content;
    modalOverlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Close modal
function closeModal() {
    const modalOverlay = document.getElementById('modal-overlay');
    modalOverlay.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Utility functions
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 6px;
        color: white;
        font-weight: 500;
        z-index: 3000;
        animation: slideIn 0.3s ease;
    `;
    
    // Set background color based on type
    switch(type) {
        case 'success':
            notification.style.backgroundColor = '#27ae60';
            break;
        case 'error':
            notification.style.backgroundColor = '#e74c3c';
            break;
        case 'warning':
            notification.style.backgroundColor = '#f39c12';
            break;
        default:
            notification.style.backgroundColor = '#3498db';
    }
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Format numbers for display
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

// Debounce function for performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Check if element is in viewport
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Lazy loading for images
function initializeLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Handle network status
function handleNetworkStatus() {
    window.addEventListener('online', () => {
        showNotification('Conexión restaurada', 'success');
    });
    
    window.addEventListener('offline', () => {
        showNotification('Sin conexión a internet', 'warning');
    });
}

// Initialize everything when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    initializeLazyLoading();
    handleNetworkStatus();
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .results-count {
        color: #666;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .lazy {
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .lazy.loaded {
        opacity: 1;
    }
`;
document.head.appendChild(style);

<footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo SITE_NAME; ?></h3>
                    <p>Tu plataforma de televisión en vivo en español. Disfruta de canales de todo el mundo.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Categorías</h4>
                    <ul>
                        <li><a href="<?php echo generateCategoryUrl('noticias'); ?>">Noticias</a></li>
                        <li><a href="<?php echo generateCategoryUrl('deportes'); ?>">Deportes</a></li>
                        <li><a href="<?php echo generateCategoryUrl('entretenimiento'); ?>">Entretenimiento</a></li>
                        <li><a href="<?php echo generateCategoryUrl('musica'); ?>">Música</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Países</h4>
                    <ul>
                        <li><a href="<?php echo generateCountryUrl('España'); ?>">España</a></li>
                        <li><a href="<?php echo generateCountryUrl('México'); ?>">México</a></li>
                        <li><a href="<?php echo generateCountryUrl('Argentina'); ?>">Argentina</a></li>
                        <li><a href="<?php echo generateCountryUrl('Colombia'); ?>">Colombia</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Información</h4>
                    <ul>
                        <li><a href="#" onclick="showModal('about')">Acerca de</a></li>
                        <li><a href="#" onclick="showModal('contact')">Contacto</a></li>
                        <li><a href="#" onclick="showModal('privacy')">Privacidad</a></li>
                        <li><a href="#" onclick="showModal('terms')">Términos</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
                <p class="footer-note">Los canales y contenidos son propiedad de sus respectivos dueños.</p>
            </div>
        </div>
    </footer>

    <!-- Modals -->
    <div id="modal-overlay" class="modal-overlay hidden" onclick="closeModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <div id="modal-body"></div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://vjs.zencdn.net/8.6.1/video.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/app.js"></script>
    
    <script>
        // Initialize page-specific functionality
        document.addEventListener('DOMContentLoaded', function() {
            initializeApp();
        });
    </script>
</body>
</html>

<?php
// includes/footer.php
?>
    </main>
    
    <!-- Modern Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-brand">
                        <i class="fas fa-hand-holding-heart"></i>
                        <h4>Asmeera Lifeline</h4>
                    </div>
                    <p class="footer-tagline">Connecting Help, Hope and Humanity During Emergencies</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Emergency Numbers</h5>
                    <ul class="footer-links emergency-numbers">
                        <li><i class="fas fa-ambulance"></i> Ambulance: 102</li>
                        <li><i class="fas fa-fire-extinguisher"></i> Fire: 101</li>
                        <li><i class="fas fa-shield-alt"></i> Police: 100</li>
                        <li><i class="fas fa-headset"></i> Helpline: 112</li>
                    </ul>
                </div>
                
                <div class="col-lg-3 mb-4">
                    <h5>Subscribe for Alerts</h5>
                    <form class="subscribe-form" id="subscribeForm">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Your email" required>
                            <button class="btn btn-danger" type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                    <div class="mt-3">
                        <small><i class="fas fa-envelope"></i> meenalpatro@gmail.com/ashishraunksmp@gmail.com</small><br>
                        <small><i class="fas fa-phone"></i> +91 81027-40438</small>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="row">
                    <div class="col-md-6">
                        <p>&copy; <?php echo date('Y'); ?> Asmeera Lifeline. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p>A.S.M.E.E.R.A - Advanced System for Monitoring, Emergency Engagement, Relief & Assistance</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/main.js?v=<?php echo time(); ?>"></script>
    
    <script>
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
        
        // Back to Top
        const backToTop = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if(window.scrollY > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });
        
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        <?php if(isset($_SESSION['user_id'])): ?>
        // Load Notifications
        const notificationToggle = document.getElementById('notificationToggle');
        if (notificationToggle) {
            notificationToggle.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('notificationPanel')?.classList.toggle('open');
            });
        }

        function loadNotifications() {
            fetch('<?php echo SITE_URL; ?>api/get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('notificationCount').innerText = data.unread || 0;
                    if(data.notifications) {
                        let html = '';
                        data.notifications.forEach(notif => {
                            html += `
                                <div class="notification-item ${!notif.is_read ? 'unread' : ''}">
                                    <div class="notification-icon ${notif.type}">
                                        <i class="fas ${getIcon(notif.type)}"></i>
                                    </div>
                                    <div class="notification-content">
                                        <h6>${notif.title}</h6>
                                        <p>${notif.message}</p>
                                        <small>${new Date(notif.created_at).toLocaleString()}</small>
                                    </div>
                                </div>
                            `;
                        });
                        document.getElementById('notificationList').innerHTML = html || '<p class="text-center p-3">No notifications</p>';
                    }
                });
        }
        
        function getIcon(type) {
            const icons = {
                emergency: 'fa-exclamation-triangle',
                assignment: 'fa-tasks',
                update: 'fa-sync-alt',
                alert: 'fa-bell',
                info: 'fa-info-circle'
            };
            return icons[type] || 'fa-bell';
        }
        
        loadNotifications();
        setInterval(loadNotifications, 30000);
        <?php endif; ?>
    </script>
</body>
</html>


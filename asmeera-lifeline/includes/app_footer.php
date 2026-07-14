</div><!-- .app-content -->
        <footer class="app-mini-footer">
            <p>&copy; <?php echo date('Y'); ?> Asmeera Lifeline — A.S.M.E.E.R.A</p>
        </footer>
    </div><!-- .app-main -->
</div><!-- .app-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
AOS.init({ duration: 800, once: true, offset: 40 });

// ===== LIVE CLOCK - 12 HOUR FORMAT (SAME AS DASHBOARD) =====
<?php if (isset($_SESSION['user_id'])): ?>
function updateLiveClock() {
    const clockElement = document.getElementById('clockTime');
    if (clockElement) {
        const now = new Date();
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        clockElement.textContent = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
    }
}
setInterval(updateLiveClock, 1000);
updateLiveClock();
<?php endif; ?>

// ===== TOAST FUNCTION =====
function showToast(message, type = 'success') {
    Swal.fire({
        text: message,
        icon: type,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

// ===== SIDEBAR TOGGLE (Mobile) =====
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('appSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
});
document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
    document.getElementById('appSidebar').classList.remove('open');
    this.classList.remove('active');
});

// ===== VOLUNTEER LOCATION TRACKING =====
<?php if (in_array($_SESSION['user_role'] ?? '', ['volunteer'])): ?>
if ('geolocation' in navigator) {
    navigator.geolocation.getCurrentPosition(function(pos) {
        fetch('<?php echo SITE_URL; ?>api/update_volunteer_location.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ 
                latitude: pos.coords.latitude, 
                longitude: pos.coords.longitude 
            })
        });
    });
}
<?php endif; ?>
</script>
<?php if (!empty($extra_scripts)) echo $extra_scripts; ?>
</body>
</html>
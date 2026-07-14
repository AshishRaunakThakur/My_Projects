// assets/js/main.js - Complete Dynamic Features

// Notification Panel Toggle
document.addEventListener('DOMContentLoaded', function() {
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationPanel = document.getElementById('notificationPanel');
    const closeNotifications = document.getElementById('closeNotifications');
    
    if(notificationToggle) {
        notificationToggle.addEventListener('click', function(e) {
            e.preventDefault();
            notificationPanel.classList.toggle('open');
        });
    }
    
    if(closeNotifications) {
        closeNotifications.addEventListener('click', function() {
            notificationPanel.classList.remove('open');
        });
    }
    
    // Click outside to close
    document.addEventListener('click', function(e) {
        if(notificationPanel && notificationPanel.classList.contains('open')) {
            if(!notificationPanel.contains(e.target) && !notificationToggle?.contains(e.target)) {
                notificationPanel.classList.remove('open');
            }
        }
    });
});

// Show Toast Message
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

// Show Loading
function showLoading() {
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

// Close Loading
function closeLoading() {
    Swal.close();
}

// Confirm Dialog
function confirmDialog(message, title = 'Are you sure?') {
    return Swal.fire({
        title: title,
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, proceed!'
    });
}

// Format Date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// Get Priority Badge
function getPriorityBadge(priority) {
    const badges = {
        critical: '<span class="badge-critical">🔴 CRITICAL</span>',
        high: '<span class="badge-high">🟠 HIGH</span>',
        medium: '<span class="badge-medium">🟡 MEDIUM</span>',
        low: '<span class="badge-low">🟢 LOW</span>'
    };
    return badges[priority] || badges.low;
}

// Get Status Badge
function getStatusBadge(status) {
    const badges = {
        pending: '<span class="badge bg-warning">⏳ Pending</span>',
        assigned: '<span class="badge bg-info">📌 Assigned</span>',
        in_progress: '<span class="badge bg-primary">🔄 In Progress</span>',
        completed: '<span class="badge bg-success">✅ Completed</span>',
        cancelled: '<span class="badge bg-danger">❌ Cancelled</span>'
    };
    return badges[status] || badges.pending;
}

// Validate Form
function validateForm(formId) {
    const form = document.getElementById(formId);
    if(!form) return true;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if(!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Auto-hide Alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });
}, 100);

// Subscribe Form
const subscribeForm = document.getElementById('subscribeForm');
if(subscribeForm) {
    subscribeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const email = this.querySelector('input[type="email"]').value;
        showToast('Subscribed successfully! You will receive emergency alerts.', 'success');
        this.reset();
    });
}

// Add animation to cards on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if(entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.querySelectorAll('.card, .stat-card').forEach(card => {
    if (card.closest('.dashboard-stat-grid') || card.closest('.app-body')) return;
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
});

// Real-time clock update
function updateClock() {
    const clock = document.getElementById('liveClock');
    if(clock) {
        const now = new Date();
        clock.textContent = now.toLocaleTimeString();
    }
}
setInterval(updateClock, 1000);
updateClock();

// Landing page navbar scroll effect
const landingNavbar = document.querySelector('.navbar-landing');
if (landingNavbar) {
    const onScroll = () => {
        if (window.scrollY > 50) {
            landingNavbar.classList.add('scrolled');
        } else {
            landingNavbar.classList.remove('scrolled');
        }
    };
    window.addEventListener('scroll', onScroll);
    onScroll();
}

// Handle offline mode
window.addEventListener('online', () => {
    showToast('Connection restored!', 'success');
});

window.addEventListener('offline', () => {
    showToast('No internet connection. Some features may be limited.', 'warning');
});
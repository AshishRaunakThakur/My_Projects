<?php
require_once 'config/database.php';
require_once 'includes/helpers.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$page_title = 'Dashboard';
$current_page = 'dashboard';
$stats = getDashboardStats($db, $role, $user_id);

require_once 'includes/app_header.php';
?>

<style>
/* ===== DASHBOARD - LIGHT MODE ===== */
.dash-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.dash-stats .stat-box {
    background: #ffffff;
    border: 1px solid #e8ecf4;
    border-radius: 14px;
    padding: 16px 14px;
    text-align: center;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.dash-stats .stat-box:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    border-color: #dc3545;
}
.dash-stats .stat-box .icon {
    font-size: 1.3rem;
    margin-bottom: 6px;
    display: block;
}
.dash-stats .stat-box .icon.red { color: #dc3545; }
.dash-stats .stat-box .icon.orange { color: #fd7e14; }
.dash-stats .stat-box .icon.blue { color: #0d6efd; }
.dash-stats .stat-box .icon.green { color: #28a745; }
.dash-stats .stat-box .icon.purple { color: #6f42c1; }
.dash-stats .stat-box .icon.cyan { color: #0dcaf0; }

.dash-stats .stat-box .value {
    font-size: 1.6rem;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.2;
}
.dash-stats .stat-box .label {
    font-size: 0.7rem;
    color: #8e95a9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
}
.dash-stats .stat-box .trend {
    font-size: 0.65rem;
    font-weight: 600;
    padding: 1px 10px;
    border-radius: 10px;
    display: inline-block;
    margin-top: 4px;
}
.dash-stats .stat-box .trend.up { background: #e8f5e9; color: #28a745; }
.dash-stats .stat-box .trend.down { background: #fce4ec; color: #dc3545; }

/* Cards */
.dash-card {
    background: #ffffff;
    border: 1px solid #e8ecf4;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.dash-card .head {
    padding: 12px 18px;
    background: #f8f9fc;
    border-bottom: 1px solid #e8ecf4;
    color: #1a1a2e;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.dash-card .head i { color: #dc3545; }
.dash-card .body { padding: 14px 18px; color: #1a1a2e; }
.dash-card .body.p-0 { padding: 0; }

/* ===== MAP - SMALLER SIZE ===== */
#emergencyMap {
    height: 250px;
    width: 100%;
    background: #f5f6fa;
    border-radius: 0 0 14px 14px;
}

/* Table */
.table-scroll {
    overflow-x: auto;
    max-height: 320px;
    overflow-y: auto;
}
.table-scroll::-webkit-scrollbar { width: 4px; }
.table-scroll::-webkit-scrollbar-track { background: #f5f6fa; }
.table-scroll::-webkit-scrollbar-thumb { background: #dc3545; border-radius: 4px; }

.dash-card table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
    color: #1a1a2e;
}
.dash-card table th {
    padding: 10px 14px;
    text-align: left;
    font-weight: 600;
    font-size: 0.7rem;
    text-transform: uppercase;
    color: #8e95a9;
    border-bottom: 1px solid #e8ecf4;
    background: #fafbfc;
    position: sticky;
    top: 0;
    z-index: 2;
}
.dash-card table td {
    padding: 9px 14px;
    border-bottom: 1px solid #f0f2f8;
    vertical-align: middle;
}
.dash-card table tr:hover td { background: #f8f9fc; }

/* Badges - Light Mode */
.badge-priority {
    padding: 2px 12px;
    border-radius: 10px;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
}
.badge-priority.critical { background: #fce4ec; color: #c62828; }
.badge-priority.high { background: #fff3e0; color: #e65100; }
.badge-priority.medium { background: #fff8e1; color: #f57f17; }
.badge-priority.low { background: #e8f5e9; color: #2e7d32; }

.badge-status {
    padding: 2px 12px;
    border-radius: 10px;
    font-size: 0.65rem;
    font-weight: 500;
}
.badge-status.pending { background: #fff8e1; color: #f57f17; }
.badge-status.assigned { background: #e3f2fd; color: #0d47a1; }
.badge-status.in_progress { background: #e8eaf6; color: #283593; }
.badge-status.completed { background: #e8f5e9; color: #1b5e20; }
.badge-status.cancelled { background: #fce4ec; color: #c62828; }

.btn-sm-outline {
    padding: 3px 12px;
    border-radius: 8px;
    font-size: 0.7rem;
    border: 1px solid #e0e0e0;
    background: #fafbfc;
    color: #5a607a;
    text-decoration: none;
    transition: all 0.3s;
    display: inline-block;
}
.btn-sm-outline:hover {
    background: #dc3545;
    color: #fff;
    border-color: #dc3545;
}

/* Charts */
.dash-card canvas {
    max-height: 180px;
    width: 100% !important;
    height: auto !important;
}

/* Responsive */
@media (max-width: 768px) {
    .dash-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .dash-stats .stat-box .value { font-size: 1.2rem; }
    #emergencyMap { height: 200px; }
    .dash-card .body { padding: 10px 12px; }
    .dash-card table { font-size: 0.75rem; }
    .dash-card table th,
    .dash-card table td { padding: 6px 10px; }
}

@media (max-width: 480px) {
    .dash-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    .dash-stats .stat-box { padding: 12px 8px; }
    .dash-stats .stat-box .value { font-size: 1rem; }
    .dash-stats .stat-box .icon { font-size: 1rem; }
    #emergencyMap { height: 160px; }
}

.avail-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 4px;
}
.avail-dot.available { background: #28a745; }
.avail-dot.busy { background: #ffc107; }
.avail-dot.offline { background: #9e9e9e; }

.loading-text {
    text-align: center;
    padding: 30px 0;
    color: #b0b6c8;
}
.loading-text i { color: #dc3545; }

/* Leaflet Override */
.leaflet-popup-content-wrapper {
    border-radius: 12px !important;
}
.leaflet-popup-content {
    color: #1a1a2e !important;
}

/* Make content scrollable */
.app-content {
    max-height: calc(100vh - 70px);
    overflow-y: auto;
}
.app-content::-webkit-scrollbar { width: 4px; }
.app-content::-webkit-scrollbar-track { background: #f5f6fa; }
.app-content::-webkit-scrollbar-thumb { background: #dc3545; border-radius: 4px; }
</style>

<!-- ===== STATS ===== -->
<div class="dash-stats" data-aos="fade-up">
    <?php if ($role === 'admin'): ?>
        <div class="stat-box"><span class="icon red"><i class="fas fa-exclamation-triangle"></i></span><div class="value counter" data-target="<?php echo $stats['total_requests'] ?? 0; ?>">0</div><div class="label">Total Requests</div><span class="trend up">↑ 12%</span></div>
        <div class="stat-box"><span class="icon orange"><i class="fas fa-spinner"></i></span><div class="value counter" data-target="<?php echo $stats['active_requests'] ?? 0; ?>">0</div><div class="label">Active</div><span class="trend up">↑ 5%</span></div>
        <div class="stat-box"><span class="icon blue"><i class="fas fa-hands-helping"></i></span><div class="value counter" data-target="<?php echo $stats['total_volunteers'] ?? 0; ?>">0</div><div class="label">Volunteers</div><span class="trend up">↑ 8%</span></div>
        <div class="stat-box"><span class="icon green"><i class="fas fa-building"></i></span><div class="value counter" data-target="<?php echo $stats['total_ngos'] ?? 0; ?>">0</div><div class="label">NGOs</div><span class="trend up">↑ 3%</span></div>
        <div class="stat-box"><span class="icon purple"><i class="fas fa-search"></i></span><div class="value counter" data-target="<?php echo $stats['missing_persons'] ?? 0; ?>">0</div><div class="label">Missing</div><span class="trend down">↓ 2%</span></div>
        <div class="stat-box"><span class="icon cyan"><i class="fas fa-heart"></i></span><div class="value counter" data-target="<?php echo $stats['safe_checkins'] ?? 0; ?>">0</div><div class="label">Check-ins 24h</div><span class="trend up">↑ 15%</span></div>
    <?php elseif ($role === 'citizen'): ?>
        <div class="stat-box"><span class="icon red"><i class="fas fa-file-alt"></i></span><div class="value counter" data-target="<?php echo $stats['my_requests'] ?? 0; ?>">0</div><div class="label">My Requests</div></div>
        <div class="stat-box"><span class="icon orange"><i class="fas fa-clock"></i></span><div class="value counter" data-target="<?php echo $stats['pending'] ?? 0; ?>">0</div><div class="label">Pending</div></div>
        <div class="stat-box"><span class="icon green"><i class="fas fa-check"></i></span><div class="value counter" data-target="<?php echo $stats['completed'] ?? 0; ?>">0</div><div class="label">Completed</div></div>
        <div class="stat-box"><span class="icon blue"><i class="fas fa-shield-alt"></i></span><div class="value counter" data-target="<?php echo $stats['checkins'] ?? 0; ?>">0</div><div class="label">Safe Check-ins</div></div>
    <?php elseif ($role === 'volunteer'): ?>
        <div class="stat-box"><span class="icon red"><i class="fas fa-tasks"></i></span><div class="value counter" data-target="<?php echo $stats['active_tasks'] ?? 0; ?>">0</div><div class="label">Active Tasks</div></div>
        <div class="stat-box"><span class="icon green"><i class="fas fa-check-double"></i></span><div class="value counter" data-target="<?php echo $stats['completed_tasks'] ?? 0; ?>">0</div><div class="label">Completed</div></div>
        <div class="stat-box"><span class="icon blue"><i class="fas fa-bullhorn"></i></span><div class="value counter" data-target="<?php echo $stats['available_nearby'] ?? 0; ?>">0</div><div class="label">Open Urgent</div></div>
        <div class="stat-box">
            <span class="icon <?php echo ($stats['availability'] ?? 'offline') === 'available' ? 'green' : (($stats['availability'] ?? 'offline') === 'busy' ? 'orange' : ''); ?>">
                <i class="fas fa-circle"></i>
            </span>
            <div class="value" style="font-size:1rem;">
                <span class="avail-dot <?php echo $stats['availability'] ?? 'offline'; ?>"></span>
                <?php echo ucfirst($stats['availability'] ?? 'offline'); ?>
            </div>
            <div class="label">Status</div>
        </div>
    <?php elseif ($role === 'ngo'): ?>
        <div class="stat-box"><span class="icon red"><i class="fas fa-boxes"></i></span><div class="value counter" data-target="<?php echo $stats['total_resources'] ?? 0; ?>">0</div><div class="label">Total Resources</div></div>
        <div class="stat-box"><span class="icon green"><i class="fas fa-campground"></i></span><div class="value counter" data-target="<?php echo $stats['active_camps'] ?? 0; ?>">0</div><div class="label">Active Camps</div></div>
        <div class="stat-box"><span class="icon <?php echo ($stats['verified'] ?? 'Pending') === 'Yes' ? 'green' : 'orange'; ?>"><i class="fas fa-certificate"></i></span><div class="value" style="font-size:1rem;"><?php echo $stats['verified'] ?? 'Pending'; ?></div><div class="label">Verification</div></div>
    <?php endif; ?>
</div>

<!-- ===== MAP - SMALLER SIZE ===== -->
<div class="dash-card" data-aos="fade-up" data-aos-delay="100">
    <div class="head"><i class="fas fa-map-marked-alt"></i> Live Emergency Map</div>
    <div class="body p-0">
        <div id="emergencyMap"></div>
    </div>
</div>

<!-- ===== REQUESTS ===== -->
<div class="dash-card" data-aos="fade-up" data-aos-delay="150">
    <div class="head"><i class="fas fa-list"></i> Recent Emergency Requests</div>
    <div class="body p-0">
        <div class="table-scroll">
            <table>
                <thead><tr><th>ID</th><th>Title</th><th>Type</th><th>Priority</th><th>Status</th><th>Reporter</th><th>Action</th></tr></thead>
                <tbody id="emergencyTableBody">
                    <tr><td colspan="7" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== CHARTS ===== -->
<div class="row g-3">
    <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
        <div class="dash-card">
            <div class="head" style="background:rgba(13,202,240,0.05);"><i class="fas fa-chart-bar" style="color:#0dcaf0;"></i> Requests by Category</div>
            <div class="body"><canvas id="categoryChart" height="160"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6" data-aos="fade-up" data-aos-delay="250">
        <div class="dash-card">
            <div class="head" style="background:rgba(40,167,69,0.05);"><i class="fas fa-chart-pie" style="color:#28a745;"></i> Priority Distribution</div>
            <div class="body"><canvas id="priorityChart" height="160"></canvas></div>
        </div>
    </div>
</div>

<script>
// ===== COUNTER ANIMATION =====
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.counter');
    const animate = (el) => {
        const target = parseInt(el.dataset.target) || 0;
        const duration = 2000;
        const start = performance.now();
        const update = (now) => {
            const p = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 4);
            el.textContent = Math.floor(eased * target).toLocaleString();
            if (p < 1) requestAnimationFrame(update);
            else el.textContent = target.toLocaleString();
        };
        requestAnimationFrame(update);
    };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) { animate(e.target); observer.unobserve(e.target); } });
    }, { threshold: 0.3 });
    counters.forEach(c => observer.observe(c));
});

// ===== LOAD REQUESTS =====
function loadRequests() {
    const tbody = document.getElementById('emergencyTableBody');
    if (!tbody) return;
    fetch('<?php echo SITE_URL; ?>api/get_emergencies.php')
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="loading-text">No requests found</td></tr>';
                return;
            }
            let html = '';
            data.data.slice(0, 8).forEach(r => {
                const pClass = r.priority === 'critical' ? 'badge-priority critical' : r.priority === 'high' ? 'badge-priority high' : r.priority === 'medium' ? 'badge-priority medium' : 'badge-priority low';
                const sClass = 'badge-status ' + r.status;
                html += `<tr>
                    <td>#${r.id}</td>
                    <td>${escapeHtml(r.title || '—')}</td>
                    <td><span style="background:#f5f6fa;padding:2px 10px;border-radius:6px;font-size:0.7rem;color:#5a607a;">${escapeHtml(r.request_type || '—')}</span></td>
                    <td><span class="${pClass}">${r.priority}</span></td>
                    <td><span class="${sClass}">${r.status}</span></td>
                    <td>${escapeHtml(r.reporter_name || '—')}</td>
                    <td><a href="<?php echo SITE_URL; ?>view_request.php?id=${r.id}" class="btn-sm-outline"><i class="fas fa-eye"></i> View</a></td>
                </tr>`;
            });
            tbody.innerHTML = html;
        })
        .catch(() => tbody.innerHTML = '<tr><td colspan="7" class="loading-text">Failed to load</td></tr>');
}

function escapeHtml(t) { if (!t) return '—'; const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

// ===== CHARTS =====
function initCharts() {
    const c1 = document.getElementById('categoryChart');
    if (c1) {
        new Chart(c1, {
            type: 'bar',
            data: {
                labels: ['Food', 'Water', 'Medical', 'Shelter', 'Rescue', 'Other'],
                datasets: [{
                    label: 'Requests',
                    data: [12, 8, 15, 6, 10, 4],
                    backgroundColor: ['rgba(220,53,69,0.7)', 'rgba(13,202,240,0.7)', 'rgba(40,167,69,0.7)', 'rgba(255,193,7,0.7)', 'rgba(111,66,193,0.7)', 'rgba(108,117,125,0.7)'],
                    borderColor: ['#dc3545', '#0dcaf0', '#28a745', '#ffc107', '#6f42c1', '#6c757d'],
                    borderWidth: 2,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { 
                        display: true,
                        labels: { color: '#1a1a2e', font: { size: 11 } }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: 'rgba(0,0,0,0.06)' }, 
                        ticks: { color: '#8e95a9' } 
                    },
                    x: { 
                        grid: { display: false }, 
                        ticks: { color: '#8e95a9' } 
                    }
                }
            }
        });
    }

    const c2 = document.getElementById('priorityChart');
    if (c2) {
        new Chart(c2, {
            type: 'doughnut',
            data: {
                labels: ['Critical', 'High', 'Medium', 'Low'],
                datasets: [{
                    data: [8, 12, 20, 15],
                    backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#28a745'],
                    borderColor: ['#ffffff', '#ffffff', '#ffffff', '#ffffff'],
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { 
                            color: '#1a1a2e', 
                            padding: 12, 
                            usePointStyle: true, 
                            pointStyle: 'circle',
                            font: { size: 12, weight: '500' }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
}

// ===== MAP =====
function initMap() {
    const el = document.getElementById('emergencyMap');
    if (!el) return;
    const map = L.map('emergencyMap').setView([20.5937, 78.9629], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
        attribution: '© OpenStreetMap' 
    }).addTo(map);
    
    fetch('<?php echo SITE_URL; ?>api/get_emergencies.php')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            data.data.forEach(e => {
                if (!e.latitude || !e.longitude) return;
                const color = e.priority === 'critical' ? '#dc3545' : 
                             e.priority === 'high' ? '#fd7e14' : 
                             e.priority === 'medium' ? '#ffc107' : '#28a745';
                const icon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style="background:${color};width:12px;height:12px;border-radius:50%;border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.2);"></div>`,
                    iconSize: [12, 12]
                });
                L.marker([e.latitude, e.longitude], { icon })
                    .bindPopup(`<b>${e.title}</b><br>${e.request_type} · ${e.priority}<br><small>${e.status}</small>`)
                    .addTo(map);
            });
        });
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', function() {
    loadRequests();
    initCharts();
    setTimeout(initMap, 500);
    setInterval(loadRequests, 30000);
});

// ===== SIDEBAR TOGGLE =====
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('appSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
});
document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
    document.getElementById('appSidebar').classList.remove('open');
    this.classList.remove('active');
});

// ===== CLOCK =====
function updateClock() {
    const el = document.getElementById('liveClock');
    if (el) {
        const now = new Date();
        const time = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        el.textContent = time;
    }
}
setInterval(updateClock, 1000);
updateClock();
</script>

<?php
$extra_scripts = '<script>const SITE_URL = "' . SITE_URL . '";</script>';
require_once 'includes/app_footer.php';
?>
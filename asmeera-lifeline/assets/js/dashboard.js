// assets/js/dashboard.js
let emergencyMap = null;
let mapMarkers = [];
let categoryChart = null;
let priorityChart = null;

function initEmergencyMap() {
    const el = document.getElementById('emergencyMap');
    if (!el) return;
    emergencyMap = L.map('emergencyMap').setView([20.5937, 78.9629], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(emergencyMap);
}

function addEmergencyMarker(emergency) {
    if (!emergencyMap || !emergency.latitude || !emergency.longitude) return;
    const colors = { critical: '#dc3545', high: '#fd7e14', medium: '#ffc107', low: '#28a745' };
    const color = colors[emergency.priority] || '#6c757d';
    const icon = L.divIcon({
        className: 'custom-div-icon',
        html: `<div style="background:${color};width:16px;height:16px;border-radius:50%;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3);"></div>`,
        iconSize: [16, 16]
    });
    const marker = L.marker([emergency.latitude, emergency.longitude], { icon })
        .bindPopup(`<b>${emergency.title}</b><br>${emergency.request_type} · ${emergency.priority}<br><small>${emergency.status}</small>`)
        .addTo(emergencyMap);
    mapMarkers.push(marker);
}

async function loadEmergencyData() {
    try {
        const res = await fetch(SITE_URL + 'api/get_emergencies.php');
        const data = await res.json();
        if (!data.success) return;

        mapMarkers.forEach(m => emergencyMap?.removeLayer(m));
        mapMarkers = [];

        data.data.forEach(e => addEmergencyMarker(e));

        const tbody = document.getElementById('emergencyTableBody');
        if (!tbody) return;

        if (data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No emergency requests found</td></tr>';
            return;
        }

        tbody.innerHTML = data.data.slice(0, 10).map(e => `
            <tr>
                <td>#${e.id}</td>
                <td>${escapeHtml(e.title.substring(0, 35))}</td>
                <td><span class="badge bg-secondary">${e.request_type}</span></td>
                <td><span class="badge-${e.priority}">${e.priority}</span></td>
                <td><span class="badge bg-light text-dark">${e.status}</span></td>
                <td>${escapeHtml(e.reporter_name || '—')}</td>
                <td><a href="${SITE_URL}view_request.php?id=${e.id}" class="btn btn-sm btn-danger">View</a></td>
            </tr>
        `).join('');
    } catch (err) {
        console.error(err);
    }
}

async function loadStatistics() {
    try {
        const res = await fetch(SITE_URL + 'api/get_stats.php');
        const data = await res.json();
        if (!data.success) return;

        const catCtx = document.getElementById('categoryChart');
        const priCtx = document.getElementById('priorityChart');
        if (!catCtx || !priCtx) return;

        if (categoryChart) categoryChart.destroy();
        if (priorityChart) priorityChart.destroy();

        categoryChart = new Chart(catCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.categories.map(c => c.request_type),
                datasets: [{
                    label: 'Requests',
                    data: data.categories.map(c => c.count),
                    backgroundColor: 'rgba(233, 69, 96, 0.75)',
                    borderRadius: 8
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        priorityChart = new Chart(priCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: data.priorities.map(p => p.priority),
                datasets: [{
                    data: data.priorities.map(p => p.count),
                    backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#28a745']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    } catch (err) {
        console.error(err);
    }
}

function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}

function initMapPicker(mapId, latId, lngId, locId, defaultLat, defaultLng, zoom) {
    const el = document.getElementById(mapId);
    if (!el) return null;
    const lat = defaultLat || 20.5937;
    const lng = defaultLng || 78.9629;
    const map = L.map(mapId).setView([lat, lng], zoom || 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    let marker = null;

    if (defaultLat && defaultLng) {
        marker = L.marker([defaultLat, defaultLng]).addTo(map);
    }

    map.on('click', function(e) {
        if (marker) map.removeLayer(marker);
        marker = L.marker(e.latlng).addTo(map);
        const latEl = document.getElementById(latId);
        const lngEl = document.getElementById(lngId);
        if (latEl) latEl.value = e.latlng.lat;
        if (lngEl) lngEl.value = e.latlng.lng;
        if (locId) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}`)
                .then(r => r.json())
                .then(d => {
                    const locEl = document.getElementById(locId);
                    if (locEl && d.display_name) locEl.value = d.display_name;
                });
        }
    });
    return map;
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof SITE_URL === 'undefined') return;
    if (document.getElementById('emergencyMap')) {
        initEmergencyMap();
        loadEmergencyData();
        loadStatistics();
        setInterval(() => { loadEmergencyData(); loadStatistics(); }, 30000);
    }
});

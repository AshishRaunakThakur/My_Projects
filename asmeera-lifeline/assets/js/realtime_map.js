// assets/js/realtime_map.js - Complete Real-time Map
let realtimeMap = null;
let mapMarkers = [];
let refreshInterval = null;
let selectedFilter = 'all';
let userLocationMarker = null;
let watchId = null;

// Initialize real-time map
function initRealtimeMap() {
    const mapContainer = document.getElementById('realtimeMap');
    if (!mapContainer) return;
    
    realtimeMap = L.map('realtimeMap').setView([20.5937, 78.9629], 6);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(realtimeMap);
    
    // Add scale control
    L.control.scale().addTo(realtimeMap);
    
    // Add legend
    addMapLegend();
    
    // Start real-time updates
    startRealTimeUpdates();
    
    // Track user's own location
    trackMyLocation();
}

// Add map legend
function addMapLegend() {
    const legend = L.control({ position: 'bottomright' });
    
    legend.onAdd = function() {
        const div = L.DomUtil.create('div', 'map-legend');
        div.innerHTML = `
            <div class="legend-title">📍 Map Legend</div>
            <div><span class="legend-marker emergency"></span> Emergency</div>
            <div><span class="legend-marker volunteer"></span> Volunteer</div>
            <div><span class="legend-marker ngo"></span> NGO/Camp</div>
            <div><span class="legend-marker citizen"></span> Safe Citizen</div>
            <hr>
            <div><span class="pulse-dot"></span> Live (Active now)</div>
        `;
        return div;
    };
    
    legend.addTo(realtimeMap);
}

// Clear all markers
function clearAllMarkers() {
    mapMarkers.forEach(marker => {
        if (realtimeMap) realtimeMap.removeLayer(marker);
    });
    mapMarkers = [];
}

// Get marker color based on type
function getMarkerColor(type, priority) {
    if (type === 'emergency') {
        return priority === 'critical' ? '#dc3545' :
               priority === 'high' ? '#fd7e14' :
               priority === 'medium' ? '#ffc107' : '#28a745';
    }
    if (type === 'volunteer') return '#28a745';
    if (type === 'ngo') return '#0d6efd';
    if (type === 'citizen') return '#17a2b8';
    return '#6c757d';
}

// Get marker icon
function getMarkerIcon(type, color, isLive = false) {
    const icons = {
        emergency: { icon: '⚠️', size: 32 },
        volunteer: { icon: '🤝', size: 28 },
        ngo: { icon: '🏢', size: 30 },
        citizen: { icon: '✅', size: 26 }
    };
    
    const config = icons[type] || icons.emergency;
    const pulseClass = isLive ? 'pulse-marker' : '';
    
    return L.divIcon({
        className: 'custom-marker',
        html: `<div class="marker-container ${pulseClass}" style="
                    width: ${config.size}px;
                    height: ${config.size}px;
                    background: ${color};
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: ${config.size - 8}px;
                    border: 2px solid white;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                    cursor: pointer;
                ">
                    ${config.icon}
                    ${isLive ? '<span class="live-dot"></span>' : ''}
                </div>`,
        iconSize: [config.size, config.size],
        popupAnchor: [0, -config.size/2]
    });
}

// Fetch and display live locations
function fetchLiveLocations() {
    if (!realtimeMap) return;
    
    const url = `${SITE_URL}api/get_live_locations.php?type=${selectedFilter}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (!data.success) return;
            
            clearAllMarkers();
            
            data.data.forEach(item => {
                if (!item.latitude || !item.longitude) return;
                
                const color = getMarkerColor(item.marker_type, item.priority);
                const isLive = item.is_online || (item.marker_type === 'emergency');
                const icon = getMarkerIcon(item.marker_type, color, isLive);
                
                let popupContent = getPopupContent(item);
                
                const marker = L.marker([item.latitude, item.longitude], { icon })
                    .bindPopup(popupContent)
                    .addTo(realtimeMap);
                
                mapMarkers.push(marker);
            });
            
            // Update stats
            updateStatsCounters(data.data);
            
        })
        .catch(error => {
            console.error('Error fetching locations:', error);
        });
}

// Get popup content based on marker type
function getPopupContent(item) {
    if (item.marker_type === 'emergency') {
        return `
            <div style="min-width:200px;">
                <h6 style="color:#dc3545;margin-bottom:8px;"><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(item.title)}</h6>
                <p><strong>Type:</strong> ${item.request_type}</p>
                <p><strong>Priority:</strong> <span class="badge-${item.priority}">${item.priority}</span></p>
                <p><strong>Status:</strong> ${item.status}</p>
                <p><strong>Reporter:</strong> ${escapeHtml(item.reporter_name)}</p>
                <a href="${SITE_URL}view_request.php?id=${item.id}" class="btn btn-sm btn-danger mt-2">View Details →</a>
            </div>
        `;
    }
    
    if (item.marker_type === 'volunteer') {
        return `
            <div style="min-width:200px;">
                <h6 style="color:#28a745;margin-bottom:8px;"><i class="fas fa-hands-helping"></i> ${escapeHtml(item.full_name)}</h6>
                <p><strong>Status:</strong> <span class="badge bg-${item.availability === 'available' ? 'success' : 'warning'}">${item.availability}</span></p>
                <p><strong>Tasks Completed:</strong> ${item.total_tasks_completed || 0}</p>
                ${item.is_online ? '<p class="text-success"><i class="fas fa-circle"></i> Active Now</p>' : ''}
            </div>
        `;
    }
    
    if (item.marker_type === 'ngo') {
        return `
            <div style="min-width:200px;">
                <h6 style="color:#0d6efd;margin-bottom:8px;"><i class="fas fa-building"></i> ${escapeHtml(item.organization_name)}</h6>
                <p><strong>Contact:</strong> ${escapeHtml(item.contact_person || '—')}</p>
                ${item.verified ? '<p class="text-success"><i class="fas fa-check-circle"></i> Verified NGO</p>' : ''}
            </div>
        `;
    }
    
    if (item.marker_type === 'citizen') {
        return `
            <div style="min-width:200px;">
                <h6 style="color:#17a2b8;margin-bottom:8px;"><i class="fas fa-user-check"></i> ${escapeHtml(item.full_name)}</h6>
                <p><strong>Status:</strong> <span class="text-success"><i class="fas fa-shield-alt"></i> Safe</span></p>
                <p><strong>Location:</strong> ${escapeHtml(item.location_name || '—')}</p>
                <p><strong>Checked in:</strong> ${new Date(item.checked_in_at).toLocaleString()}</p>
                ${item.message ? `<p><em>"${escapeHtml(item.message.substring(0, 100))}"</em></p>` : ''}
            </div>
        `;
    }
    
    return '<div>Location</div>';
}

// Update statistics counters
function updateStatsCounters(data) {
    const stats = {
        emergencies: data.filter(d => d.marker_type === 'emergency').length,
        volunteers: data.filter(d => d.marker_type === 'volunteer').length,
        ngos: data.filter(d => d.marker_type === 'ngo').length,
        citizens: data.filter(d => d.marker_type === 'citizen').length
    };
    
    document.getElementById('emergencyCount')?.textContent = stats.emergencies;
    document.getElementById('volunteerCount')?.textContent = stats.volunteers;
    document.getElementById('ngoCount')?.textContent = stats.ngos;
    document.getElementById('citizenCount')?.textContent = stats.citizens;
}

// Start real-time updates
function startRealTimeUpdates() {
    fetchLiveLocations();
    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(fetchLiveLocations, 10000);
}

// Filter map by type
function filterMapByType(type) {
    selectedFilter = type;
    
    // Update active button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.filter === type) {
            btn.classList.add('active');
        }
    });
    
    fetchLiveLocations();
}

// Track user's own location
function trackMyLocation() {
    if (!navigator.geolocation) {
        console.log('Geolocation not supported');
        return;
    }
    
    watchId = navigator.geolocation.watchPosition(
        async (position) => {
            const { latitude, longitude } = position.coords;
            
            // Update user location on map
            if (userLocationMarker) {
                realtimeMap.removeLayer(userLocationMarker);
            }
            
            const userIcon = L.divIcon({
                className: 'user-location-marker',
                html: `<div style="
                            width: 20px;
                            height: 20px;
                            background: #0d6efd;
                            border-radius: 50%;
                            border: 2px solid white;
                            box-shadow: 0 0 0 2px #0d6efd;
                            animation: pulse-blue 1.5s infinite;
                        "></div>`,
                iconSize: [20, 20]
            });
            
            userLocationMarker = L.marker([latitude, longitude], { icon })
                .bindPopup('<strong>📍 You are here</strong>')
                .addTo(realtimeMap);
            
            // Send location to server
            await fetch(`${SITE_URL}api/update_location.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    latitude: latitude,
                    longitude: longitude,
                    location_name: ''
                })
            });
            
        },
        (error) => {
            console.error('Geolocation error:', error);
        },
        {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 0
        }
    );
}

// Stop tracking
function stopRealTimeTracking() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
    }
}

function escapeHtml(text) {
    if (!text) return '—';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add CSS styles
const mapStyles = `
<style>
    @keyframes pulse-marker {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    @keyframes pulse-blue {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.3); opacity: 0.7; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .pulse-marker {
        animation: pulse-marker 1.5s infinite;
    }
    
    .custom-marker {
        background: transparent !important;
        border: none !important;
    }
    
    .live-dot {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 10px;
        height: 10px;
        background: #4ade80;
        border-radius: 50%;
        border: 2px solid white;
        animation: pulse-dot 1s infinite;
    }
    
    .map-legend {
        background: rgba(0,0,0,0.7);
        backdrop-filter: blur(10px);
        padding: 12px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        font-size: 12px;
        min-width: 140px;
        color: white;
    }
    
    .legend-title {
        font-weight: bold;
        margin-bottom: 8px;
        color: white;
    }
    
    .legend-marker {
        display: inline-block;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        margin-right: 8px;
    }
    
    .legend-marker.emergency { background: #dc3545; }
    .legend-marker.volunteer { background: #28a745; }
    .legend-marker.ngo { background: #0d6efd; }
    .legend-marker.citizen { background: #17a2b8; }
    
    .pulse-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        background: #4ade80;
        border-radius: 50%;
        margin-right: 8px;
        animation: pulse-dot 1s infinite;
    }
    
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
    }
</style>
`;

document.head.insertAdjacentHTML('beforeend', mapStyles);

// Export functions
window.initRealtimeMap = initRealtimeMap;
window.filterMapByType = filterMapByType;
window.stopRealTimeTracking = stopRealTimeTracking;

// Initialize map when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map if element exists
    const mapElement = document.getElementById('map');
    if (mapElement) {
        initializeMap();
    }
    
    // Initialize mobile navigation toggle
    initializeNavToggle();
});

//Initialize Leaflet map with university location
function initializeMap() {
    // University coordinates (Athens, Greece - example location)
    const universityLat = 38.04095917045987;
    const universityLng = 23.816376490528945;
    
    // Create map centered on university
    const map = L.map('map').setView([universityLat, universityLng], 15);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Create custom icon for university marker
    const universityIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background-color: #dc2626; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; box-shadow: 0 4px 6px rgba(220, 38, 38, 0.3);">🎓</div>',
        iconSize: [40, 40],
        iconAnchor: [20, 20]
    });
    
    // Add marker for university
    const marker = L.marker([universityLat, universityLng], {
        icon: universityIcon
    }).addTo(map);
    
    // Add popup to marker
    marker.bindPopup(`
        <div style="text-align: center; padding: 10px;">
            <h3 style="margin: 0 0 5px 0; color: #dc2626;">Campus Amarousiou</h3>
            <p style="margin: 0; color: #666666;">Marousi, Greece</p>
            <a href="https://www.openstreetmap.org/?mlat=${universityLat}&mlon=${universityLng}#map=15/${universityLat}/${universityLng}" 
               target="_blank" 
               style="display: inline-block; margin-top: 10px; color: #dc2626; text-decoration: none;">
                View on OpenStreetMap →
            </a>
        </div>
    `).openPopup();
    
    // Add smooth zoom control
    map.on('zoomend', function() {
        const currentZoom = map.getZoom();
        console.log('Current zoom level:', currentZoom);
    });
}

//Initialize mobile navigation toggle
function initializeNavToggle() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            
            // Animate hamburger icon
            const spans = navToggle.querySelectorAll('span');
            if (navMenu.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideNav = navMenu.contains(event.target) || navToggle.contains(event.target);
            if (!isClickInsideNav && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                const spans = navToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
    }
}


function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background-color: ${type === 'success' ? '#dc2626' : type === 'error' ? '#991b1b' : '#dc2626'};
        color: white;
        border-radius: 6px;
        box-shadow: 0 10px 15px -3px rgba(220, 38, 38, 0.2);
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Add CSS animations for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
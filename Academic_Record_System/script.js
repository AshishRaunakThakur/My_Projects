// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initMenuToggle();
    initScrollAnimations();
    initCounterAnimations();
    initSmoothScrolling();
    initLoadingAnimation();
    initInteractiveCards();
    initParallaxEffect();
    initSearchFunctionality();
});

// Menu Toggle Functionality
function initMenuToggle() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    
    menuToggle.addEventListener('click', function() {
        menuToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
    });

    // Close menu when clicking on a link
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!menuToggle.contains(e.target) && !navMenu.contains(e.target)) {
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
        }
    });
}

// Scroll Animations
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, observerOptions);

    // Observe elements for animation
    const animateElements = document.querySelectorAll('.feature-card, .stat-item, .action-card');
    animateElements.forEach(el => {
        el.classList.add('scroll-animate');
        observer.observe(el);
    });
}

// Counter Animation
function initCounterAnimations() {
    const counters = document.querySelectorAll('.stat-number');
    
    const counterObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.getAttribute('data-target'));
                const duration = 2000; // 2 seconds
                const increment = target / (duration / 16); // 60fps
                let current = 0;

                const updateCounter = () => {
                    current += increment;
                    if (current < target) {
                        counter.textContent = Math.floor(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };

                updateCounter();
                counterObserver.unobserve(counter);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => {
        counterObserver.observe(counter);
    });
}

// Smooth Scrolling
function initSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                const offsetTop = targetSection.offsetTop - 80; // Account for fixed navbar
                
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Loading Animation
function initLoadingAnimation() {
    // Create loading screen
    const loading = document.createElement('div');
    loading.className = 'loading';
    loading.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(loading);

    // Remove loading screen after page loads
    window.addEventListener('load', function() {
        setTimeout(() => {
            loading.style.display = 'none';
        }, 2000);
    });
}

// Interactive Cards
function initInteractiveCards() {
    const actionCards = document.querySelectorAll('.action-card');
    
    actionCards.forEach(card => {
        card.addEventListener('click', function() {
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);

            // Show notification
            showNotification('Click On Top Section!', 'info');
        });

        // Add hover sound effect (visual feedback)
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.05)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
}

// Parallax Effect
function initParallaxEffect() {
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('.floating-cards .card');
        
        parallaxElements.forEach((element, index) => {
            const speed = 0.5 + (index * 0.1);
            const yPos = -(scrolled * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    });
}

// Notification System
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'info' ? '#667eea' : '#ffd700'};
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Navbar Background Change on Scroll
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 100) {
        navbar.style.background = 'rgba(102, 126, 234, 0.95)';
        navbar.style.backdropFilter = 'blur(10px)';
    } else {
        navbar.style.background = 'linear-gradient(135deg, #9934be #764ba2 100%)';
        navbar.style.backdropFilter = 'none';
    }
});

// Button Click Effects
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn')) {
        // Create ripple effect
        const ripple = document.createElement('span');
        const rect = e.target.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        `;
        
        e.target.style.position = 'relative';
        e.target.style.overflow = 'hidden';
        e.target.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }
});

// Add ripple animation to CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Typing Effect for Hero Title
function initTypingEffect() {
    const heroTitle = document.querySelector('.hero-title');
    const text = heroTitle.textContent;
    heroTitle.textContent = '';
    
    let i = 0;
    const typeWriter = () => {
        if (i < text.length) {
            heroTitle.textContent += text.charAt(i);
            i++;
            setTimeout(typeWriter, 100);
        }
    };
    
    // Start typing effect after page loads
    setTimeout(typeWriter, 1000);
}

// Initialize typing effect
window.addEventListener('load', initTypingEffect);

// Particle Background Effect
function createParticles() {
    const hero = document.querySelector('.hero');
    const particleCount = 50;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.cssText = `
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            animation: float-particle ${3 + Math.random() * 4}s ease-in-out infinite;
            animation-delay: ${Math.random() * 2}s;
        `;
        hero.appendChild(particle);
    }
}

// Add particle animation to CSS
const particleStyle = document.createElement('style');
particleStyle.textContent = `
    @keyframes float-particle {
        0%, 100% {
            transform: translateY(0px) translateX(0px);
            opacity: 0.5;
        }
        50% {
            transform: translateY(-20px) translateX(10px);
            opacity: 1;
        }
    }
`;
document.head.appendChild(particleStyle);

// Initialize particles
window.addEventListener('load', createParticles);

// Form Validation (for future forms)
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#ff6b6b';
            isValid = false;
        } else {
            input.style.borderColor = '#51cf66';
        }
    });
    
    return isValid;
}

// Export functions for potential future use
window.studentManagementSystem = {
    showNotification,
    validateForm,
    initMenuToggle,
    initScrollAnimations
};

// Search Functionality
function initSearchFunctionality() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    const filterSelect = document.querySelector('.filter-select');
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(performSearch, 300));
    }
    
    if (filterSelect) {
        filterSelect.addEventListener('change', performSearch);
    }
}

function performSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    const filterSelect = document.querySelector('.filter-select');
    
    if (!searchInput || !searchResults) return;
    
    const query = searchInput.value.trim().toLowerCase();
    const filter = filterSelect ? filterSelect.value : '';
    
    if (query === '') {
        searchResults.innerHTML = '<p class="search-placeholder">Enter a search term to find academic records...</p>';
        return;
    }
    
    // Simulate search results (in a real application, this would query a database)
    const mockResults = generateMockSearchResults(query, filter);
    displaySearchResults(mockResults);
}

function displaySearchResults(results) {
    const searchResults = document.getElementById('searchResults');
    
    if (results.length === 0) {
        searchResults.innerHTML = '<p class="no-results">No results found. Try a different search term.</p>';
        return;
    }
    
    const resultsHTML = results.map(item => {
        if (item.type === 'student') {
            return `
                <div class="search-result-item">
                    <div class="result-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="result-content">
                        <h4>${item.name}</h4>
                        <p>Semester: ${Semester} | Course: ${item.course}</p>
                    </div>
                </div>
            `;
        } else {
            return `
                <div class="search-result-item">
                    <div class="result-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="result-content">
                        <h4>${item.name}</h4>
                        <p>Students: ${item.students} | Instructor: ${item.instructor}</p>
                    </div>
                </div>
            `;
        }
    }).join('');
    
    searchResults.innerHTML = resultsHTML;
}

function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    const filterSelect = document.querySelector('.filter-select');
    
    if (searchInput) searchInput.value = '';
    if (filterSelect) filterSelect.value = '';
    if (searchResults) {
        searchResults.innerHTML = '<p class="search-placeholder">Enter a search term to find academic records...</p>';
    }
}

// Debounce function for search input
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

// Make search functions globally available
window.performSearch = performSearch;
window.clearSearch = clearSearch; 
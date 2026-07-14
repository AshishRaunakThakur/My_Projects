<?php
require_once 'config/database.php';

$page_title = 'Home';

$stats = [
    'requests' => 0,
    'volunteers' => 0,
    'ngos' => 0,
    'resources' => 0
];

try {
    $stmt = $db->query("SELECT COUNT(*) as c FROM emergency_requests");
    $stats['requests'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM users WHERE role_id = 2");
    $stats['volunteers'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM users WHERE role_id = 3");
    $stats['ngos'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['c'];

    $stmt = $db->query("SELECT COALESCE(SUM(quantity), 0) as c FROM resources");
    $stats['resources'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['c'];
} catch (Exception $e) {
    // Stats remain at 0 if DB tables not ready
}

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="container position-relative">
        <div class="row align-items-center min-vh-hero">
            <div class="col-lg-7 text-white" data-aos="fade-right">
                <span class="hero-badge"><i class="fas fa-bolt"></i> Emergency Response Platform</span>
                <h1 class="hero-title">Connecting Help, Hope &amp; Humanity</h1>
                <p class="hero-subtitle">During Emergencies — When Every Second Counts</p>
                <p class="hero-desc">A.S.M.E.E.R.A — Advanced System for Monitoring, Emergency Engagement, Relief &amp; Assistance. Real-time coordination between citizens, volunteers, and NGOs.</p>
                <div class="hero-buttons">
                    <a href="<?php echo SITE_URL; ?>register.php" class="btn btn-hero-primary">
                        <i class="fas fa-user"></i> Join as Citizen
                    </a>
                    <a href="<?php echo SITE_URL; ?>register.php?role=volunteer" class="btn btn-hero-outline">
                        <i class="fas fa-hands-helping"></i> Become Volunteer
                    </a>
                </div>
                <div class="hero-trust">
                    <span><i class="fas fa-check-circle"></i> 24/7 Emergency Support</span>
                    <span><i class="fas fa-map-marker-alt"></i> Live Location Tracking</span>
                    <span><i class="fas fa-shield-alt"></i> Secure &amp; Verified</span>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block" data-aos="fade-left" data-aos-delay="200">
                <div class="hero-card-float">
                    <div class="hero-card-inner">
                        <div class="hero-card-header">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Live Emergency Feed</span>
                            <span class="pulse-dot"></span>
                        </div>
                        <div class="hero-card-item">
                            <div class="item-icon medical"><i class="fas fa-heartbeat"></i></div>
                            <div>
                                <strong>Medical Aid Request</strong>
                                <small>Priority: Critical • 2 min ago</small>
                            </div>
                        </div>
                        <div class="hero-card-item">
                            <div class="item-icon food"><i class="fas fa-utensils"></i></div>
                            <div>
                                <strong>Food Relief Needed</strong>
                                <small>Priority: High • 5 min ago</small>
                            </div>
                        </div>
                        <div class="hero-card-item">
                            <div class="item-icon rescue"><i class="fas fa-life-ring"></i></div>
                            <div>
                                <strong>Rescue Operation</strong>
                                <small>Volunteer Assigned • 8 min ago</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-wave">
        <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
            <path d="M0,64L48,69.3C96,75,192,85,288,80C384,75,480,53,576,48C672,43,768,53,864,64C960,75,1056,85,1152,80C1248,75,1344,53,1392,42.7L1440,32L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z"></path>
        </svg>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="0">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="stat-value counter" data-target="<?php echo $stats['requests']; ?>">0</div>
                    <div class="stat-label">Emergency Requests</div>
                </div>
            </div>
            <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card">
                    <div class="stat-icon volunteer"><i class="fas fa-hands-helping"></i></div>
                    <div class="stat-value counter" data-target="<?php echo $stats['volunteers']; ?>">0</div>
                    <div class="stat-label">Active Volunteers</div>
                </div>
            </div>
            <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card">
                    <div class="stat-icon ngo"><i class="fas fa-building"></i></div>
                    <div class="stat-value counter" data-target="<?php echo $stats['ngos']; ?>">0</div>
                    <div class="stat-label">NGO Partners</div>
                </div>
            </div>
            <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card">
                    <div class="stat-icon resource"><i class="fas fa-box-open"></i></div>
                    <div class="stat-value counter" data-target="<?php echo $stats['resources']; ?>">0</div>
                    <div class="stat-label">Relief Resources</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="features-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Platform Features</span>
            <h2>Everything You Need in a Crisis</h2>
            <p>Powerful tools designed for fast, coordinated emergency response</p>
        </div>
        <div class="row g-4">
            <?php
            $features = [
                ['icon' => 'fa-ambulance', 'color' => 'danger', 'title' => 'Emergency Request', 'desc' => 'Quickly request help for food, water, medical aid, shelter, or rescue with priority tagging.'],
                ['icon' => 'fa-map-marked-alt', 'color' => 'primary', 'title' => 'Live Mapping', 'desc' => 'Real-time location tracking of emergencies, volunteers, and relief resources on interactive maps.'],
                ['icon' => 'fa-chart-line', 'color' => 'success', 'title' => 'Analytics Dashboard', 'desc' => 'Real-time statistics, disaster response trends, and resource allocation insights.'],
                ['icon' => 'fa-hands-helping', 'color' => 'warning', 'title' => 'Volunteer Network', 'desc' => 'Connect skilled volunteers with nearby emergencies based on availability and skills.'],
                ['icon' => 'fa-search', 'color' => 'info', 'title' => 'Missing Persons', 'desc' => 'Report and search for missing persons with photo uploads and location details.'],
                ['icon' => 'fa-heart', 'color' => 'danger', 'title' => 'Safe Check-in', 'desc' => 'Let your loved ones know you are safe during disasters with one-click check-ins.'],
            ];
            $delay = 0;
            foreach ($features as $f):
            ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                <div class="feature-card">
                    <div class="feature-icon icon-<?php echo $f['color']; ?>">
                        <i class="fas <?php echo $f['icon']; ?>"></i>
                    </div>
                    <h4><?php echo $f['title']; ?></h4>
                    <p><?php echo $f['desc']; ?></p>
                    <div class="feature-arrow"><i class="fas fa-arrow-right"></i></div>
                </div>
            </div>
            <?php $delay += 100; endforeach; ?>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="how-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Simple Process</span>
            <h2>How Asmeera Lifeline Works</h2>
            <p>Three steps to get help or provide assistance during emergencies</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="0">
                <div class="step-card">
                    <div class="step-number">01</div>
                    <i class="fas fa-user-plus step-icon"></i>
                    <h4>Register &amp; Connect</h4>
                    <p>Sign up as a citizen, volunteer, or NGO. Build your profile and get verified quickly.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="150">
                <div class="step-card">
                    <div class="step-number">02</div>
                    <i class="fas fa-bullhorn step-icon"></i>
                    <h4>Report or Respond</h4>
                    <p>Citizens submit emergency requests. Volunteers and NGOs receive instant alerts to act.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="300">
                <div class="step-card">
                    <div class="step-number">03</div>
                    <i class="fas fa-check-double step-icon"></i>
                    <h4>Track &amp; Resolve</h4>
                    <p>Monitor progress in real-time on the map. Mark tasks complete and save lives together.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="about-section py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <span class="section-tag">About Us</span>
                <h2>Built for Humanity in Crisis</h2>
                <p class="about-text">Asmeera Lifeline is a comprehensive disaster management platform that bridges the gap between those in need and those who can help. Our mission is to save lives through technology-driven coordination.</p>
                <ul class="about-list">
                    <li><i class="fas fa-check"></i> Real-time emergency request management</li>
                    <li><i class="fas fa-check"></i> NGO resource and relief camp coordination</li>
                    <li><i class="fas fa-check"></i> Volunteer task assignment and tracking</li>
                    <li><i class="fas fa-check"></i> Missing person registry and safe check-ins</li>
                    <li><i class="fas fa-check"></i> Priority-based response system</li>
                </ul>
                <a href="<?php echo SITE_URL; ?>register.php" class="btn btn-danger btn-lg mt-3">
                    <i class="fas fa-rocket"></i> Get Started Today
                </a>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="about-visual">
                    <div class="about-card about-card-1">
                        <i class="fas fa-users"></i>
                        <div>
                            <strong>Multi-Role Platform</strong>
                            <span>Citizens • Volunteers • NGOs • Admin</span>
                        </div>
                    </div>
                    <div class="about-card about-card-2">
                        <i class="fas fa-globe-asia"></i>
                        <div>
                            <strong>Location Intelligence</strong>
                            <span>GPS-based emergency mapping</span>
                        </div>
                    </div>
                    <div class="about-card about-card-3">
                        <i class="fas fa-bell"></i>
                        <div>
                            <strong>Instant Alerts</strong>
                            <span>Real-time notifications system</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container text-center" data-aos="zoom-in">
        <h2>Ready to Make a Difference?</h2>
        <p>Join thousands of people working together to save lives during emergencies.</p>
        <div class="cta-buttons">
            <a href="<?php echo SITE_URL; ?>register.php" class="btn btn-light btn-lg">
                <i class="fas fa-user-plus"></i> Create Free Account
            </a>
            <a href="<?php echo SITE_URL; ?>login.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="contact-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Contact</span>
            <h2>Get in Touch</h2>
            <p>Have questions? We're here to help you</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="contact-card">
                    <i class="fas fa-envelope"></i>
                    <h5>Email Us</h5>
                    <p>meenalpatro@gmail.com/ashishraunksmp@gmail.com</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="contact-card">
                    <i class="fas fa-phone-alt"></i>
                    <h5>Call Us</h5>
                    <p>+91 81027-40438</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="contact-card">
                    <i class="fas fa-headset"></i>
                    <h5>Emergency Helpline</h5>
                    <p>Dial 112 — National Emergency</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.counter');
    const animateCounter = (el) => {
        const target = parseInt(el.dataset.target) || 0;
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                el.textContent = target.toLocaleString();
                clearInterval(timer);
            } else {
                el.textContent = Math.floor(current).toLocaleString();
            }
        }, 16);
    };

    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(c => statsObserver.observe(c));

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

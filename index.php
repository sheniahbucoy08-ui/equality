<?php
require_once __DIR__ . '/includes/auth.php';
// If already logged in, go straight to dashboard
if (isLoggedIn()) {
    header('Location: /equalvoice/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EqualVoice — Gender-Balanced Leadership Platform</title>
  <meta name="description" content="EqualVoice empowers diverse voices, tracks gender representation, and connects leaders with mentors for a more inclusive workplace.">
  <link rel="stylesheet" href="/equalvoice/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="landing-page">

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Navigation -->
<nav class="landing-nav" id="landingNav">
  <div class="nav-logo">
    <div class="logo-icon" aria-hidden="true"><i class="fas fa-equals"></i></div>
    <span>EqualVoice</span>
  </div>
  <div class="nav-actions">
    <a href="/equalvoice/login.php?tab=login" class="btn btn-ghost btn-sm">Sign In</a>
    <a href="/equalvoice/login.php?tab=register" class="btn btn-primary btn-sm">Join Free</a>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero">
  <div class="hero-content">
    <div class="hero-text fade-in">
      
      <h1>
        Every Voice<br>
        <span class="highlight">Deserves to Lead</span>
      </h1>
      <p>EqualVoice is a platform that tracks gender representation in leadership, connects you with mentors, amplifies community stories, and fights for a workplace where everyone belongs.</p>
      <div class="hero-actions">
        <a href="/equalvoice/login.php?tab=register" class="btn-hero-primary">
          <i class="fas fa-rocket"></i> Get Started Free
        </a>
        <a href="/equalvoice/login.php?tab=login" class="btn-hero-secondary">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </a>
      </div>
    </div>
    <!--
    <div class="hero-visual">
      <div class="hero-card">
        <div class="card-icon"><i class="fas fa-chart-pie" style="color:#F3EBA5;"></i></div>
        <div class="card-stat">+42%</div>
        <div class="card-label">Diversity improvement tracked</div>
      </div>
      <div class="hero-card">
        <div class="card-icon"><i class="fas fa-users" style="color:#ED8E89;"></i></div>
        <div class="card-stat">12K+</div>
        <div class="card-label">Leaders empowered</div>
      </div>
      <div class="hero-card">
        <div class="card-icon"><i class="fas fa-handshake" style="color:#F7B685;"></i></div>
        <div class="card-stat">850+</div>
        <div class="card-label">Mentorship connections</div>
      </div>
      <div class="hero-card">
        <div class="card-icon"><i class="fas fa-book-open" style="color:#B4A8E0;"></i></div>
        <div class="card-stat">98%</div>
        <div class="card-label">Say workplace improved</div>
      </div>
    </div>
  </div>-->
</section>

<!-- Pride Stripe -->
<div class="pride-bar"></div>

<!-- Features Section -->
<section class="features-section">
  <div class="section-tag"><i class="fas fa-star"></i> &nbsp; Platform Features</div>
  <h2>Everything You Need to <span style="background:var(--gradient-primary);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Lead Inclusively</span></h2>
  <p class="section-desc">Powerful tools built for advocates, leaders, allies, and everyone who believes equitable workplaces create better outcomes for all.</p>

  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon" style="background:linear-gradient(135deg,#ED8E89,#ff9f43);">
        <i class="fas fa-book-open"></i>
      </div>
      <h3>Story Wall</h3>
      <p>Share your experiences, read others' journeys, and build a community of solidarity. Anonymous posting available for sensitive topics.</p>
      <a href="/equalvoice/login.php" class="feature-link">Explore stories <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="feature-card">
      <div class="feature-icon" style="background:linear-gradient(135deg,#B4A8E0,#9C8FCC);">
        <i class="fas fa-chart-donut"></i>
      </div>
      <h3>Leadership Tracker</h3>
      <p>Real-time data on gender representation across Women, Men, Non-Binary, Transgender, Gay, and Lesbian leaders — with pride colors.</p>
      <a href="/equalvoice/login.php" class="feature-link">View tracker <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="feature-card">
      <div class="feature-icon" style="background:linear-gradient(135deg,#F7B685,#4facfe);">
        <i class="fas fa-user-graduate"></i>
      </div>
      <h3>Mentorship</h3>
      <p>Connect with experienced leaders who champion diversity. Request mentorship, share goals, and grow with guidance from trailblazers.</p>
      <a href="/equalvoice/login.php" class="feature-link">Find a mentor <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="feature-card">
      <div class="feature-icon" style="background:linear-gradient(135deg,#94C691,#9BD6D9);">
        <i class="fas fa-graduation-cap"></i>
      </div>
      <h3>Learning Hub</h3>
      <p>Bite-sized lessons on gender equality, unconscious bias, equal pay, allyship, and building inclusive teams — at your own pace.</p>
      <a href="/equalvoice/login.php" class="feature-link">Start learning <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="feature-card">
      <div class="feature-icon" style="background:linear-gradient(135deg,#94C691,#e84393);">
        <i class="fas fa-hands-helping"></i>
      </div>
      <h3>Help Desk</h3>
      <p>Report workplace issues confidentially, access counselors, legal aid, crisis lines, and LGBTQ+ support resources — you're not alone.</p>
      <a href="/equalvoice/login.php" class="feature-link">Get support <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="feature-card">
      <div class="feature-icon" style="background:linear-gradient(135deg,#F3EBA5,#F7B685);">
        <i class="fas fa-briefcase"></i>
      </div>
      <h3>Job Fairness Tracker</h3>
      <p>Compare companies on gender pay gap, women in leadership, and fairness scores — so you can make informed career decisions.</p>
      <a href="/equalvoice/login.php" class="feature-link">Browse companies <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- Stats Banner
<section class="stats-section">
  <div class="stats-inner">
    <div class="stat-item">
      <span class="stat-num" data-target="12000">0</span>
      <div class="stat-desc">Leaders Empowered</div>
    </div>
    <div class="stat-item">
      <span class="stat-num" data-target="850">0</span>
      <div class="stat-desc">Mentorship Connections</div>
    </div>
    <div class="stat-item">
      <span class="stat-num" data-target="5200">0</span>
      <div class="stat-desc">Stories Shared</div>
    </div>
    <div class="stat-item">
      <span class="stat-num" data-target="98">0</span>
      <div class="stat-desc">% Satisfaction Rate</div>
    </div>
  </div>
</section>
-->

<!-- Gender Representation Section -->
<section class="gender-section">
  <div class="gender-inner">
    <div class="gender-text">
      <div class="section-tag" style="text-align:left;"><i class="fas fa-rainbow"></i> &nbsp; Gender Inclusion</div>
      <h2>Tracking All Gender<br>Identities in Leadership</h2>
      <p>We go beyond the binary. EqualVoice tracks representation for Women, Men, Non-Binary, Transgender, Gay, and Lesbian leaders — each with their own pride colors — because every identity deserves to be seen and celebrated.</p>
      <a href="/equalvoice/login.php" class="btn btn-primary">
        <i class="fas fa-chart-pie"></i> See Full Tracker
      </a>
    </div>
    <div class="gender-legend">
      <div class="legend-item">
        <div class="legend-color" style="background:#ED8E89; border-radius:3px; width:14px; height:14px; flex-shrink:0;"></div>
        <span class="legend-label">Women</span>
        <div class="legend-bar" style="background:#ED8E89; width:28%; height:10px; border-radius:9999px;"></div>
        <span class="legend-value">28%</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background:#9BD6D9; border-radius:3px; width:14px; height:14px; flex-shrink:0;"></div>
        <span class="legend-label">Men</span>
        <div class="legend-bar" style="background:#9BD6D9; width:42%; height:10px; border-radius:9999px;"></div>
        <span class="legend-value">42%</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background:#F3EBA5; border-radius:3px; width:14px; height:14px; flex-shrink:0;"></div>
        <span class="legend-label">Non-Binary</span>
        <div class="legend-bar" style="background:#F3EBA5; width:8%; height:10px; border-radius:9999px;"></div>
        <span class="legend-value">8%</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background:linear-gradient(90deg,#F7B685,#ED8E89); border-radius:3px; width:14px; height:14px; flex-shrink:0;"></div>
        <span class="legend-label">Transgender</span>
        <div class="legend-bar" style="background:linear-gradient(90deg,#F7B685,#ED8E89); width:10%; height:10px; border-radius:9999px;"></div>
        <span class="legend-value">10%</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background:#B4A8E0; border-radius:3px; width:14px; height:14px; flex-shrink:0;"></div>
        <span class="legend-label">Gay / Pride</span>
        <div class="legend-bar" style="background:#B4A8E0; width:7%; height:10px; border-radius:9999px;"></div>
        <span class="legend-value">7%</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background:#94C691; border-radius:3px; width:14px; height:14px; flex-shrink:0;"></div>
        <span class="legend-label">Lesbian</span>
        <div class="legend-bar" style="background:#94C691; width:5%; height:10px; border-radius:9999px;"></div>
        <span class="legend-value">5%</span>
      </div>
    </div>
  </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
  <div class="cta-inner">
    <h2>Ready to Make Your Voice Heard?</h2>
    <p>Join thousands of leaders, advocates, and allies building a more inclusive world — one conversation at a time.</p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="/equalvoice/login.php?tab=register" class="btn-hero-primary">
        <i class="fas fa-user-plus"></i> Create Free Account
      </a>
      <a href="/equalvoice/login.php?tab=login" class="btn-hero-secondary">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </a>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="landing-footer">
  <div class="footer-inner">
    <div class="footer-logo">EqualVoice</div>
    <div style="font-size:.75rem;color:rgba(255,255,255,.4);">&copy; <?= date('Y') ?> EqualVoice. All rights reserved.</div>
  </div>
  <div class="footer-pride"></div>
</footer>

<script>
// Scroll nav effect
window.addEventListener('scroll', () => {
  document.getElementById('landingNav').classList.toggle('scrolled', window.scrollY > 40);
});

// Animated counters
function animateCounters() {
  document.querySelectorAll('.stat-num[data-target]').forEach(el => {
    const target = +el.dataset.target;
    let current = 0;
    const step = target / 60;
    const timer = setInterval(() => {
      current += step;
      if (current >= target) { current = target; clearInterval(timer); }
      el.textContent = Math.floor(current).toLocaleString() + (target === 98 ? '%' : '+');
    }, 25);
  });
}

// Intersection observer for counters
const statsSection = document.querySelector('.stats-section');
if (statsSection) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { animateCounters(); observer.disconnect(); } });
  }, { threshold: 0.4 });
  observer.observe(statsSection);
}
</script>
</body>
</html>

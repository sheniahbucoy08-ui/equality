<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    header('Location: /equalvoice/dashboard.php');
    exit;
}
$activeTab = $_GET['tab'] ?? 'login';
$activeTab = in_array($activeTab, ['login', 'register']) ? $activeTab : 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — EqualVoice</title>
  <link rel="stylesheet" href="/equalvoice/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="auth-page">

<div class="toast-container" id="toastContainer"></div>

<div class="auth-wrapper">
  <!-- Left panel: brand + features -->
  <div class="auth-panel">
    <div class="auth-brand">
      <div class="brand-icon"><img src="/equalvoice/images/Logo.jpg" alt="EqualVoice"></div>
      <h1>EqualVoice</h1>
      <p>Gender-Balanced Leadership Platform</p>
    </div>
    <ul class="auth-features">
      <li><i class="fas fa-chart-pie"></i> Track gender representation</li>
      <li><i class="fas fa-book-open"></i> Share &amp; read community stories</li>
      <li><i class="fas fa-user-graduate"></i> Connect with mentors</li>
      <li><i class="fas fa-graduation-cap"></i> Learn at your own pace</li>
      <li><i class="fas fa-shield-alt"></i> Anonymous reporting</li>
      <li><i class="fas fa-briefcase"></i> Job fairness insights</li>
    </ul>
    <div class="pride-stripe">
      <span></span><span></span><span></span>
      <span></span><span></span><span></span>
    </div>
  </div>

  <!-- Right panel: form -->
  <div class="auth-form-panel">
    <h2>Welcome Back</h2>
    <p class="auth-subtitle">Sign in to your account or create a new one</p>

    <!-- Tabs -->
    <div class="auth-tabs">
      <button class="auth-tab <?= $activeTab === 'login' ? 'active' : '' ?>" onclick="switchTab('login')">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
      <button class="auth-tab <?= $activeTab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">
        <i class="fas fa-user-plus"></i> Register
      </button>
    </div>

    <!-- Error message -->
    <div class="error-message" id="authError">
      <i class="fas fa-exclamation-circle"></i>
      <span id="authErrorText"></span>
    </div>

    <!-- Login Form -->
    <form class="auth-form <?= $activeTab === 'login' ? 'active' : '' ?>" id="loginForm">
      <div class="form-group">
        <label for="loginEmail"><i class="fas fa-envelope"></i> Email Address</label>
        <div class="input-icon-wrap">
          <i class="input-icon fas fa-envelope"></i>
          <input type="email" id="loginEmail" name="email" placeholder="you@example.com" required autocomplete="email">
        </div>
      </div>
      <div class="form-group">
        <label for="loginPassword"><i class="fas fa-lock"></i> Password</label>
        <div class="input-icon-wrap">
          <i class="input-icon fas fa-lock"></i>
          <input type="password" id="loginPassword" name="password" placeholder="Your password" required autocomplete="current-password">
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full" id="loginBtn">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>

      <div class="auth-divider">or use demo account</div>

      <div class="demo-hints">
        <div class="demo-title"><i class="fas fa-info-circle"></i> Demo Accounts (click to fill)</div>
        <div class="demo-account" onclick="fillDemo('admin@equalvoice.com','admin123')">
          <span class="demo-role"><i class="fas fa-shield-alt"></i> Admin</span>
          <span class="demo-creds">admin@equalvoice.com / admin123</span>
        </div>
        <div class="demo-account" onclick="fillDemo('user@example.com','user123')">
          <span class="demo-role"><i class="fas fa-user"></i> User</span>
          <span class="demo-creds">user@example.com / user123</span>
        </div>
        <div class="demo-account" onclick="fillDemo('mentor@equalvoice.com','mentor123')">
          <span class="demo-role"><i class="fas fa-user-graduate"></i> Mentor (Elena)</span>
          <span class="demo-creds">mentor@equalvoice.com / mentor123</span>
        </div>
        <div class="demo-account" onclick="fillDemo('amina@equalvoice.com','mentor123')">
          <span class="demo-role"><i class="fas fa-user-graduate"></i> Mentor (Amina)</span>
          <span class="demo-creds">amina@equalvoice.com / mentor123</span>
        </div>
        <div class="demo-account" onclick="fillDemo('jordan@equalvoice.com','mentor123')">
          <span class="demo-role"><i class="fas fa-user-graduate"></i> Mentor (Jordan)</span>
          <span class="demo-creds">jordan@equalvoice.com / mentor123</span>
        </div>
        <div class="demo-account" onclick="fillDemo('marcus@equalvoice.com','mentor123')">
          <span class="demo-role"><i class="fas fa-user-graduate"></i> Mentor (Marcus)</span>
          <span class="demo-creds">marcus@equalvoice.com / mentor123</span>
        </div>
        <div class="demo-account" onclick="fillDemo('priya@equalvoice.com','mentor123')">
          <span class="demo-role"><i class="fas fa-user-graduate"></i> Mentor (Priya)</span>
          <span class="demo-creds">priya@equalvoice.com / mentor123</span>
        </div>
      </div>
    </form>

    <!-- Register Form -->
    <form class="auth-form <?= $activeTab === 'register' ? 'active' : '' ?>" id="registerForm">
      <div class="form-row">
        <div class="form-group">
          <label for="regName">Full Name</label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-user"></i>
            <input type="text" id="regName" name="name" placeholder="Jane Leader" required autocomplete="name">
          </div>
        </div>
        <div class="form-group">
          <label for="regDept">Department</label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-building"></i>
            <input type="text" id="regDept" name="department" placeholder="Technology" autocomplete="off">
          </div>
        </div>
      </div>
      <div class="form-group">
        <label for="regEmail">Email Address</label>
        <div class="input-icon-wrap">
          <i class="input-icon fas fa-envelope"></i>
          <input type="email" id="regEmail" name="email" placeholder="you@example.com" required autocomplete="email">
        </div>
      </div>
      <div class="form-group">
        <label for="regGender">Gender Identity <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
        <select id="regGender" name="gender_identity">
          <option value="">Prefer not to say</option>
          <option value="Woman">Woman</option>
          <option value="Man">Man</option>
          <option value="Non-Binary">Non-Binary</option>
          <option value="Transgender Woman">Transgender Woman</option>
          <option value="Transgender Man">Transgender Man</option>
          <option value="Gay Man">Gay Man</option>
          <option value="Lesbian">Lesbian</option>
          <option value="Bisexual">Bisexual</option>
          <option value="Queer">Queer</option>
          <option value="Other">Other / Self-describe</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="regPassword">Password</label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-lock"></i>
            <input type="password" id="regPassword" name="password" placeholder="Min 6 characters" required autocomplete="new-password">
          </div>
        </div>
        <div class="form-group">
          <label for="regConfirm">Confirm Password</label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-lock"></i>
            <input type="password" id="regConfirm" name="confirm_password" placeholder="Repeat password" required autocomplete="new-password">
          </div>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full" id="registerBtn">
        <i class="fas fa-user-plus"></i> Create Account
      </button>
    </form>

    <div class="auth-footer-link">
      <a href="/equalvoice/index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
    </div>
  </div>
</div>

<script src="/equalvoice/js/auth.js"></script>
<script>
// Init tab from URL
document.addEventListener('DOMContentLoaded', () => {
  const urlTab = new URLSearchParams(location.search).get('tab');
  if (urlTab === 'register') switchTab('register');
});
</script>
</body>
</html>

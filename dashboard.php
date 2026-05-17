<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$user = currentUser();

// Admins live in admin.php — separate dashboards. Auto-redirect on root visit
// (no ?view query) so admin only ever lands on the admin panel by default.
if ($user['role'] === 'admin' && empty($_GET['view'])) {
    header('Location: /equalvoice/admin.php');
    exit;
}
$isAdmin = ($user['role'] === 'admin');
$initials = function(string $n): string {
    $p = array_filter(explode(' ', trim($n)));
    $i = '';
    foreach (array_slice($p, 0, 2) as $w) $i .= strtoupper($w[0] ?? '');
    return $i ?: '?';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — EqualVoice</title>
  <link rel="stylesheet" href="/equalvoice/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">

  <!-- Sidebar Overlay (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <!-- ====================== SIDEBAR ====================== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
      <div class="brand-logo" aria-hidden="true"><i class="fas fa-equals"></i></div>
      <div class="brand-name">EqualVoice<span>Leadership Platform</span></div>
    </div>

    <nav class="sidebar__nav">
      <?php if (!$isAdmin): ?>
      <span class="nav-section-label">Main</span>

      <button class="sidebar__link active" data-view="stories" onclick="loadView('stories')">
        <span class="nav-icon"><i class="fas fa-book-open"></i></span> Story Wall
      </button>

      <button class="sidebar__link" data-view="learning" onclick="loadView('learning')">
        <span class="nav-icon"><i class="fas fa-graduation-cap"></i></span> Learning Hub
      </button>

      <button class="sidebar__link" data-view="helpdesk" onclick="loadView('helpdesk')">
        <span class="nav-icon"><i class="fas fa-hands-helping"></i></span> Help Desk
      </button>

      <button class="sidebar__link" data-view="opportunities" onclick="loadView('opportunities')">
        <span class="nav-icon"><i class="fas fa-briefcase"></i></span> Opportunities
      </button>

      <button class="sidebar__link" data-view="leadership" onclick="loadView('leadership')">
        <span class="nav-icon"><i class="fas fa-chart-pie"></i></span> Leadership Tracker
      </button>
      <?php endif; ?>

      <?php if ($isAdmin): ?>
      <span class="nav-section-label">Admin</span>
      <a class="sidebar__link active" href="/equalvoice/admin.php">
        <span class="nav-icon"><i class="fas fa-shield-alt"></i></span> Admin Panel
      </a>
      <?php endif; ?>

      <span class="nav-section-label">Account</span>
      <a class="sidebar__link" href="/equalvoice/profile.php">
        <span class="nav-icon"><i class="fas fa-user"></i></span> My Profile
      </a>
    </nav>

    <div class="sidebar__footer">
      <a class="sidebar-user" href="/equalvoice/profile.php">
        <div class="avatar avatar-sm" style="background:<?= htmlspecialchars($user['avatar_color']) ?>;">
          <?= htmlspecialchars($initials($user['name'])) ?>
        </div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
        </div>
        <i class="fas fa-sign-out-alt logout-icon" title="Logout" onclick="event.preventDefault();confirmLogout();"></i>
      </a>
    </div>
    <div class="sidebar-pride-bar"></div>
  </aside>
  <!-- ====================== /SIDEBAR ===================== -->

  <!-- Main -->
  <div class="main-content">
    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()">
          <span></span><span></span><span></span>
        </button>
        <div>
          <div class="page-title" id="pageTitle">Story Wall</div>
          <div class="page-subtitle" id="pageSubtitle">Community stories and experiences</div>
        </div>
      </div>
      <div class="topbar-right">
        <?php if ($user['role'] === 'admin'): ?>
        <a href="/equalvoice/admin.php" class="btn btn-primary btn-sm" title="Open Admin Panel">
          <i class="fas fa-shield-alt"></i> Admin Panel
        </a>
        <?php endif; ?>
        <a class="topbar-user" href="/equalvoice/profile.php">
          <div class="text-right" style="text-align:right;">
            <div class="topbar-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="topbar-role"><?= htmlspecialchars($user['role']) ?></div>
          </div>
          <div class="avatar" style="background:<?= htmlspecialchars($user['avatar_color']) ?>;">
            <?= htmlspecialchars($initials($user['name'])) ?>
          </div>
        </a>
      </div>
    </header>

    <!-- Dashboard Content Area -->
    <main class="dashboard-content" id="dashboardContent">
      <div class="page-loading"><div class="spinner"></div><span>Loading...</span></div>
    </main>
  </div>
</div>

<!-- Toast -->
<div class="toast-container" id="toastContainer"></div>

<!-- ===== MODALS ===== -->

<!-- Story Modal -->
<div class="modal-overlay" id="storyModal">
  <div class="modal">
    <div class="modal__header">
      <h3><i class="fas fa-pen" style="color:#ED8E89;"></i> &nbsp; Share Your Story</h3>
      <span class="modal__close" onclick="closeModal('storyModal')"><i class="fas fa-times"></i></span>
    </div>
    <div class="modal__body">
      <div class="form-group">
        <label>Your Story</label>
        <textarea id="storyContent" placeholder="Share your experience, victory, challenge, or message to the community... (min 10 characters)" rows="5"></textarea>
      </div>
      <div class="checkbox-group">
        <input type="checkbox" id="storyAnonymous">
        <label for="storyAnonymous">Post anonymously</label>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn-ghost" onclick="closeModal('storyModal')">Cancel</button>
      <button class="btn btn-primary" onclick="submitStory()">
        <i class="fas fa-paper-plane"></i> Share Story
      </button>
    </div>
  </div>
</div>

<!-- Mentorship Request Modal -->
<div class="modal-overlay" id="mentorModal">
  <div class="modal">
    <div class="modal__header">
      <h3><i class="fas fa-handshake" style="color:#F7B685;"></i> &nbsp; Request Mentorship</h3>
      <span class="modal__close" onclick="closeModal('mentorModal')"><i class="fas fa-times"></i></span>
    </div>
    <div class="modal__body">
      <div style="background:var(--bg-elevated);padding:1rem;border-radius:12px;margin-bottom:1rem;" id="mentorModalInfo"></div>
      <div class="form-group">
        <label>Your Message</label>
        <textarea id="mentorMessage" placeholder="Tell the mentor about your goals, what you're looking for in mentorship, and any specific areas you'd like guidance on... (min 10 characters)" rows="5"></textarea>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn-ghost" onclick="closeModal('mentorModal')">Cancel</button>
      <button class="btn btn-primary" onclick="submitMentorRequest()">
        <i class="fas fa-paper-plane"></i> Send Request
      </button>
    </div>
  </div>
</div>

<!-- Lesson Modal -->
<div class="modal-overlay" id="lessonModal">
  <div class="modal" style="max-width:620px;">
    <div class="modal__header">
      <h3 id="lessonModalTitle"></h3>
      <span class="modal__close" onclick="closeModal('lessonModal')"><i class="fas fa-times"></i></span>
    </div>
    <div class="modal__body" id="lessonModalBody"></div>
    <div class="modal__footer">
      <button class="btn btn-ghost" onclick="closeModal('lessonModal')">Close</button>
      <button class="btn btn-success" id="lessonCompleteBtn">
        <i class="fas fa-check"></i> Mark Complete
      </button>
    </div>
  </div>
</div>

<script>
const BASE = '/equalvoice/api/';
const CURRENT_USER_ID = <?= (int)$user['id'] ?>;
const CURRENT_USER_ROLE = '<?= htmlspecialchars($user['role']) ?>';
let currentMentorId = null;
let currentLessonId = null;
const PAGE_INFO = {
  stories:      { title: 'Story Wall',         subtitle: 'Community stories and shared experiences' },
  learning:     { title: 'Learning Hub',        subtitle: 'Grow your knowledge on gender equality' },
  helpdesk:     { title: 'Help Desk',           subtitle: 'Get support and report issues confidentially' },
  opportunities:{ title: 'Opportunities',       subtitle: 'Mentors and job fairness tracker' },
  leadership:   { title: 'Leadership Tracker',  subtitle: 'Gender representation in leadership roles' },
};
</script>
<script src="/equalvoice/js/main.js"></script>
<script src="/equalvoice/js/charts.js"></script>
</body>
</html>

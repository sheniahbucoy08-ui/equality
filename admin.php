<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireAdmin();
$user = currentUser();

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
  <title>Admin Analytics — EqualVoice</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/equalvoice/css/admin.css">
</head>
<body class="admin-body">

<div class="admin-wrapper">

  <!-- Sidebar Backdrop (mobile) -->
  <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar(false)"></div>

  <!-- ============== ADMIN SIDEBAR ============== -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="pride-stripe-top"></div>

    <div class="brand">
      <div class="brand-logo" aria-hidden="true">
        <i class="fas fa-equals"></i>
      </div>
      <div class="brand-info">
        <div class="brand-name">EqualVoice</div>
        <div class="brand-tag">Admin Panel</div>
      </div>
    </div>

    <nav class="nav-section">
      <span class="nav-label">Analytics</span>
      <button class="nav-item active" data-section="overview" onclick="showSection('overview')">
        <i class="fas fa-chart-pie"></i> Dashboard Overview
      </button>
      <button class="nav-item" data-section="analytics" onclick="showSection('analytics')">
        <i class="fas fa-chart-line"></i> Detailed Analytics
      </button>

      <span class="nav-label">Management</span>
      <button class="nav-item" data-section="users" onclick="showSection('users')">
        <i class="fas fa-users-cog"></i> User Management
      </button>
      <button class="nav-item" data-section="mentors" onclick="showSection('mentors')">
        <i class="fas fa-user-graduate"></i> Mentor Management
      </button>
      <button class="nav-item" data-section="leadership" onclick="showSection('leadership')">
        <i class="fas fa-rainbow"></i> Leadership Data
      </button>
      <button class="nav-item" data-section="reports" onclick="showSection('reports')">
        <i class="fas fa-flag"></i> Reports Inbox <span class="badge" id="navReportBadge" style="display:none;">0</span>
      </button>

      <span class="nav-label">Account</span>
      <a class="nav-item" href="/equalvoice/profile.php">
        <i class="fas fa-user-circle"></i> My Profile
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="footer-user">
        <div class="footer-avatar" style="background:<?= htmlspecialchars($user['avatar_color']) ?>;">
          <?= htmlspecialchars($initials($user['name'])) ?>
        </div>
        <div class="footer-info">
          <div class="footer-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="footer-role">Administrator</div>
        </div>
        <a href="/equalvoice/logout.php" class="footer-action" title="Sign out">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </div>
    </div>
  </aside>

  <!-- ============== MAIN CONTENT ============== -->
  <div class="admin-main">

    <!-- Topbar -->
    <header class="admin-topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="admin-burger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <div class="page-info">
          <h1 id="pageTitle">Dashboard Overview</h1>
          <div class="breadcrumb">
            <a href="/equalvoice/admin.php">Admin</a> <i class="fas fa-chevron-right" style="font-size:.55rem;margin:0 6px;"></i>
            <span id="pageBreadcrumb">Overview</span>
          </div>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="btn btn-eq-ghost btn-sm" onclick="loadOverview()">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <button class="btn btn-eq-primary btn-sm" onclick="window.print()">
          <i class="fas fa-download"></i> Export
        </button>
      </div>
    </header>

    <!-- Toast container -->
    <div class="eq-toast-container" id="toastContainer"></div>

    <!-- Content -->
    <main class="admin-content">

      <!-- ====== OVERVIEW SECTION ====== -->
      <section class="admin-section active" id="section-overview">
        <div class="admin-banner">
          <h2>Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! <i class="fas fa-rainbow ms-1" style="color:#F3EBA5;"></i></h2>
          <p>Real-time analytics and insights for the EqualVoice platform.</p>
        </div>

        <div id="kpiContainer">
          <div class="spinner-circle"></div>
        </div>

        <div class="row-2col">
          <div class="panel">
            <div class="panel-header">
              <h3 class="panel-title"><i class="fas fa-chart-area"></i> User Growth (Last 30 Days)</h3>
              <span class="text-eq-muted small">New registrations</span>
            </div>
            <div class="panel-body">
              <canvas id="userGrowthChart" style="max-height:280px;"></canvas>
            </div>
          </div>
          <div class="panel">
            <div class="panel-header">
              <h3 class="panel-title"><i class="fas fa-users"></i> Role Split</h3>
            </div>
            <div class="panel-body">
              <canvas id="roleChart" style="max-height:240px;"></canvas>
              <div class="text-center mt-3" id="roleLegend"></div>
            </div>
          </div>
        </div>

        <div class="row-2col">
          <div class="panel">
            <div class="panel-header">
              <h3 class="panel-title"><i class="fas fa-rainbow"></i> Gender Identity Distribution</h3>
              <span class="text-eq-muted small" id="genderTotal"></span>
            </div>
            <div class="panel-body">
              <div id="genderBars"></div>
            </div>
          </div>

          <div class="panel">
            <div class="panel-header">
              <h3 class="panel-title"><i class="fas fa-history"></i> Recent Activity</h3>
            </div>
            <div class="panel-body" id="recentActivity">
              <div class="spinner-circle"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- ====== MENTORS SECTION ====== -->
      <section class="admin-section" id="section-mentors">
        <div class="panel">
          <div class="panel-header">
            <h3 class="panel-title"><i class="fas fa-user-graduate"></i> Mentors</h3>
            <button class="btn btn-eq-primary btn-sm" onclick="openMentorForm()">
              <i class="fas fa-plus"></i> Add Mentor
            </button>
          </div>
          <div class="panel-body">
            <div id="mentorEditForm" style="display:none;background:var(--eq-bg-elevated);padding:1.25rem;border-radius:14px;margin-bottom:1.25rem;">
              <h4 id="mentorFormTitle" style="margin-bottom:1rem;font-size:1rem;font-weight:700;">Add Mentor</h4>
              <input type="hidden" id="editMentorId">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Mentor Name</label>
                  <input type="text" class="form-control" id="editMentorName" placeholder="e.g. Elena Vasquez">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Role Title</label>
                  <input type="text" class="form-control" id="editMentorRole" placeholder="e.g. Former CEO">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Expertise</label>
                  <input type="text" class="form-control" id="editMentorExpertise" placeholder="e.g. Startup scaling & executive coaching">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Mentor Account Email (optional)</label>
                  <input type="email" class="form-control" id="editMentorEmail" placeholder="mentor@equalvoice.com">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Rating (0-5)</label>
                  <input type="number" class="form-control" id="editMentorRating" min="0" max="5" step="0.1" value="4.8">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Sessions</label>
                  <input type="number" class="form-control" id="editMentorSessions" min="0" step="1" value="0">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Font Awesome Icon</label>
                  <input type="text" class="form-control" id="editMentorIcon" placeholder="fa-user-tie" value="fa-user">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Availability</label>
                  <select class="form-select" id="editMentorAvailable">
                    <option value="1">Available</option>
                    <option value="0">Unavailable</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">Bio (optional)</label>
                  <textarea class="form-control" id="editMentorBio" rows="3" placeholder="Short mentor bio"></textarea>
                </div>
              </div>
              <div class="d-flex gap-2 mt-3">
                <button class="btn btn-eq-primary" onclick="saveMentor()"><i class="fas fa-save"></i> Save</button>
                <button class="btn btn-eq-ghost" onclick="closeMentorForm()">Cancel</button>
              </div>
            </div>

            <div id="mentorsTableBody"><div class="spinner-circle"></div></div>
          </div>
        </div>
      </section>

      <!-- ====== ANALYTICS SECTION ====== -->
      <section class="admin-section" id="section-analytics">
        <div class="row-2col">
          <div class="panel">
            <div class="panel-header">
              <h3 class="panel-title"><i class="fas fa-flag"></i> Reports by Type</h3>
            </div>
            <div class="panel-body">
              <canvas id="reportTypesChart" style="max-height:300px;"></canvas>
            </div>
          </div>
          <div class="panel">
            <div class="panel-header">
              <h3 class="panel-title"><i class="fas fa-chart-bar"></i> Platform Engagement</h3>
            </div>
            <div class="panel-body">
              <canvas id="engagementChart" style="max-height:300px;"></canvas>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <h3 class="panel-title"><i class="fas fa-trophy"></i> Top Insights</h3>
          </div>
          <div class="panel-body" id="insightsContent">
            <div class="spinner-circle"></div>
          </div>
        </div>
      </section>

      <!-- ====== USERS SECTION ====== -->
      <section class="admin-section" id="section-users">
        <div class="panel">
          <div class="panel-header">
            <h3 class="panel-title"><i class="fas fa-users-cog"></i> All Users</h3>
            <div class="d-flex align-items-center gap-2">
              <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control" id="userSearch" placeholder="Search by name or email..." oninput="filterUsers()">
              </div>
              <span class="text-eq-muted small" id="userCountLabel">Loading...</span>
            </div>
          </div>
          <div class="panel-body no-padding" id="usersTableBody">
            <div class="spinner-circle"></div>
          </div>
        </div>

        <!-- Edit user profile form -->
        <div class="panel" id="userEditPanel" style="display:none;">
          <div class="panel-header">
            <h3 class="panel-title"><i class="fas fa-user-edit"></i> Edit User Profile</h3>
          </div>
          <div class="panel-body">
            <input type="hidden" id="editUserId">
            <div class="row g-3 mb-2">
              <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" id="editUserName" placeholder="Full name">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="editUserEmail" placeholder="Email address">
              </div>
              <div class="col-md-6">
                <label class="form-label">Department</label>
                <input type="text" class="form-control" id="editUserDepartment" placeholder="Department">
              </div>
              <div class="col-md-6">
                <label class="form-label">Gender Identity</label>
                <select class="form-select" id="editUserGender">
                  <option value="">Prefer not to say</option>
                  <option>Woman</option>
                  <option>Man</option>
                  <option>Non-Binary</option>
                  <option>Transgender Woman</option>
                  <option>Transgender Man</option>
                  <option>Gay Man</option>
                  <option>Lesbian</option>
                  <option>Bisexual</option>
                  <option>Queer</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Avatar Color</label>
                <input type="color" class="form-control form-control-color" id="editUserColor" value="#B4A8E0" style="width:72px;">
              </div>
              <div class="col-md-8">
                <label class="form-label">Interests</label>
                <input type="text" class="form-control" id="editUserInterests" placeholder="Interests">
              </div>
              <div class="col-12">
                <label class="form-label">Bio</label>
                <textarea class="form-control" id="editUserBio" rows="3" placeholder="Bio"></textarea>
              </div>
              <div class="col-12">
                <label class="form-label">Career Goals</label>
                <input type="text" class="form-control" id="editUserGoals" placeholder="Career goals">
              </div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-eq-primary" onclick="saveUserProfileEdit()"><i class="fas fa-save"></i> Save Changes</button>
              <button class="btn btn-eq-ghost" onclick="closeUserEditPanel()">Cancel</button>
            </div>
          </div>
        </div>
      </section>

      <!-- ====== LEADERSHIP SECTION ====== -->
      <section class="admin-section" id="section-leadership">
        <div class="panel">
          <div class="panel-header">
            <h3 class="panel-title"><i class="fas fa-rainbow"></i> Leadership Data Editor</h3>
            <button class="btn btn-eq-primary btn-sm" onclick="openOrgForm()">
              <i class="fas fa-plus"></i> Add Organization
            </button>
          </div>
          <div class="panel-body">
            <!-- Add/Edit form -->
            <div id="orgEditForm" style="display:none;background:var(--eq-bg-elevated);padding:1.25rem;border-radius:14px;margin-bottom:1.25rem;">
              <h4 id="orgFormTitle" style="margin-bottom:1rem;font-size:1rem;font-weight:700;">Add Organization</h4>
              <input type="hidden" id="editOrgId">
              <div class="mb-3">
                <label class="form-label">Organization Name</label>
                <input type="text" class="form-control" id="editOrgName" placeholder="e.g. Tech Industry Average">
              </div>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label"><span class="gender-dot" style="background:#ED8E89;"></span>Women %</label>
                  <input type="number" class="form-control" id="editWomen" min="0" max="100" step="0.1">
                </div>
                <div class="col-md-4">
                  <label class="form-label"><span class="gender-dot" style="background:#9BD6D9;"></span>Men %</label>
                  <input type="number" class="form-control" id="editMen" min="0" max="100" step="0.1">
                </div>
                <div class="col-md-4">
                  <label class="form-label"><span class="gender-dot" style="background:#F3EBA5;"></span>Non-Binary %</label>
                  <input type="number" class="form-control" id="editNonbinary" min="0" max="100" step="0.1">
                </div>
                <div class="col-md-4">
                  <label class="form-label"><span class="gender-dot" style="background:#F7B685;"></span>Transgender %</label>
                  <input type="number" class="form-control" id="editTrans" min="0" max="100" step="0.1">
                </div>
                <div class="col-md-4">
                  <label class="form-label"><span class="gender-dot" style="background:#B4A8E0;"></span>Gay/Pride %</label>
                  <input type="number" class="form-control" id="editGay" min="0" max="100" step="0.1">
                </div>
                <div class="col-md-4">
                  <label class="form-label"><span class="gender-dot" style="background:#94C691;"></span>Lesbian %</label>
                  <input type="number" class="form-control" id="editLesbian" min="0" max="100" step="0.1">
                </div>
              </div>
              <div class="mb-3 mt-3">
                <label class="form-label">Total Leaders (count)</label>
                <input type="number" class="form-control" id="editTotalLeaders" min="0">
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-eq-primary" onclick="saveOrg()"><i class="fas fa-save"></i> Save</button>
                <button class="btn btn-eq-ghost" onclick="document.getElementById('orgEditForm').style.display='none'">Cancel</button>
              </div>
            </div>
            <div id="leadershipTableBody"><div class="spinner-circle"></div></div>
          </div>
        </div>
      </section>

      <!-- ====== REPORTS SECTION ====== -->
      <section class="admin-section" id="section-reports">
        <div class="panel">
          <div class="panel-header">
            <h3 class="panel-title"><i class="fas fa-flag"></i> Reports Inbox</h3>
            <select class="form-select form-select-sm" style="width:auto;" id="reportFilter" onchange="loadReports()">
              <option value="">All Reports</option>
              <option value="open">Open Only</option>
              <option value="resolved">Resolved Only</option>
            </select>
          </div>
          <div class="panel-body" id="reportsContent">
            <div class="spinner-circle"></div>
          </div>
        </div>
      </section>

    </main>
  </div>
</div>

<!-- Bootstrap JS + Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const API = '/equalvoice/api/';
const MY_ID = <?= (int)$user['id'] ?>;
let allUsers = [];
let allMentors = [];
let charts = {};
const COLORS = {
  coral:'#ED8E89', peach:'#F7B685', yellow:'#F3EBA5',
  green:'#94C691', cyan:'#9BD6D9', lavender:'#B4A8E0',
};

// ---- Toast ----
function showToast(message, type='info') {
  const c = document.getElementById('toastContainer');
  const t = document.createElement('div');
  t.className = `eq-toast ${type}`;
  const icons = {success:'check-circle', error:'exclamation-circle', warning:'exclamation-triangle', info:'info-circle'};
  t.innerHTML = `<i class="fas fa-${icons[type]||'info-circle'} text-${type==='success'?'green':type==='error'?'coral':type==='warning'?'peach':'cyan'}"></i>
    <span>${escHtml(message)}</span>
    <button class="close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;
  c.appendChild(t);
  setTimeout(() => t.remove(), 4500);
}

function escHtml(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ---- Sidebar ----
function toggleSidebar(force) {
  const s = document.getElementById('adminSidebar');
  const b = document.getElementById('sidebarBackdrop');
  const open = force === undefined ? !s.classList.contains('open') : force;
  s.classList.toggle('open', open);
  b.classList.toggle('active', open);
}

// ---- Section navigation ----
function showSection(name) {
  document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item[data-section]').forEach(n => n.classList.remove('active'));
  document.getElementById(`section-${name}`).classList.add('active');
  document.querySelector(`.nav-item[data-section="${name}"]`)?.classList.add('active');

  const titles = {
    overview:    {t:'Dashboard Overview',  b:'Overview'},
    analytics:   {t:'Detailed Analytics',  b:'Analytics'},
    users:       {t:'User Management',     b:'Users'},
    mentors:     {t:'Mentor Management',   b:'Mentors'},
    leadership:  {t:'Leadership Data',     b:'Leadership'},
    reports:     {t:'Reports Inbox',       b:'Reports'},
  };
  document.getElementById('pageTitle').textContent      = titles[name].t;
  document.getElementById('pageBreadcrumb').textContent = titles[name].b;

  toggleSidebar(false);

  // Lazy load
  if (name === 'overview' && !document.getElementById('kpiContainer').dataset.loaded) loadOverview();
  if (name === 'analytics' && !document.getElementById('insightsContent').dataset.loaded) loadAnalytics();
  if (name === 'users' && !allUsers.length) loadUsers();
  if (name === 'mentors') loadMentors();
  if (name === 'leadership') loadLeadership();
  if (name === 'reports') loadReports();
}

// ============================================================
// OVERVIEW
// ============================================================
async function loadOverview() {
  const res = await fetch(API + 'admin_analytics.php');
  const data = await res.json();
  if (!data.success) { showToast(data.error, 'error'); return; }

  document.getElementById('kpiContainer').dataset.loaded = '1';

  // KPI cards
  const s = data.stats;
  document.getElementById('kpiContainer').innerHTML = `
    <div class="kpi-grid">
      <div class="kpi-card k-lavender">
        <div class="kpi-head">
          <div class="kpi-icon"><i class="fas fa-users"></i></div>
          <span class="kpi-trend up"><i class="fas fa-arrow-up"></i> Active</span>
        </div>
        <div class="kpi-value">${s.total_users}</div>
        <div class="kpi-label">Total Users (${s.admin_users} admin)</div>
      </div>
      <div class="kpi-card k-coral">
        <div class="kpi-head">
          <div class="kpi-icon"><i class="fas fa-book-open"></i></div>
          <span class="kpi-trend neutral"><i class="fas fa-comment"></i> Stories</span>
        </div>
        <div class="kpi-value">${s.total_stories}</div>
        <div class="kpi-label">Stories Shared (${s.anon_stories} anon)</div>
      </div>
      <div class="kpi-card k-peach">
        <div class="kpi-head">
          <div class="kpi-icon"><i class="fas fa-flag"></i></div>
          <span class="kpi-trend ${s.open_reports>0?'down':'up'}">
            <i class="fas fa-${s.open_reports>0?'exclamation':'check'}"></i> ${s.open_reports>0?'Open':'Clear'}
          </span>
        </div>
        <div class="kpi-value">${s.open_reports}</div>
        <div class="kpi-label">Open Reports (${s.resolved_reports} resolved)</div>
      </div>
      <div class="kpi-card k-green">
        <div class="kpi-head">
          <div class="kpi-icon"><i class="fas fa-handshake"></i></div>
          <span class="kpi-trend up"><i class="fas fa-arrow-up"></i> Active</span>
        </div>
        <div class="kpi-value">${s.mentorship_requests}</div>
        <div class="kpi-label">Mentorship Requests</div>
      </div>
      <div class="kpi-card k-cyan">
        <div class="kpi-head">
          <div class="kpi-icon"><i class="fas fa-graduation-cap"></i></div>
          <span class="kpi-trend neutral"><i class="fas fa-book"></i> Learning</span>
        </div>
        <div class="kpi-value">${s.lesson_completions}</div>
        <div class="kpi-label">Lessons Completed (${s.total_lessons} total)</div>
      </div>
      <div class="kpi-card k-coral">
        <div class="kpi-head">
          <div class="kpi-icon"><i class="fas fa-heart"></i></div>
          <span class="kpi-trend up">Engagement</span>
        </div>
        <div class="kpi-value">${s.total_likes}</div>
        <div class="kpi-label">Story Likes</div>
      </div>
      <div class="kpi-card k-yellow">
        <div class="kpi-head">
          <div class="kpi-icon"><i class="fas fa-user-tie"></i></div>
          <span class="kpi-trend neutral">Available</span>
        </div>
        <div class="kpi-value">${s.total_mentors}</div>
        <div class="kpi-label">Active Mentors</div>
      </div>
      <div class="kpi-card k-lavender">
        <div class="kpi-head">
          <div class="kpi-icon"><i class="fas fa-briefcase"></i></div>
          <span class="kpi-trend neutral">Tracked</span>
        </div>
        <div class="kpi-value">${s.companies_tracked}</div>
        <div class="kpi-label">Companies Tracked</div>
      </div>
    </div>`;

  // Update sidebar badge
  const badge = document.getElementById('navReportBadge');
  if (s.open_reports > 0) { badge.textContent = s.open_reports; badge.style.display = 'inline-block'; }
  else                    { badge.style.display = 'none'; }

  // Charts
  drawUserGrowthChart(data.user_growth);
  drawRoleChart(data.role_breakdown);
  drawGenderBars(data.gender_breakdown);
  renderRecentActivity(data.recent_users, data.recent_reports);

  // Cache for analytics tab
  window.__analyticsData = data;
}

function drawUserGrowthChart(growth) {
  const ctx = document.getElementById('userGrowthChart');
  if (charts.growth) charts.growth.destroy();
  const labels = growth.map(g => g.label);
  const values = growth.map(g => g.count);
  charts.growth = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'New Users',
        data: values,
        borderColor: COLORS.lavender,
        backgroundColor: 'rgba(180,168,224,0.15)',
        fill: true,
        tension: 0.4,
        pointBackgroundColor: COLORS.coral,
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        borderWidth: 3,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#E8DFD3' } },
        x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
      },
    },
  });
}

function drawRoleChart(roles) {
  const ctx = document.getElementById('roleChart');
  if (charts.role) charts.role.destroy();
  charts.role = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Users', 'Admins'],
      datasets: [{
        data: [roles.user, roles.admin],
        backgroundColor: [COLORS.cyan, COLORS.lavender],
        borderColor: '#fff',
        borderWidth: 3,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: true,
      cutout: '65%',
      plugins: { legend: { display: false } },
    },
  });
  document.getElementById('roleLegend').innerHTML = `
    <div class="d-flex justify-content-center gap-3 small">
      <span><span class="gender-dot" style="background:${COLORS.cyan};"></span> ${roles.user} Users</span>
      <span><span class="gender-dot" style="background:${COLORS.lavender};"></span> ${roles.admin} Admins</span>
    </div>`;
}

function drawGenderBars(breakdown) {
  if (!breakdown.length) {
    document.getElementById('genderBars').innerHTML = '<div class="empty-block"><i class="fas fa-rainbow"></i><h4>No gender data yet</h4><p>Users haven\'t shared their gender identity.</p></div>';
    return;
  }
  const total = breakdown.reduce((s, b) => s + b.count, 0);
  document.getElementById('genderTotal').textContent = `${total} users`;
  document.getElementById('genderBars').innerHTML = breakdown.map(b => {
    const pct = total > 0 ? (b.count / total * 100).toFixed(1) : 0;
    return `
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="small fw-bold"><span class="gender-dot" style="background:${b.color};margin-right:6px;"></span>${escHtml(b.label)}</span>
          <span class="small text-eq-muted">${b.count} (${pct}%)</span>
        </div>
        <div style="height:8px;background:var(--eq-bg-elevated);border-radius:9999px;overflow:hidden;">
          <div style="height:100%;width:${pct}%;background:${b.color};border-radius:9999px;transition:width .8s ease;"></div>
        </div>
      </div>`;
  }).join('');
}

function renderRecentActivity(users, reports) {
  const items = [];
  users.forEach(u => items.push({
    icon: 'user-plus', color: COLORS.lavender,
    text: `<strong>${escHtml(u.name)}</strong> joined as <em>${escHtml(u.role)}</em>`,
    time: u.time_ago,
  }));
  reports.forEach(r => items.push({
    icon: 'flag', color: r.status === 'open' ? COLORS.coral : COLORS.green,
    text: `<strong>${r.is_anonymous ? 'Anonymous' : escHtml(r.reporter || 'User')}</strong> reported <em>${escHtml(r.issue_type)}</em>`,
    time: r.time_ago,
  }));
  items.sort(() => 0); // already chronological from API

  document.getElementById('recentActivity').innerHTML = items.length ? items.slice(0, 8).map(i => `
    <div class="activity-item">
      <div class="a-icon" style="background:${i.color};"><i class="fas fa-${i.icon}"></i></div>
      <div class="a-text">
        ${i.text}
        <div class="a-time"><i class="fas fa-clock"></i> ${escHtml(i.time)}</div>
      </div>
    </div>`).join('') : '<div class="empty-block"><i class="fas fa-history"></i><h4>No activity yet</h4></div>';
}

// ============================================================
// ANALYTICS
// ============================================================
async function loadAnalytics() {
  const data = window.__analyticsData || (await (await fetch(API + 'admin_analytics.php')).json());
  if (!data) return;
  document.getElementById('insightsContent').dataset.loaded = '1';

  // Reports by type
  if (data.report_types?.length) {
    const ctx = document.getElementById('reportTypesChart');
    if (charts.reportTypes) charts.reportTypes.destroy();
    charts.reportTypes = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.report_types.map(r => r.issue_type),
        datasets: [{
          label: 'Count',
          data: data.report_types.map(r => r.count),
          backgroundColor: [COLORS.coral, COLORS.peach, COLORS.yellow, COLORS.green, COLORS.cyan, COLORS.lavender, COLORS.coral, COLORS.peach],
          borderRadius: 8,
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: { y: { grid: { display: false } }, x: { beginAtZero: true, ticks: { stepSize: 1 } } },
      },
    });
  } else {
    document.getElementById('reportTypesChart').parentElement.innerHTML =
      '<div class="empty-block"><i class="fas fa-flag"></i><h4>No reports yet</h4><p>Reports will appear here as they come in.</p></div>';
  }

  // Engagement bar chart
  const s = data.stats;
  const eCtx = document.getElementById('engagementChart');
  if (charts.engagement) charts.engagement.destroy();
  charts.engagement = new Chart(eCtx, {
    type: 'bar',
    data: {
      labels: ['Stories', 'Likes', 'Lessons', 'Mentorship'],
      datasets: [{
        data: [s.total_stories, s.total_likes, s.lesson_completions, s.mentorship_requests],
        backgroundColor: [COLORS.coral, COLORS.peach, COLORS.green, COLORS.cyan],
        borderRadius: 8,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: true,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
    },
  });

  // Insights
  const totalUsers = s.total_users || 1;
  const completionRate = totalUsers > 0 ? ((s.lesson_completions / (totalUsers * Math.max(1, s.total_lessons))) * 100).toFixed(1) : 0;
  const reportRate    = totalUsers > 0 ? (((s.open_reports + s.resolved_reports) / totalUsers) * 100).toFixed(1) : 0;
  const engagementRate= totalUsers > 0 ? ((s.total_stories / totalUsers) * 100).toFixed(1) : 0;

  document.getElementById('insightsContent').innerHTML = `
    <div class="row-3col" style="margin-bottom:0;">
      <div style="background:linear-gradient(135deg,#94C691,#7BB87F);color:#fff;border-radius:14px;padding:1.25rem;">
        <div style="font-size:.7rem;text-transform:uppercase;opacity:.85;letter-spacing:.05em;">Lesson Completion Rate</div>
        <div style="font-size:2rem;font-weight:900;line-height:1;margin-top:.5rem;">${completionRate}%</div>
        <div style="font-size:.75rem;opacity:.9;margin-top:.25rem;">of all available lessons</div>
      </div>
      <div style="background:linear-gradient(135deg,#B4A8E0,#9C8FCC);color:#fff;border-radius:14px;padding:1.25rem;">
        <div style="font-size:.7rem;text-transform:uppercase;opacity:.85;letter-spacing:.05em;">Story Engagement</div>
        <div style="font-size:2rem;font-weight:900;line-height:1;margin-top:.5rem;">${engagementRate}%</div>
        <div style="font-size:.75rem;opacity:.9;margin-top:.25rem;">stories per user ratio</div>
      </div>
      <div style="background:linear-gradient(135deg,#F7B685,#E89B65);color:#fff;border-radius:14px;padding:1.25rem;">
        <div style="font-size:.7rem;text-transform:uppercase;opacity:.85;letter-spacing:.05em;">Report Rate</div>
        <div style="font-size:2rem;font-weight:900;line-height:1;margin-top:.5rem;">${reportRate}%</div>
        <div style="font-size:.75rem;opacity:.9;margin-top:.25rem;">users who reported issues</div>
      </div>
    </div>`;
}

// ============================================================
// USERS
// ============================================================
async function loadUsers() {
  const res = await fetch(API + 'admin_users.php');
  const data = await res.json();
  if (!data.success) { showToast(data.error, 'error'); return; }
  allUsers = data.users;
  renderUsers(allUsers);
}

function renderUsers(users) {
  const lbl = document.getElementById('userCountLabel');
  if (lbl) lbl.textContent = `${users.length} user${users.length !== 1 ? 's' : ''}`;

  if (!users.length) {
    document.getElementById('usersTableBody').innerHTML = '<div class="empty-block"><i class="fas fa-users"></i><h4>No users found</h4></div>';
    return;
  }

  document.getElementById('usersTableBody').innerHTML = `
    <table class="admin-table">
      <thead><tr>
        <th>User</th><th>Role</th><th>Gender Identity</th><th>Joined</th><th>Last Login</th><th>Actions</th>
      </tr></thead>
      <tbody>${users.map(u => `
        <tr>
          <td>
            <div class="user-cell">
              <div class="avatar" style="background:${escHtml(u.avatar_color)};">${escHtml(u.name.split(' ').slice(0,2).map(w=>w[0]).join('').toUpperCase())}</div>
              <div>
                <div class="u-name">${escHtml(u.name)}</div>
                <div class="u-email">${escHtml(u.email)}</div>
              </div>
            </div>
          </td>
          <td>
            <span class="eq-badge role-${u.role}"><i class="fas fa-${u.role==='admin'?'shield-alt':'user'}"></i> ${u.role}</span>
            ${u.mentor_profile_id ? `<span class="eq-badge role-user" style="margin-left:6px;"><i class="fas fa-user-graduate"></i> mentor</span>` : ''}
          </td>
          <td>${u.gender_identity ? `<span class="gender-tag ${genderClass(u.gender_identity)}">${escHtml(u.gender_identity)}</span>` : '<span class="text-eq-muted small">—</span>'}</td>
          <td class="small text-eq-muted">${formatDate(u.created_at)}</td>
          <td class="small text-eq-muted">${u.last_login ? formatDate(u.last_login) : 'Never'}</td>
          <td>
            <div class="d-flex gap-1">
              ${u.id !== MY_ID ? `
                <button class="btn btn-eq-warm btn-icon" onclick="openUserEditPanel(${u.id})" title="Edit user profile"><i class="fas fa-pen"></i></button>
                ${!u.mentor_profile_id ? `<button class="btn btn-eq-success btn-icon" onclick="promoteToMentor(${u.id},'${escHtml(u.name).replace(/'/g,"\\'")}')" title="Promote to mentor account"><i class="fas fa-user-graduate"></i></button>` : ''}
                <button class="btn btn-eq-ghost btn-icon" onclick="toggleRole(${u.id},'${u.role}')" title="${u.role==='admin'?'Demote to User':'Promote to Admin'}"><i class="fas fa-${u.role==='admin'?'user':'shield-alt'}"></i></button>
                <button class="btn btn-eq-danger btn-icon" onclick="deleteUser(${u.id})" title="Delete"><i class="fas fa-trash"></i></button>
              ` : '<span class="small text-eq-muted">You</span>'}
            </div>
          </td>
        </tr>`).join('')}
      </tbody>
    </table>`;
}

function genderClass(g) {
  g = g.toLowerCase();
  if (g.includes('lesbian')) return 'lesbian';
  if (g.includes('gay')) return 'gay';
  if (g.includes('transgender') || g.includes('trans')) return 'transgender';
  if (g.includes('non-binary') || g.includes('nonbinary')) return 'nonbinary';
  if (g.includes('woman')) return 'women';
  if (g.includes('man')) return 'men';
  return 'gay';
}

function filterUsers() {
  const q = document.getElementById('userSearch').value.toLowerCase();
  renderUsers(q ? allUsers.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)) : allUsers);
}

async function toggleRole(id, current) {
  const newRole = current === 'admin' ? 'user' : 'admin';
  if (!confirm(`Change this user to ${newRole}?`)) return;
  const res = await fetch(API + 'admin_users.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'change_role',id,role:newRole})});
  const data = await res.json();
  showToast(data.message || data.error, data.success ? 'success' : 'error');
  if (data.success) loadUsers();
}

async function deleteUser(id) {
  if (!confirm('Delete this user permanently?')) return;
  const res = await fetch(API + 'admin_users.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'delete',id})});
  const data = await res.json();
  showToast(data.message || data.error, data.success ? 'success' : 'error');
  if (data.success) loadUsers();
}

async function promoteToMentor(id, name) {
  if (!confirm(`Promote ${name} to mentor account?`)) return;
  const res = await fetch(API + 'admin_users.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({action:'promote_to_mentor', id}),
  });
  const data = await res.json();
  showToast(data.message || data.error, data.success ? 'success' : 'error');
  if (data.success) {
    loadUsers();
    if (document.getElementById('section-mentors')?.classList.contains('active')) {
      loadMentors();
    }
  }
}

function openUserEditPanel(userId) {
  const u = allUsers.find(x => Number(x.id) === Number(userId));
  if (!u) {
    showToast('User record not found.', 'error');
    return;
  }

  document.getElementById('editUserId').value         = u.id;
  document.getElementById('editUserName').value       = u.name || '';
  document.getElementById('editUserEmail').value      = u.email || '';
  document.getElementById('editUserDepartment').value = u.department || '';
  document.getElementById('editUserGender').value     = u.gender_identity || '';
  document.getElementById('editUserBio').value        = u.bio || '';
  document.getElementById('editUserInterests').value  = u.interests || '';
  document.getElementById('editUserGoals').value      = u.goals || '';
  document.getElementById('editUserColor').value      = /^#[0-9A-Fa-f]{6}$/.test(u.avatar_color || '') ? u.avatar_color : '#B4A8E0';

  const panel = document.getElementById('userEditPanel');
  panel.style.display = 'block';
  panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeUserEditPanel() {
  document.getElementById('userEditPanel').style.display = 'none';
}

async function saveUserProfileEdit() {
  const id = Number(document.getElementById('editUserId').value || 0);
  if (!id) {
    showToast('Invalid user selected.', 'error');
    return;
  }

  const payload = {
    action: 'update_profile',
    id,
    name: document.getElementById('editUserName').value.trim(),
    email: document.getElementById('editUserEmail').value.trim(),
    department: document.getElementById('editUserDepartment').value.trim(),
    gender_identity: document.getElementById('editUserGender').value.trim(),
    bio: document.getElementById('editUserBio').value.trim(),
    interests: document.getElementById('editUserInterests').value.trim(),
    goals: document.getElementById('editUserGoals').value.trim(),
    avatar_color: (document.getElementById('editUserColor').value || '#B4A8E0').toUpperCase(),
  };

  const res = await fetch(API + 'admin_users.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  showToast(data.message || data.error, data.success ? 'success' : 'error');
  if (data.success) {
    closeUserEditPanel();
    loadUsers();
  }
}

// ============================================================
// MENTORS
// ============================================================
async function loadMentors() {
  const res = await fetch(API + 'mentors.php?action=admin_list');
  const data = await res.json();
  if (!data.success) {
    showToast(data.error || 'Failed to load mentors.', 'error');
    return;
  }
  allMentors = data.mentors || [];
  renderMentors();
}

function renderMentors() {
  const container = document.getElementById('mentorsTableBody');
  if (!allMentors.length) {
    container.innerHTML = '<div class="empty-block"><i class="fas fa-user-graduate"></i><h4>No mentors found</h4><p>Add your first mentor profile.</p></div>';
    return;
  }

  container.innerHTML = `
    <table class="admin-table">
      <thead><tr>
        <th>Mentor</th>
        <th>Role</th>
        <th>Expertise</th>
        <th>Rating</th>
        <th>Sessions</th>
        <th>Account Link</th>
        <th>Status</th>
        <th>Actions</th>
      </tr></thead>
      <tbody>
        ${allMentors.map(m => `
          <tr>
            <td>
              <div class="user-cell">
                <div class="avatar" style="background:#B4A8E0;"><i class="fas ${escHtml(m.icon || 'fa-user')}"></i></div>
                <div>
                  <div class="u-name">${escHtml(m.name)}</div>
                  <div class="u-email">${escHtml(m.bio || '').slice(0, 70)}${(m.bio || '').length > 70 ? '...' : ''}</div>
                </div>
              </div>
            </td>
            <td>${escHtml(m.role_title || '—')}</td>
            <td>${escHtml(m.expertise || '—')}</td>
            <td><span class="eq-badge"><i class="fas fa-star" style="color:#F7B685;"></i> ${Number(m.rating || 0).toFixed(1)}</span></td>
            <td>${Number(m.sessions || 0)}</td>
            <td>${m.user_id ? `<span class="eq-badge role-user"><i class="fas fa-link"></i> Linked #${m.user_id}</span>` : '<span class="text-eq-muted small">Not linked</span>'}</td>
            <td>${Number(m.is_available) ? '<span class="eq-badge status-resolved">Available</span>' : '<span class="eq-badge status-open">Unavailable</span>'}</td>
            <td>
              <div class="d-flex gap-1">
                <button class="btn btn-eq-ghost btn-icon" onclick="editMentor(${m.id})" title="Edit mentor"><i class="fas fa-edit"></i></button>
                <button class="btn btn-eq-danger btn-icon" onclick="deleteMentor(${m.id})" title="Delete mentor"><i class="fas fa-trash"></i></button>
              </div>
            </td>
          </tr>
        `).join('')}
      </tbody>
    </table>
  `;
}

function openMentorForm() {
  document.getElementById('mentorFormTitle').textContent = 'Add Mentor';
  document.getElementById('editMentorId').value = '';
  document.getElementById('editMentorName').value = '';
  document.getElementById('editMentorRole').value = '';
  document.getElementById('editMentorExpertise').value = '';
  document.getElementById('editMentorEmail').value = '';
  document.getElementById('editMentorRating').value = '4.8';
  document.getElementById('editMentorSessions').value = '0';
  document.getElementById('editMentorIcon').value = 'fa-user';
  document.getElementById('editMentorAvailable').value = '1';
  document.getElementById('editMentorBio').value = '';
  document.getElementById('mentorEditForm').style.display = 'block';
  document.getElementById('mentorEditForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeMentorForm() {
  document.getElementById('mentorEditForm').style.display = 'none';
}

function editMentor(id) {
  const m = allMentors.find(x => Number(x.id) === Number(id));
  if (!m) return;
  document.getElementById('mentorFormTitle').textContent = 'Edit Mentor';
  document.getElementById('editMentorId').value = m.id;
  document.getElementById('editMentorName').value = m.name || '';
  document.getElementById('editMentorRole').value = m.role_title || '';
  document.getElementById('editMentorExpertise').value = m.expertise || '';
  document.getElementById('editMentorEmail').value = '';
  document.getElementById('editMentorRating').value = Number(m.rating || 0).toFixed(1);
  document.getElementById('editMentorSessions').value = Number(m.sessions || 0);
  document.getElementById('editMentorIcon').value = m.icon || 'fa-user';
  document.getElementById('editMentorAvailable').value = Number(m.is_available) ? '1' : '0';
  document.getElementById('editMentorBio').value = m.bio || '';
  document.getElementById('mentorEditForm').style.display = 'block';
  document.getElementById('mentorEditForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function saveMentor() {
  const id = Number(document.getElementById('editMentorId').value || 0);
  const payload = {
    action: id ? 'update' : 'create',
    id: id || undefined,
    name: document.getElementById('editMentorName').value.trim(),
    role_title: document.getElementById('editMentorRole').value.trim(),
    expertise: document.getElementById('editMentorExpertise').value.trim(),
    mentor_email: document.getElementById('editMentorEmail').value.trim(),
    rating: document.getElementById('editMentorRating').value,
    sessions: document.getElementById('editMentorSessions').value,
    icon: document.getElementById('editMentorIcon').value.trim(),
    is_available: Number(document.getElementById('editMentorAvailable').value),
    bio: document.getElementById('editMentorBio').value.trim(),
  };

  const res = await fetch(API + 'mentors.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  showToast(data.message || data.error, data.success ? 'success' : 'error');
  if (data.success) {
    closeMentorForm();
    loadMentors();
  }
}

async function deleteMentor(id) {
  if (!confirm('Delete this mentor profile? This will also remove related mentorship requests.')) return;
  const res = await fetch(API + 'mentors.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'delete', id }),
  });
  const data = await res.json();
  showToast(data.message || data.error, data.success ? 'success' : 'error');
  if (data.success) loadMentors();
}

// ============================================================
// LEADERSHIP
// ============================================================
async function loadLeadership() {
  const res = await fetch(API + 'leadership.php');
  const data = await res.json();
  if (!data.success) return;
  const rows = data.data;
  const container = document.getElementById('leadershipTableBody');

  if (!rows.length) {
    container.innerHTML = '<div class="empty-block"><i class="fas fa-rainbow"></i><h4>No data yet</h4><p>Click "Add Organization" to get started.</p></div>';
    return;
  }

  const colors = ['#ED8E89','#9BD6D9','#F3EBA5','#F7B685','#B4A8E0','#94C691'];
  const fields = ['women_pct','men_pct','nonbinary_pct','transgender_pct','gay_pct','lesbian_pct'];
  const labels = ['Women','Men','NB','Trans','Gay','Lesbian'];

  container.innerHTML = `
    <div class="leadership-table-wrapper">
      <table class="leadership-table">
        <thead><tr>
          <th>Organization</th>
          ${labels.map((l,i)=>`<th><span class="gender-dot" style="background:${colors[i]};"></span>${l}</th>`).join('')}
          <th>Leaders</th>
          <th>Actions</th>
        </tr></thead>
        <tbody>${rows.map(r => `
          <tr>
            <td><strong>${escHtml(r.org_name)}</strong></td>
            ${fields.map((f,i)=>`<td><span class="pct-pill" style="background:${colors[i]};">${parseFloat(r[f]).toFixed(1)}%</span></td>`).join('')}
            <td><strong>${Number(r.total_leaders).toLocaleString()}</strong></td>
            <td>
              <div class="d-flex gap-1">
                <button class="btn btn-eq-ghost btn-icon" onclick="editOrg(${r.id},'${escHtml(r.org_name).replace(/'/g,"\\'")}',${r.women_pct},${r.men_pct},${r.nonbinary_pct},${r.transgender_pct},${r.gay_pct},${r.lesbian_pct},${r.total_leaders})"><i class="fas fa-edit"></i></button>
                <button class="btn btn-eq-danger btn-icon" onclick="deleteOrg(${r.id})"><i class="fas fa-trash"></i></button>
              </div>
            </td>
          </tr>`).join('')}
        </tbody>
      </table>
    </div>`;
}

function openOrgForm() {
  document.getElementById('orgFormTitle').textContent = 'Add Organization';
  ['editOrgId','editOrgName','editWomen','editMen','editNonbinary','editTrans','editGay','editLesbian','editTotalLeaders'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('orgEditForm').style.display = 'block';
  document.getElementById('orgEditForm').scrollIntoView({behavior:'smooth', block:'start'});
}

function editOrg(id, name, w, m, nb, t, g, l, total) {
  document.getElementById('orgFormTitle').textContent = 'Edit Organization';
  document.getElementById('editOrgId').value = id;
  document.getElementById('editOrgName').value = name;
  document.getElementById('editWomen').value = w;
  document.getElementById('editMen').value = m;
  document.getElementById('editNonbinary').value = nb;
  document.getElementById('editTrans').value = t;
  document.getElementById('editGay').value = g;
  document.getElementById('editLesbian').value = l;
  document.getElementById('editTotalLeaders').value = total;
  document.getElementById('orgEditForm').style.display = 'block';
  document.getElementById('orgEditForm').scrollIntoView({behavior:'smooth', block:'start'});
}

async function saveOrg() {
  const id = document.getElementById('editOrgId').value;
  const body = {
    action: id ? 'update' : 'create',
    id: id ? parseInt(id) : undefined,
    org_name: document.getElementById('editOrgName').value,
    women_pct: document.getElementById('editWomen').value,
    men_pct: document.getElementById('editMen').value,
    nonbinary_pct: document.getElementById('editNonbinary').value,
    transgender_pct: document.getElementById('editTrans').value,
    gay_pct: document.getElementById('editGay').value,
    lesbian_pct: document.getElementById('editLesbian').value,
    total_leaders: document.getElementById('editTotalLeaders').value,
  };
  const res = await fetch(API + 'leadership.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body)});
  const data = await res.json();
  showToast(data.message || data.error, data.success ? 'success' : 'error');
  if (data.success) { document.getElementById('orgEditForm').style.display = 'none'; loadLeadership(); }
}

async function deleteOrg(id) {
  if (!confirm('Delete this organization record?')) return;
  const res = await fetch(API + 'leadership.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'delete',id})});
  const data = await res.json();
  showToast(data.message || data.error, data.success ? 'success' : 'error');
  if (data.success) loadLeadership();
}

// ============================================================
// REPORTS
// ============================================================
async function loadReports() {
  const filter = document.getElementById('reportFilter').value;
  const url = API + 'reports.php' + (filter ? `?status=${filter}` : '');
  const res = await fetch(url);
  const data = await res.json();
  if (!data.success) return;

  const reports = data.reports;
  const container = document.getElementById('reportsContent');

  if (!reports.length) {
    container.innerHTML = '<div class="empty-block"><i class="fas fa-inbox"></i><h4>No reports</h4><p>All clear!</p></div>';
    return;
  }

  container.innerHTML = reports.map(r => `
    <div class="report-card ${r.status}" id="report-${r.id}">
      <div class="r-head">
        <div class="r-type">
          <i class="fas fa-flag" style="color:${r.status==='open'?'#ED8E89':'#94C691'};"></i>
          ${escHtml(r.issue_type)}
        </div>
        <div class="r-meta">
          <span class="eq-badge status-${r.status}">${r.status}</span>
          ${r.is_anonymous ? '<span><i class="fas fa-user-secret"></i> Anonymous</span>' : `<span><i class="fas fa-user"></i> ${escHtml(r.reporter_name || 'Unknown')}</span>`}
          <span><i class="fas fa-clock"></i> ${escHtml(r.time_ago)}</span>
        </div>
      </div>
      <div class="r-desc">${escHtml(r.description)}</div>
      <div class="r-actions">
        ${r.status==='open' ? `<button class="btn btn-eq-success btn-sm" onclick="resolveReport(${r.id})"><i class="fas fa-check"></i> Mark Resolved</button>` : ''}
        <button class="btn btn-eq-danger btn-sm" onclick="deleteReport(${r.id})"><i class="fas fa-trash"></i> Delete</button>
      </div>
    </div>`).join('');
}

async function resolveReport(id) {
  const res = await fetch(API + 'reports.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'resolve',id})});
  const data = await res.json();
  showToast(data.message || data.error, data.success ? 'success' : 'error');
  if (data.success) loadReports();
}

async function deleteReport(id) {
  if (!confirm('Delete this report?')) return;
  const res = await fetch(API + 'reports.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'delete',id})});
  const data = await res.json();
  showToast(data.message || data.error, data.success ? 'success' : 'error');
  if (data.success) document.getElementById(`report-${id}`)?.remove();
}

// ============================================================
function formatDate(s) { return s ? new Date(s).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '—'; }

// Init
document.addEventListener('DOMContentLoaded', () => loadOverview());
</script>
</body>
</html>

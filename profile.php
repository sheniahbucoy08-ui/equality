<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$user = currentUser();
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
  <title>My Profile — EqualVoice</title>
  <link rel="stylesheet" href="/equalvoice/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
<div class="app-layout">
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <!-- Sidebar (same as dashboard) -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
      <div class="brand-logo" aria-hidden="true"><i class="fas fa-equals"></i></div>
      <div class="brand-name">EqualVoice<span>Leadership Platform</span></div>
    </div>
    <nav class="sidebar__nav">
      <?php if (!$isAdmin): ?>
      <span class="nav-section-label">Main</span>
      <a class="sidebar__link" href="/equalvoice/dashboard.php?view=stories"><span class="nav-icon"><i class="fas fa-book-open"></i></span> Story Wall</a>
      <a class="sidebar__link" href="/equalvoice/dashboard.php?view=learning"><span class="nav-icon"><i class="fas fa-graduation-cap"></i></span> Learning Hub</a>
      <a class="sidebar__link" href="/equalvoice/dashboard.php?view=helpdesk"><span class="nav-icon"><i class="fas fa-hands-helping"></i></span> Help Desk</a>
      <a class="sidebar__link" href="/equalvoice/dashboard.php?view=opportunities"><span class="nav-icon"><i class="fas fa-briefcase"></i></span> Opportunities</a>
      <a class="sidebar__link" href="/equalvoice/dashboard.php?view=leadership"><span class="nav-icon"><i class="fas fa-chart-pie"></i></span> Leadership Tracker</a>
      <?php endif; ?>
      <?php if ($isAdmin): ?>
      <span class="nav-section-label">Admin</span>
      <a class="sidebar__link" href="/equalvoice/admin.php"><span class="nav-icon"><i class="fas fa-shield-alt"></i></span> Admin Panel</a>
      <?php endif; ?>
      <span class="nav-section-label">Account</span>
      <a class="sidebar__link active" href="/equalvoice/profile.php"><span class="nav-icon"><i class="fas fa-user"></i></span> My Profile</a>
    </nav>
    <div class="sidebar__footer">
      <div class="sidebar-user">
        <div class="avatar avatar-sm" id="sidebarAvatar" style="background:<?= htmlspecialchars($user['avatar_color']) ?>;">
          <?= htmlspecialchars($initials($user['name'])) ?>
        </div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
        </div>
        <a href="/equalvoice/logout.php"><i class="fas fa-sign-out-alt logout-icon" title="Logout"></i></a>
      </div>
    </div>
    <div class="sidebar-pride-bar"></div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('active');"><span></span><span></span><span></span></button>
        <div>
          <div class="page-title">My Profile</div>
          <div class="page-subtitle">Manage your account and preferences</div>
        </div>
      </div>
      <div class="topbar-right">
        <?php if ($isAdmin): ?>
          <a href="/equalvoice/admin.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-left"></i> Admin Panel</a>
        <?php else: ?>
          <a href="/equalvoice/dashboard.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <?php endif; ?>
      </div>
    </header>

    <main class="dashboard-content">
      <div class="profile-page" id="profilePage">
        <div class="page-loading"><div class="spinner"></div><span>Loading profile...</span></div>
      </div>
    </main>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<!-- Color picker modal -->
<div class="modal-overlay" id="colorModal">
  <div class="modal" style="max-width:400px;">
    <div class="modal__header">
      <h3><i class="fas fa-palette" style="color:var(--accent-primary);"></i> &nbsp; Choose Avatar Color</h3>
      <span class="modal__close" onclick="closeColorModal()"><i class="fas fa-times"></i></span>
    </div>
    <div class="modal__body">
      <p style="font-size:.875rem;color:var(--text-muted);margin-bottom:1rem;">Select a color for your avatar initials:</p>
      <div class="color-picker-grid" id="colorPickerGrid">
        <?php
        $colors = ['#ED8E89','#F7B685','#F3EBA5','#94C691','#9BD6D9','#B4A8E0','#9C8FCC','#7BB87F','#D67670','#E89B65','#7AC0C4','#4A3D7A'];
        foreach ($colors as $c):
        ?>
        <div class="color-option <?= $c === $user['avatar_color'] ? 'selected' : '' ?>"
             style="background:<?= $c ?>;"
             data-color="<?= $c ?>"
             onclick="selectColor('<?= $c ?>')"></div>
        <?php endforeach; ?>
      </div>
      <div style="display:flex;align-items:center;gap:1rem;margin-top:1.5rem;">
        <div id="colorPreviewAvatar" class="avatar avatar-lg" style="background:<?= htmlspecialchars($user['avatar_color']) ?>;">
          <?= htmlspecialchars($initials($user['name'])) ?>
        </div>
        <div>
          <div style="font-size:.875rem;font-weight:700;">Preview</div>
          <div style="font-size:.75rem;color:var(--text-muted);" id="colorPreviewHex"><?= htmlspecialchars($user['avatar_color']) ?></div>
        </div>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn-ghost" onclick="closeColorModal()">Cancel</button>
      <button class="btn btn-primary" onclick="saveColor()"><i class="fas fa-check"></i> Save Color</button>
    </div>
  </div>
</div>

<script>
const BASE = '/equalvoice/api/';
let selectedColor = '<?= htmlspecialchars($user['avatar_color']) ?>';

function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('active');
}

function showToast(message, type = 'info') {
  const c = document.getElementById('toastContainer');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  const icons = {success:'check-circle',error:'exclamation-circle',warning:'exclamation-triangle',info:'info-circle'};
  t.innerHTML = `<i class="fas fa-${icons[type]||'info-circle'}"></i> ${message} <span class="toast__close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></span>`;
  c.appendChild(t);
  setTimeout(() => t.remove(), 4500);
}

async function loadProfile() {
  try {
    const res = await fetch(BASE + 'profile.php');
    const data = await res.json();
    if (!data.success) { showToast(data.error, 'error'); return; }
    renderProfile(data.user);
  } catch(e) { showToast('Failed to load profile.', 'error'); }
}

function renderProfile(u) {
  const initials = u.name.split(' ').slice(0,2).map(w=>w[0]).join('').toUpperCase();
  const genderColors = {woman:'#ED8E89',man:'#9BD6D9','non-binary':'#F3EBA5',transgender:'#F7B685',gay:'#B4A8E0',lesbian:'#94C691'};
  const gColor = Object.entries(genderColors).find(([k])=>u.gender_identity&&u.gender_identity.toLowerCase().includes(k))?.[1]||u.avatar_color;

  document.getElementById('profilePage').innerHTML = `
    <!-- Profile Hero -->
    <div class="profile-hero">
      <div class="profile-avatar-wrapper">
        <div class="profile-avatar" style="background:${u.avatar_color};">${initials}</div>
        <button class="avatar-edit-btn" onclick="openColorModal()"><i class="fas fa-pencil-alt"></i></button>
      </div>
      <div class="profile-info">
        <h2>${escHtml(u.name)}</h2>
        <div class="profile-email"><i class="fas fa-envelope"></i> ${escHtml(u.email)}</div>
        <div class="profile-badges">
          <span class="profile-badge badge-${u.role}"><i class="fas fa-${u.role==='admin'?'shield-alt':'user'}"></i> ${u.role.charAt(0).toUpperCase()+u.role.slice(1)}</span>
          ${u.gender_identity ? `<span class="profile-badge" style="background:rgba(${hexToRgb(gColor)},.2);border-color:${gColor};color:${gColor};"><i class="fas fa-rainbow"></i> ${escHtml(u.gender_identity)}</span>` : ''}
          ${u.department ? `<span class="profile-badge"><i class="fas fa-building"></i> ${escHtml(u.department)}</span>` : ''}
          <span class="profile-badge"><i class="fas fa-calendar"></i> Member since ${u.member_since}</span>
        </div>
      </div>
      <div class="profile-actions">
        <button class="btn" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.4);" onclick="document.querySelector('[data-tab=\"edit\"]').click()">
          <i class="fas fa-edit"></i> Edit Profile
        </button>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="profile-stats-row">
      <div class="profile-stat">
        <div class="pstat-icon"><i class="fas fa-book-open" style="color:#ED8E89;"></i></div>
        <div class="pstat-value">${u.stories_shared}</div>
        <div class="pstat-label">Stories Shared</div>
      </div>
      <div class="profile-stat">
        <div class="pstat-icon"><i class="fas fa-graduation-cap" style="color:#94C691;"></i></div>
        <div class="pstat-value">${u.lessons_completed}</div>
        <div class="pstat-label">Lessons Completed</div>
      </div>
      <div class="profile-stat">
        <div class="pstat-icon"><i class="fas fa-handshake" style="color:#F7B685;"></i></div>
        <div class="pstat-value">${u.mentorship_requests}</div>
        <div class="pstat-label">Mentorship Requests</div>
      </div>
    </div>

    <!-- Profile Form Card -->
    <div class="profile-form-card">
      <div class="card-tabs">
        <button class="card-tab active" data-tab="edit" onclick="switchProfileTab('edit')"><i class="fas fa-user-edit"></i> Personal Info</button>
        <button class="card-tab" data-tab="security" onclick="switchProfileTab('security')"><i class="fas fa-lock"></i> Security</button>
        <button class="card-tab" data-tab="activity" onclick="switchProfileTab('activity')"><i class="fas fa-history"></i> Activity</button>
      </div>

      <!-- Personal Info Tab -->
      <div class="card-tab-content active" id="tab-edit">
        <div class="grid-2">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" id="profileName" value="${escHtml(u.name)}" placeholder="Your name">
          </div>
          <div class="form-group">
            <label>Department</label>
            <input type="text" id="profileDept" value="${escHtml(u.department||'')}" placeholder="Your department">
          </div>
        </div>
        <div class="form-group">
          <label>Gender Identity <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <div class="gender-select-wrap">
            <select id="profileGender" onchange="updateGenderPreview()">
              <option value="">Prefer not to say</option>
              ${['Woman','Man','Non-Binary','Transgender Woman','Transgender Man','Gay Man','Lesbian','Bisexual','Queer','Other'].map(g=>`<option value="${g}" ${u.gender_identity===g?'selected':''}>${g}</option>`).join('')}
            </select>
            <div class="gender-preview" id="genderPreview" style="background:${gColor};"></div>
          </div>
        </div>
        <div class="form-group">
          <label>Bio</label>
          <textarea id="profileBio" placeholder="Tell your community about yourself...">${escHtml(u.bio||'')}</textarea>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Interests</label>
            <input type="text" id="profileInterests" value="${escHtml(u.interests||'')}" placeholder="e.g. Technology, DEI, Leadership">
          </div>
          <div class="form-group">
            <label>Career Goals</label>
            <input type="text" id="profileGoals" value="${escHtml(u.goals||'')}" placeholder="e.g. Become a team lead">
          </div>
        </div>
        <button class="btn btn-primary" onclick="saveProfileInfo()"><i class="fas fa-save"></i> Save Changes</button>
      </div>

      <!-- Security Tab -->
      <div class="card-tab-content" id="tab-security">
        <h4 style="margin-bottom:1rem;">Change Password</h4>
        <div class="form-group">
          <label>Current Password</label>
          <input type="password" id="currentPass" placeholder="Your current password">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>New Password</label>
            <input type="password" id="newPass" placeholder="Min 6 characters">
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" id="confirmPass" placeholder="Repeat new password">
          </div>
        </div>
        <button class="btn btn-primary" onclick="changePassword()"><i class="fas fa-lock"></i> Change Password</button>
      </div>

      <!-- Activity Tab -->
      <div class="card-tab-content" id="tab-activity">
        <h4 style="margin-bottom:1rem;">Recent Activity</h4>
        ${u.activity.length ? `
          <div class="activity-timeline">
            ${u.activity.map(a=>`
              <div class="timeline-item">
                <div class="timeline-icon" style="background:${a.color};"><i class="fas ${a.icon}"></i></div>
                <div class="timeline-text">
                  <div class="tl-title">${escHtml(a.title)}</div>
                  <div class="tl-time"><i class="fas fa-clock"></i> ${a.time}</div>
                </div>
              </div>`).join('')}
          </div>` : `<div class="empty-state"><div class="empty-icon"><i class="fas fa-history"></i></div><h3>No recent activity</h3><p>Start sharing stories and completing lessons to see your activity here.</p></div>`}
      </div>
    </div>
  `;
}

function switchProfileTab(tab) {
  document.querySelectorAll('.card-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
  document.querySelectorAll('.card-tab-content').forEach(c => c.classList.toggle('active', c.id === `tab-${tab}`));
}

const genderColorMap = {woman:'#ED8E89',man:'#9BD6D9','non-binary':'#F3EBA5',transgender:'#F7B685',gay:'#B4A8E0',lesbian:'#94C691',bisexual:'#d63bca',queer:'#f7b731'};
function updateGenderPreview() {
  const val = document.getElementById('profileGender').value.toLowerCase();
  const color = Object.entries(genderColorMap).find(([k]) => val.includes(k))?.[1] || '#B4A8E0';
  document.getElementById('genderPreview').style.background = color;
}

async function saveProfileInfo() {
  const body = {
    action:'update_info',
    name: document.getElementById('profileName').value,
    bio: document.getElementById('profileBio').value,
    department: document.getElementById('profileDept').value,
    interests: document.getElementById('profileInterests').value,
    goals: document.getElementById('profileGoals').value,
    gender_identity: document.getElementById('profileGender').value,
  };
  const res = await fetch(BASE+'profile.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
  const data = await res.json();
  showToast(data.message || data.error, data.success ? 'success' : 'error');
}

async function changePassword() {
  const body = {
    action:'change_password',
    current_password: document.getElementById('currentPass').value,
    new_password: document.getElementById('newPass').value,
    confirm_password: document.getElementById('confirmPass').value,
  };
  const res = await fetch(BASE+'profile.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
  const data = await res.json();
  showToast(data.message || data.error, data.success ? 'success' : 'error');
  if (data.success) { document.getElementById('currentPass').value=''; document.getElementById('newPass').value=''; document.getElementById('confirmPass').value=''; }
}

function openColorModal() { document.getElementById('colorModal').classList.add('active'); }
function closeColorModal() { document.getElementById('colorModal').classList.remove('active'); }

function selectColor(color) {
  selectedColor = color;
  document.querySelectorAll('.color-option').forEach(el => el.classList.toggle('selected', el.dataset.color === color));
  document.getElementById('colorPreviewAvatar').style.background = color;
  document.getElementById('colorPreviewHex').textContent = color;
}

async function saveColor() {
  const res = await fetch(BASE+'profile.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'update_color',avatar_color:selectedColor})});
  const data = await res.json();
  if (data.success) {
    closeColorModal();
    showToast('Avatar color updated!', 'success');
    document.querySelector('.profile-avatar').style.background = selectedColor;
    document.getElementById('sidebarAvatar').style.background = selectedColor;
  } else { showToast(data.error, 'error'); }
}

function hexToRgb(hex) {
  const r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
  return `${r},${g},${b}`;
}

function escHtml(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('DOMContentLoaded', loadProfile);
</script>
</body>
</html>

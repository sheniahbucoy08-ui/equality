// ============================================================
// EqualVoice — Dashboard Main JS
// ============================================================

'use strict';

let currentView = 'stories';

// ---- Utilities ---------------------------------------------
function escHtml(s) {
  return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function showToast(message, type = 'info') {
  const c = document.getElementById('toastContainer');
  if (!c) return;
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  const icons = { success: 'check-circle', error: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };
  t.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}"></i> ${escHtml(message)}
    <span class="toast__close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></span>`;
  c.appendChild(t);
  setTimeout(() => t.remove(), 4500);
}

function setLoading(msg = 'Loading...') {
  document.getElementById('dashboardContent').innerHTML =
    `<div class="page-loading"><div class="spinner"></div><span>${msg}</span></div>`;
}

function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

// Close modal on overlay click
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('active');
  }
});

// Sidebar helpers
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('active');
}

function confirmLogout() {
  if (confirm('Are you sure you want to sign out?')) {
    location.href = '/equalvoice/logout.php';
  }
}

// ---- Navigation --------------------------------------------
function loadView(view) {
  currentView = view;

  // Update sidebar active state
  document.querySelectorAll('.sidebar__link[data-view]').forEach(el =>
    el.classList.toggle('active', el.dataset.view === view)
  );

  // Update topbar title
  const info = PAGE_INFO[view] || { title: view, subtitle: '' };
  document.getElementById('pageTitle').textContent    = info.title;
  document.getElementById('pageSubtitle').textContent = info.subtitle;

  closeSidebar();

  // Dispatch to view renderer
  const views = { stories, learning, helpdesk, opportunities, leadership };
  if (views[view]) views[view]();
}

// ---- API helper -------------------------------------------
async function apiFetch(endpoint, options = {}) {
  try {
    const res = await fetch(BASE + endpoint, options);
    return await res.json();
  } catch (e) {
    return { success: false, error: 'Network error. Is XAMPP running?' };
  }
}

// ============================================================
// STORY WALL
// ============================================================
async function stories() {
  setLoading('Loading stories...');
  const data = await apiFetch('stories.php');

  if (!data.success) {
    document.getElementById('dashboardContent').innerHTML =
      `<div class="empty-state"><div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div><h3>Error loading stories</h3><p>${escHtml(data.error || 'Unknown error')}</p></div>`;
    return;
  }

  const totalStories = data.stories.length;
  const anonCount    = data.stories.filter(s => s.is_anonymous).length;
  const totalLikes   = data.stories.reduce((sum, s) => sum + (s.like_count || 0), 0);

  document.getElementById('dashboardContent').innerHTML = `
    <div class="story-wall fade-in">
      <div class="section-header">
        <div class="section-title">
          <div class="section-icon" style="background:linear-gradient(135deg,#ED8E89,#F7B685);">
            <i class="fas fa-book-open"></i>
          </div>
          Story Wall
        </div>
        <button class="btn btn-primary" onclick="openModal('storyModal')">
          <i class="fas fa-plus"></i> Share Your Story
        </button>
      </div>

      <!-- Story stats banner -->
      <div class="grid-3" style="margin-bottom:1.5rem;">
        <div class="stat-card">
          <div class="stat-icon" style="background:linear-gradient(135deg,#ED8E89,#D67670);"><i class="fas fa-book-open"></i></div>
          <div class="stat-info">
            <div class="stat-value">${totalStories}</div>
            <div class="stat-label">Total Stories</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:linear-gradient(135deg,#B4A8E0,#9C8FCC);"><i class="fas fa-user-secret"></i></div>
          <div class="stat-info">
            <div class="stat-value">${anonCount}</div>
            <div class="stat-label">Anonymous Posts</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:linear-gradient(135deg,#94C691,#7BB87F);"><i class="fas fa-heart"></i></div>
          <div class="stat-info">
            <div class="stat-value">${totalLikes}</div>
            <div class="stat-label">Total Hearts</div>
          </div>
        </div>
      </div>

      ${totalStories === 0
        ? `<div class="empty-state">
             <div class="empty-icon"><i class="fas fa-book-open"></i></div>
             <h3>No stories yet</h3>
             <p>Be the first to share your experience and inspire others!</p>
             <button class="btn btn-primary mt-4" onclick="openModal('storyModal')">
               <i class="fas fa-pen"></i> Share Your Story
             </button>
           </div>`
        : `<div class="story-grid">${data.stories.map(renderStoryCard).join('')}</div>`
      }
    </div>`;
}

function getInitials(name) {
  if (!name || typeof name !== 'string') return '?';
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return '?';
  return parts.slice(0, 2).map(w => (w[0] || '').toUpperCase()).join('') || '?';
}

function renderStoryCard(s) {
  // Defensive: ensure values exist
  const isAnon     = !!s.is_anonymous;
  const authorName = s.author_name || 'Community Member';
  const authorColor= s.author_color || '#B4A8E0';
  const initials   = isAnon ? '?' : getInitials(authorName);
  const likeCount  = s.like_count || 0;
  const liked      = !!s.liked_by_me;

  return `
    <div class="story-card" id="story-${s.id}">
      <div class="story-header">
        <div class="avatar" style="background:${escHtml(authorColor)};">${escHtml(initials)}</div>
        <div class="story-author">
          <div class="author-name">${escHtml(authorName)}</div>
          <div class="author-time"><i class="fas fa-clock"></i> ${escHtml(s.time_ago || 'recently')}</div>
        </div>
        ${isAnon ? '<span class="badge badge-info" title="Posted anonymously"><i class="fas fa-user-secret"></i> Anon</span>' : ''}
      </div>
      <div class="story-content">${escHtml(s.content || '')}</div>
      <div class="story-actions">
        <button class="like-btn ${liked ? 'liked' : ''}" id="like-btn-${s.id}" onclick="toggleLike(${s.id})">
          <i class="fas fa-heart"></i> <span id="like-count-${s.id}">${likeCount}</span>
        </button>
        <span style="font-size:.75rem;color:var(--text-muted);margin-left:auto;">
          <i class="fas fa-rainbow" style="color:#B4A8E0;"></i> Community
        </span>
      </div>
    </div>`;
}

async function toggleLike(storyId) {
  const data = await apiFetch('stories.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'like', story_id: storyId }),
  });
  if (data.success) {
    const btn = document.getElementById(`like-btn-${storyId}`);
    btn.classList.toggle('liked', data.liked);
    document.getElementById(`like-count-${storyId}`).textContent = data.count;
  } else if (data.error?.includes('session') || data.error?.includes('login')) {
    showToast('Please log in to like stories.', 'warning');
  }
}

async function submitStory() {
  const content  = document.getElementById('storyContent').value.trim();
  const isAnon   = document.getElementById('storyAnonymous').checked;
  if (content.length < 10) { showToast('Story must be at least 10 characters.', 'warning'); return; }

  const data = await apiFetch('stories.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ action: 'create', content, is_anonymous: isAnon }),
  });

  if (data.success) {
    closeModal('storyModal');
    document.getElementById('storyContent').value = '';
    document.getElementById('storyAnonymous').checked = false;
    showToast('Story shared! Thank you for your voice.', 'success');
    stories(); // Reload
  } else {
    showToast(data.error, 'error');
  }
}

// ============================================================
// LEARNING HUB
// ============================================================
async function learning() {
  setLoading('Loading lessons...');
  const data = await apiFetch('lessons.php');

  if (!data.success) {
    document.getElementById('dashboardContent').innerHTML = '<div class="empty-state"><div class="empty-icon"><i class="fas fa-book"></i></div><h3>Failed to load lessons</h3></div>';
    return;
  }

  const levelColors = { Beginner: '#94C691', Intermediate: '#B4A8E0', Advanced: '#ED8E89' };

  document.getElementById('dashboardContent').innerHTML = `
    <div class="learning-hub fade-in">
      <div class="section-header">
        <div class="section-title">
          <div class="section-icon"><i class="fas fa-graduation-cap"></i></div>
          Learning Hub
        </div>
        <div style="display:flex;align-items:center;gap:1rem;">
          <span style="font-size:.875rem;color:var(--text-muted);">${data.completed} / ${data.total} completed</span>
          <div class="progress-bar" style="width:120px;">
            <div class="progress-fill" style="width:${data.progress_pct}%;"></div>
          </div>
          <span style="font-size:.875rem;font-weight:700;color:var(--accent-primary);">${data.progress_pct}%</span>
        </div>
      </div>

      <div class="grid-2">
        <div>
          <h4 style="margin-bottom:1rem;color:var(--text-secondary);font-size:.875rem;text-transform:uppercase;letter-spacing:.05em;">Lessons</h4>
          <div style="display:flex;flex-direction:column;gap:.75rem;">
            ${data.lessons.map((l, i) => `
              <div class="lesson-card ${l.completed ? 'completed' : ''}">
                <div class="lesson-number">${l.completed ? '<i class="fas fa-check" style="font-size:1.1rem;"></i>' : i + 1}</div>
                <div class="lesson-info">
                  <h4>${escHtml(l.title)}</h4>
                  <p>${escHtml(l.content.slice(0, 120))}${l.content.length > 120 ? '...' : ''}</p>
                  <div class="lesson-meta">
                    <span class="meta-tag"><i class="fas fa-clock"></i> ${escHtml(l.duration)}</span>
                    <span class="meta-tag" style="color:${levelColors[l.level]||'#B4A8E0'};"><i class="fas fa-signal"></i> ${escHtml(l.level)}</span>
                    <span class="meta-tag"><i class="fas fa-tag"></i> ${escHtml(l.category)}</span>
                  </div>
                </div>
                <button class="btn btn-sm ${l.completed ? 'btn-success' : 'btn-primary'}" onclick="openLesson(${l.id},'${escHtml(l.title)}',\`${escHtml(l.content)}\`,${l.completed})">
                  <i class="fas fa-${l.completed ? 'eye' : 'play'}"></i> ${l.completed ? 'Review' : 'Start'}
                </button>
              </div>`).join('')}
          </div>
        </div>
        <div>
          <h4 style="margin-bottom:1rem;color:var(--text-secondary);font-size:.875rem;text-transform:uppercase;letter-spacing:.05em;">
            <i class="fas fa-file-pdf" style="color:#ED8E89;"></i> &nbsp;Resources
          </h4>
          <p style="font-size:.75rem;color:var(--text-muted);margin-bottom:1rem;">Click <strong>View</strong> to read in your browser or <strong>Download</strong> to save the PDF.</p>
          <div style="display:flex;flex-direction:column;gap:.75rem;">
            ${[
              { slug: 'equal-pay-act',            title: 'The Equal Pay Act',           summary: 'Prohibits wage discrimination based on gender.',            icon: 'fa-balance-scale', color: '#B4A8E0' },
              { slug: 'anti-discrimination-laws', title: 'Anti-Discrimination Laws',    summary: 'Protections against workplace gender discrimination.',      icon: 'fa-gavel',         color: '#9C8FCC' },
              { slug: 'inclusive-hiring-guide',   title: 'Inclusive Hiring Guide',      summary: 'Best practices for equitable recruitment processes.',       icon: 'fa-user-check',    color: '#94C691' },
              { slug: 'gender-diversity-toolkit', title: 'Gender Diversity Toolkit',    summary: 'Resources for building inclusive workplace cultures.',      icon: 'fa-toolbox',       color: '#F7B685' },
            ].map(r => `
              <div class="card pdf-resource-card" style="padding:1rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                <div class="stat-icon" style="background:${r.color};width:44px;height:44px;flex-shrink:0;"><i class="fas ${r.icon}"></i></div>
                <div style="flex:1;min-width:160px;">
                  <div style="font-weight:700;font-size:.875rem;display:flex;align-items:center;gap:.5rem;">
                    ${escHtml(r.title)}
                    <span class="badge badge-info" style="font-size:.65rem;"><i class="fas fa-file-pdf"></i> PDF</span>
                  </div>
                  <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;">${escHtml(r.summary)}</div>
                </div>
                <div style="display:flex;gap:.4rem;flex-shrink:0;">
                  <a class="btn btn-secondary btn-sm" href="/equalvoice/pdfs/download.php?file=${r.slug}" target="_blank" rel="noopener" title="Open PDF in new tab">
                    <i class="fas fa-eye"></i> View
                  </a>
                  <a class="btn btn-primary btn-sm" href="/equalvoice/pdfs/download.php?file=${r.slug}&mode=download" title="Download PDF">
                    <i class="fas fa-download"></i> Download
                  </a>
                </div>
              </div>`).join('')}
          </div>
        </div>
      </div>
    </div>`;
}

function openLesson(id, title, content, completed) {
  currentLessonId = id;
  document.getElementById('lessonModalTitle').innerHTML =
    `<i class="fas fa-graduation-cap" style="color:#B4A8E0;"></i> &nbsp; ${escHtml(title)}`;
  document.getElementById('lessonModalBody').innerHTML =
    `<p style="line-height:1.9;color:var(--text-primary);font-size:.95rem;">${escHtml(content)}</p>
     <div style="margin-top:1.5rem;padding:1rem;background:var(--bg-elevated);border-radius:12px;font-size:.875rem;">
       <i class="fas fa-lightbulb" style="color:#F7B685;"></i> <strong>Key Takeaway:</strong>
       Apply what you've learned by discussing it with a colleague or mentor today.
     </div>`;

  const btn = document.getElementById('lessonCompleteBtn');
  btn.style.display = completed ? 'none' : 'inline-flex';
  openModal('lessonModal');
}

async function markLessonComplete() {
  if (!currentLessonId) return;
  const data = await apiFetch('lessons.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'complete', lesson_id: currentLessonId }),
  });
  if (data.success) {
    closeModal('lessonModal');
    showToast('Lesson completed! Great work!', 'success');
    learning(); // Reload
  } else {
    showToast(data.error, 'error');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('lessonCompleteBtn');
  if (btn) btn.addEventListener('click', markLessonComplete);
});

// ============================================================
// HELP DESK
// ============================================================
async function helpdesk() {
  setLoading('Loading resources...');
  const data = await apiFetch('resources.php');

  document.getElementById('dashboardContent').innerHTML = `
    <div class="help-desk fade-in">
      <div class="section-header">
        <div class="section-title">
          <div class="section-icon"><i class="fas fa-hands-helping"></i></div>
          Help Desk
        </div>
      </div>

      <!-- Report Form -->
      <div class="report-form-card">
        <h3 style="margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem;">
          <i class="fas fa-flag" style="color:var(--accent-primary);"></i> Report an Issue
        </h3>
        <p style="font-size:.875rem;color:var(--text-muted);margin-bottom:1.5rem;">
          Report workplace discrimination, harassment, or any concern. Anonymous reporting is available.
        </p>
        <div class="grid-2">
          <div class="form-group">
            <label>Issue Type</label>
            <select id="reportType">
              <option value="">Select issue type...</option>
              <option>Gender Discrimination</option>
              <option>Harassment</option>
              <option>Pay Gap / Equal Pay</option>
              <option>Promotion Bias</option>
              <option>Hostile Work Environment</option>
              <option>LGBTQ+ Discrimination</option>
              <option>Retaliation</option>
              <option>Other</option>
            </select>
          </div>
          <div style="display:flex;align-items:flex-end;padding-bottom:.25rem;">
            <div class="checkbox-group">
              <input type="checkbox" id="reportAnon">
              <label for="reportAnon"><i class="fas fa-user-secret"></i> Submit anonymously</label>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea id="reportDesc" rows="4" placeholder="Describe the situation in detail. Your report is confidential and will be reviewed by our support team. (min 20 characters)"></textarea>
        </div>
        <button class="btn btn-primary" onclick="submitReport()">
          <i class="fas fa-paper-plane"></i> Submit Report
        </button>
      </div>

      <!-- Resources -->
      ${data.success ? `
        <h3 style="margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;">
          <i class="fas fa-life-ring" style="color:#94C691;"></i> Support Resources
        </h3>
        <div class="grid-2">
          ${data.resources.map(r => `
            <div class="resource-card">
              <div class="resource-type-icon" style="background:${r.color};">
                <i class="fas ${r.icon}"></i>
              </div>
              <div class="resource-info">
                <h4>${escHtml(r.title)}</h4>
                <p>${escHtml(r.description || '')}</p>
                <div class="resource-contact">
                  ${r.contact_phone ? `<span><i class="fas fa-phone"></i> ${escHtml(r.contact_phone)}</span>` : ''}
                  ${r.contact_address ? `<span><i class="fas fa-map-marker-alt"></i> ${escHtml(r.contact_address)}</span>` : ''}
                  ${r.hours ? `<span><i class="fas fa-clock"></i> ${escHtml(r.hours)}</span>` : ''}
                </div>
              </div>
            </div>`).join('')}
        </div>` : '<div class="empty-state"><div class="empty-icon"><i class="fas fa-exclamation"></i></div><h3>Resources unavailable</h3></div>'}
    </div>`;
}

async function submitReport() {
  const issue_type  = document.getElementById('reportType').value;
  const description = document.getElementById('reportDesc').value.trim();
  const is_anonymous = document.getElementById('reportAnon').checked;

  if (!issue_type)         { showToast('Please select an issue type.', 'warning'); return; }
  if (description.length < 20) { showToast('Please describe the issue in at least 20 characters.', 'warning'); return; }

  const data = await apiFetch('reports.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'create', issue_type, description, is_anonymous }),
  });

  if (data.success) {
    showToast(data.message, 'success');
    document.getElementById('reportType').value = '';
    document.getElementById('reportDesc').value = '';
    document.getElementById('reportAnon').checked = false;
  } else {
    showToast(data.error, 'error');
  }
}

// ============================================================
// OPPORTUNITIES (Mentors + Job Fairness)
// ============================================================
async function opportunities() {
  setLoading('Loading opportunities...');
  const [mentorData, companyData, myReqData, incomingReqData] = await Promise.all([
    apiFetch('mentors.php'),
    apiFetch('companies.php'),
    apiFetch('mentors.php?action=my_requests'),
    apiFetch('mentors.php?action=incoming_requests'),
  ]);

  const myRequests = myReqData.success ? (myReqData.requests || []) : [];
  const incomingRequests = incomingReqData.success ? (incomingReqData.requests || []) : [];
  const canRespondMentorRequests = CURRENT_USER_ROLE === 'admin' || incomingRequests.length > 0;

  document.getElementById('dashboardContent').innerHTML = `
    <div class="opportunity-finder fade-in">
      <div class="section-header">
        <div class="section-title">
          <div class="section-icon"><i class="fas fa-briefcase"></i></div>
          Opportunities
        </div>
      </div>

      <!-- Mentors -->
      <h3 style="margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;">
        <i class="fas fa-user-graduate" style="color:#F7B685;"></i> Find a Mentor
      </h3>
      <div class="grid-3" style="margin-bottom:2rem;">
        ${(mentorData.mentors || []).map(m => `
          <div class="mentor-card">
            <div class="mentor-avatar"><i class="fas ${m.icon || 'fa-user'}"></i></div>
            <h4>${escHtml(m.name)}</h4>
            <div class="mentor-role">${escHtml(m.role_title)}</div>
            <div class="mentor-expertise">${escHtml(m.expertise)}</div>
            <div class="mentor-stats">
              <span><i class="fas fa-star" style="color:#F7B685;"></i> ${m.rating}</span>
              <span><i class="fas fa-comments"></i> ${m.sessions} sessions</span>
            </div>
            <div class="available-badge">Available now</div>
            <button class="btn btn-primary btn-sm btn-full" onclick="openMentorModal(${m.id},'${escHtml(m.name)}','${escHtml(m.role_title)}')" ${m.user_id && Number(m.user_id) === Number(CURRENT_USER_ID) ? 'disabled title="This is your mentor account"' : ''}>
              <i class="fas fa-paper-plane"></i> Request Mentorship
            </button>
          </div>`).join('')}
      </div>

      <!-- Request status for users -->
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card__header">
          <h4 style="margin:0;display:flex;align-items:center;gap:.5rem;">
            <i class="fas fa-inbox" style="color:#B4A8E0;"></i> My Mentor Requests
          </h4>
          <span style="font-size:.8rem;color:var(--text-muted);">${myRequests.length} total</span>
        </div>
        <div class="card__body">
          ${myRequests.length ? myRequests.map(r => `
            <div style="padding:.8rem 0;border-bottom:1px solid var(--border-subtle);">
              <div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;flex-wrap:wrap;">
                <div>
                  <div style="font-weight:700;font-size:.9rem;">${escHtml(r.mentor_name)}</div>
                  <div style="font-size:.75rem;color:var(--text-muted);">${escHtml(r.role_title || '')}</div>
                </div>
                <span class="badge ${r.status === 'accepted' ? 'badge-success' : r.status === 'declined' ? 'badge-error' : 'badge-warning'}">${escHtml(r.status)}</span>
              </div>
              <div style="font-size:.82rem;color:var(--text-secondary);margin-top:.5rem;">${escHtml(r.message || '')}</div>
              <div style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem;"><i class="fas fa-clock"></i> ${escHtml(r.time_ago || '')}</div>
            </div>
          `).join('') : `
            <div class="empty-state" style="padding:1rem 0;">
              <div class="empty-icon"><i class="fas fa-inbox"></i></div>
              <h3>No mentor requests yet</h3>
              <p>Send a mentorship request to get started.</p>
            </div>
          `}
        </div>
      </div>

      ${canRespondMentorRequests ? `
      <div class="card" style="margin-bottom:2rem;">
        <div class="card__header">
          <h4 style="margin:0;display:flex;align-items:center;gap:.5rem;">
            <i class="fas fa-user-check" style="color:#94C691;"></i> Incoming Mentor Requests
          </h4>
          <span style="font-size:.8rem;color:var(--text-muted);">${incomingRequests.length} total</span>
        </div>
        <div class="card__body">
          ${incomingRequests.length ? incomingRequests.map(r => `
            <div style="padding:.8rem 0;border-bottom:1px solid var(--border-subtle);">
              <div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;flex-wrap:wrap;">
                <div>
                  <div style="font-weight:700;font-size:.9rem;">${escHtml(r.requester_name || 'User')}</div>
                  <div style="font-size:.75rem;color:var(--text-muted);">for mentor: ${escHtml(r.mentor_name || '')}</div>
                </div>
                <span class="badge ${r.status === 'accepted' ? 'badge-success' : r.status === 'declined' ? 'badge-error' : 'badge-warning'}">${escHtml(r.status)}</span>
              </div>
              <div style="font-size:.82rem;color:var(--text-secondary);margin-top:.5rem;">${escHtml(r.message || '')}</div>
              <div style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem;"><i class="fas fa-clock"></i> ${escHtml(r.time_ago || '')}</div>
              ${r.status === 'pending' ? `
                <div style="display:flex;gap:.5rem;margin-top:.7rem;">
                  <button class="btn btn-success btn-sm" onclick="respondMentorRequest(${r.id},'accepted')"><i class="fas fa-check"></i> Accept</button>
                  <button class="btn btn-danger btn-sm" onclick="respondMentorRequest(${r.id},'declined')"><i class="fas fa-times"></i> Decline</button>
                </div>
              ` : ''}
            </div>
          `).join('') : `
            <div class="empty-state" style="padding:1rem 0;">
              <div class="empty-icon"><i class="fas fa-user-check"></i></div>
              <h3>No incoming requests</h3>
              <p>New mentorship requests will appear here.</p>
            </div>
          `}
        </div>
      </div>
      ` : ''}

      <!-- Job Fairness -->
      <h3 style="margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;">
        <i class="fas fa-briefcase" style="color:#F7B685;"></i> Job Fairness Tracker
      </h3>
      <div class="card">
        <div class="card__header">
          <span style="font-size:.875rem;color:var(--text-muted);">Companies ranked by fairness score</span>
          <div style="display:flex;gap:.5rem;">
            <span class="badge badge-success"><i class="fas fa-circle" style="font-size:.5rem;"></i> A ≥90</span>
            <span class="badge badge-info"><i class="fas fa-circle" style="font-size:.5rem;"></i> B ≥80</span>
            <span class="badge badge-warning"><i class="fas fa-circle" style="font-size:.5rem;"></i> C ≥70</span>
            <span class="badge badge-error"><i class="fas fa-circle" style="font-size:.5rem;"></i> D &lt;70</span>
          </div>
        </div>
        <div class="card__body" style="padding:0;">
          ${(companyData.companies || []).map(c => `
            <div class="company-card" style="border-radius:0;border:none;border-bottom:1px solid var(--border-subtle);">
              <div class="company-icon"><i class="fas ${c.icon}"></i></div>
              <div class="company-info">
                <h4>${escHtml(c.name)}</h4>
                <div style="display:flex;gap:1rem;font-size:.75rem;color:var(--text-muted);flex-wrap:wrap;">
                  <span><i class="fas fa-dollar-sign" style="color:#F7B685;"></i> Pay gap: ${c.gender_pay_gap}%</span>
                  <span><i class="fas fa-female" style="color:#ED8E89;"></i> Women in leadership: ${c.women_in_leadership}%</span>
                  <span><i class="fas fa-star" style="color:#F7B685;"></i> ${c.rating}/5.0</span>
                </div>
                <div class="progress-bar" style="width:200px;margin-top:.5rem;">
                  <div class="progress-fill" style="width:${c.fairness_score}%;background:${c.score_color};"></div>
                </div>
              </div>
              <div class="fairness-score" style="color:${c.score_color};">
                ${c.fairness_score}
                <div class="score-label">/ 100 &nbsp;<span style="background:${c.score_color};color:#fff;padding:2px 8px;border-radius:9999px;font-size:.7rem;font-weight:800;">${c.score_grade}</span></div>
              </div>
            </div>`).join('')}
        </div>
      </div>
    </div>`;
}

function openMentorModal(id, name, role) {
  currentMentorId = id;
  document.getElementById('mentorModalInfo').innerHTML =
    `<div style="display:flex;align-items:center;gap:.75rem;">
       <div class="avatar" style="background:var(--gradient-primary);"><i class="fas fa-user-tie"></i></div>
       <div><div style="font-weight:700;">${escHtml(name)}</div><div style="font-size:.75rem;color:var(--text-muted);">${escHtml(role)}</div></div>
     </div>`;
  openModal('mentorModal');
}

async function submitMentorRequest() {
  const message = document.getElementById('mentorMessage').value.trim();
  if (!currentMentorId) { showToast('Invalid mentor.', 'error'); return; }
  if (message.length < 10) { showToast('Please write a message of at least 10 characters.', 'warning'); return; }

  const data = await apiFetch('mentors.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'request', mentor_id: currentMentorId, message }),
  });

  if (data.success) {
    closeModal('mentorModal');
    document.getElementById('mentorMessage').value = '';
    showToast('Mentorship request sent! The mentor will be in touch.', 'success');
  } else {
    showToast(data.error, 'error');
  }
}

async function respondMentorRequest(requestId, status) {
  const verb = status === 'accepted' ? 'accept' : 'decline';
  if (!confirm(`Are you sure you want to ${verb} this mentorship request?`)) return;

  const data = await apiFetch('mentors.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'respond', request_id: requestId, status }),
  });

  if (data.success) {
    showToast(`Request ${status}.`, 'success');
    opportunities();
  } else {
    showToast(data.error || 'Failed to update request.', 'error');
  }
}

// ============================================================
// LEADERSHIP TRACKER
// ============================================================
let leadershipData = [];
let selectedOrgIndex = 0;

async function leadership() {
  setLoading('Loading leadership data...');
  const data = await apiFetch('leadership.php');

  if (!data.success || !data.data.length) {
    document.getElementById('dashboardContent').innerHTML =
      `<div class="empty-state"><div class="empty-icon"><i class="fas fa-chart-pie"></i></div><h3>No leadership data available</h3><p>Admin can add organizations in the Admin Panel.</p></div>`;
    return;
  }

  leadershipData = data.data;

  document.getElementById('dashboardContent').innerHTML = `
    <div class="leadership-section fade-in">
      <div class="section-header">
        <div class="section-title">
          <div class="section-icon"><i class="fas fa-chart-pie"></i></div>
          Leadership Tracker
        </div>
      </div>

      <!-- Gender Stat Cards -->
      <div class="leadership-stats-row" id="genderStatCards"></div>

      <!-- Chart + Legend -->
      <div class="leadership-grid">
        <div class="chart-panel">
          <div class="chart-title"><i class="fas fa-chart-donut" style="color:var(--accent-primary);"></i> Gender Distribution</div>
          <div class="chart-container">
            <canvas id="leadershipChart"></canvas>
            <div class="chart-center-text">
              <div class="center-value" id="chartCenterValue">100%</div>
              <div class="center-label">Leaders</div>
            </div>
          </div>
          <div class="gender-legend" id="genderLegend"></div>
        </div>

        <div class="org-selector">
          <h4><i class="fas fa-building" style="color:var(--accent-primary);"></i> Select Organization</h4>
          <div class="org-list">
            ${leadershipData.map((org, i) => `
              <div class="org-item ${i === 0 ? 'active' : ''}" data-index="${i}" onclick="selectOrg(${i})">
                <div class="org-icon"><i class="fas fa-chart-pie"></i></div>
                <div>
                  <div style="font-weight:600;">${escHtml(org.org_name)}</div>
                  <div style="font-size:.7rem;color:var(--text-muted);">${Number(org.total_leaders).toLocaleString()} leaders</div>
                </div>
              </div>`).join('')}
          </div>
        </div>
      </div>
    </div>`;

  selectOrg(0);
}

function selectOrg(index) {
  selectedOrgIndex = index;
  const org = leadershipData[index];

  // Update org list active state
  document.querySelectorAll('.org-item').forEach((el, i) =>
    el.classList.toggle('active', i === index)
  );

  document.getElementById('chartCenterValue').textContent =
    Number(org.total_leaders).toLocaleString();

  // Render chart
  buildLeadershipChart('leadershipChart', org);

  // Render legend
  renderGenderLegend('genderLegend', org);

  // Render stat cards
  renderGenderStatCards('genderStatCards', org);
}

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
  // Load view from URL param (for sidebar links from other pages)
  const urlView = new URLSearchParams(location.search).get('view') || 'stories';
  loadView(urlView);
});

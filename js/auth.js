// ============================================================
// EqualVoice — Auth Page JS (login.php)
// ============================================================

'use strict';

const AUTH_API = '/equalvoice/api/auth_handler.php';

// --- Tab Switching ------------------------------------------
function switchTab(tab) {
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
  document.querySelector(`.auth-tab:nth-child(${tab === 'login' ? 1 : 2})`).classList.add('active');
  document.getElementById(tab === 'login' ? 'loginForm' : 'registerForm').classList.add('active');
  hideError();
  const url = new URL(location.href);
  url.searchParams.set('tab', tab);
  history.replaceState(null, '', url.toString());
}

// --- Fill Demo Credentials ----------------------------------
function fillDemo(email, password) {
  document.getElementById('loginEmail').value = email;
  document.getElementById('loginPassword').value = password;
  switchTab('login');
}

// --- Show / Hide Error --------------------------------------
function showError(msg) {
  const el = document.getElementById('authError');
  document.getElementById('authErrorText').textContent = msg;
  el.classList.add('visible');
}

function hideError() {
  document.getElementById('authError').classList.remove('visible');
}

// --- Toast --------------------------------------------------
function showToast(message, type = 'info') {
  const c = document.getElementById('toastContainer');
  if (!c) return;
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  const icons = { success: 'check-circle', error: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };
  t.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}"></i> ${message}
    <span class="toast__close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></span>`;
  c.appendChild(t);
  setTimeout(() => t.remove(), 4500);
}

// --- Generic API call ---------------------------------------
async function authRequest(data, btnId) {
  const btn = document.getElementById(btnId);
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Please wait...';
  hideError();

  try {
    const res = await fetch(AUTH_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
    const json = await res.json();
    if (json.success) {
      showToast(json.message, 'success');
      setTimeout(() => { location.href = json.redirect || '/equalvoice/dashboard.php'; }, 600);
    } else {
      showError(json.error || 'Something went wrong. Please try again.');
    }
  } catch (e) {
    showError('Network error. Make sure XAMPP is running.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
}

// --- Login Form Submit --------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email    = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    if (!email || !password) { showError('Please enter your email and password.'); return; }
    await authRequest({ action: 'login', email, password }, 'loginBtn');
  });

  // Register Form Submit
  document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const name             = document.getElementById('regName').value.trim();
    const email            = document.getElementById('regEmail').value.trim();
    const password         = document.getElementById('regPassword').value;
    const confirm_password = document.getElementById('regConfirm').value;
    const gender_identity  = document.getElementById('regGender').value;
    const department       = document.getElementById('regDept').value.trim();

    if (!name || !email || !password) { showError('Name, email, and password are required.'); return; }
    if (password.length < 6) { showError('Password must be at least 6 characters.'); return; }
    if (password !== confirm_password) { showError('Passwords do not match.'); return; }

    await authRequest({
      action: 'register', name, email, password, confirm_password, gender_identity, department,
    }, 'registerBtn');
  });
});

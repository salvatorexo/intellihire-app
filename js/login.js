/* =========================================================
   IntelliHire - Login Form Logic
   Handles: validation, AJAX login, role-based redirect
   ========================================================= */

const form = document.getElementById('loginForm');
const alertBox = document.getElementById('alertBox');
const submitBtn = document.getElementById('submitBtn');
const togglePass = document.getElementById('togglePass');
const passwordField = document.getElementById('password');

/* ---------- Alert Helpers ---------- */
function showAlert(message, type = 'error') {
    alertBox.textContent = message;
    alertBox.className = 'alert ' + type;
    alertBox.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function hideAlert() { alertBox.style.display = 'none'; }

/* ---------- Error Helpers ---------- */
function setError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errEl = document.querySelector(`.error[data-for="${fieldId}"]`);
    if (field) field.classList.add('invalid');
    if (errEl) errEl.textContent = message;
}
function clearError(fieldId) {
    const field = document.getElementById(fieldId);
    const errEl = document.querySelector(`.error[data-for="${fieldId}"]`);
    if (field) field.classList.remove('invalid');
    if (errEl) errEl.textContent = '';
}
function clearAllErrors() {
    document.querySelectorAll('.error').forEach(el => el.textContent = '');
    document.querySelectorAll('input').forEach(el => el.classList.remove('invalid'));
}

/* ---------- Show / Hide Password ---------- */
if (togglePass) {
    togglePass.addEventListener('click', () => {
        const type = passwordField.type === 'password' ? 'text' : 'password';
        passwordField.type = type;
        togglePass.textContent = type === 'password' ? '👁' : '🙈';
    });
}

/* ---------- Validation ---------- */
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function validateForm() {
    clearAllErrors();
    let valid = true;

    const email = document.getElementById('email').value.trim();
    if (!emailRegex.test(email)) {
        setError('email', 'Please enter a valid email address.');
        valid = false;
    }

    const password = passwordField.value;
    if (password.length < 1) {
        setError('password', 'Please enter your password.');
        valid = false;
    }

    return valid;
}

/* ---------- Live clear on blur ---------- */
document.querySelectorAll('input').forEach(field => {
    field.addEventListener('blur', () => {
        if (field.value.trim()) clearError(field.id);
    });
});

/* ---------- Form Submit ---------- */
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAlert();

    if (!validateForm()) {
        showAlert('Please fix the errors above.', 'error');
        return;
    }

    const payload = {
        email: document.getElementById('email').value.trim(),
        password: passwordField.value,
        remember: document.getElementById('remember').checked
    };

    submitBtn.disabled = true;
    submitBtn.textContent = 'Logging in...';

    try {
       const csrfToken = window.__getCsrfToken ? window.__getCsrfToken() : '';
const response = await fetch('php/login.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify(payload)
});

        // Safety: read raw text first (so we can see PHP errors if any)
        const raw = await response.text();
        let result;
        try {
            result = JSON.parse(raw);
        } catch (parseErr) {
            console.error('Server did not return JSON. Raw response:', raw);
            showAlert('Server error. Check console for details.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Login';
            return;
        }

        if (result.success) {
            showAlert(result.message || 'Login successful! Redirecting...', 'success');

            // Prefer the server-provided redirect, fall back to role-based paths
           let destination = result.redirect;
           if (result.success) {
    showAlert(result.message || 'Login successful! Redirecting...', 'success');

    // Refresh CSRF token for the new session
    if (result.csrf && window.__setCsrfToken) {
        window.__setCsrfToken(result.csrf);
    }

    // ... rest of redirect logic
}
if (!destination) {
    const role = result.role;
    if (role === 'applicant')      destination = 'applicant/applicantdash.html';
    else if (role === 'recruiter') destination = 'recruiter/recruiterdash.html';
    else if (role === 'admin')     destination = 'admin/dashboard.html';
    else                            destination = 'index.html';
}

setTimeout(() => {
    window.location.href = destination;
}, 1200);
            setTimeout(() => {
                window.location.href = destination;
            }, 400);
        } else {
            showAlert(result.message || 'Invalid email or password.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Login';
        }
    } catch (err) {
        console.error('Fetch error:', err);
        showAlert('Network error. Please try again.', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Login';
    }
});
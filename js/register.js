/* =========================================================
   IntelliHire - Registration Form Logic
   Handles: role toggle, validation, AJAX submission
   ========================================================= */

const form = document.getElementById('registerForm');
const roleRadios = document.querySelectorAll('input[name="account_type"]');
const applicantFields = document.getElementById('applicantFields');
const recruiterFields = document.getElementById('recruiterFields');
const alertBox = document.getElementById('alertBox');
const submitBtn = document.getElementById('submitBtn');

/* ---------- Toggle Role Fields ---------- */
function toggleRoleFields(role) {
    if (role === 'recruiter') {
        applicantFields.style.display = 'none';
        recruiterFields.style.display = 'block';
    } else {
        applicantFields.style.display = 'block';
        recruiterFields.style.display = 'none';
    }
    clearAllErrors();
}

roleRadios.forEach(radio => {
    radio.addEventListener('change', e => toggleRoleFields(e.target.value));
});

/* ---------- Alert Helper ---------- */
function showAlert(message, type = 'error') {
    alertBox.textContent = message;
    alertBox.className = 'alert ' + type;
    alertBox.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function hideAlert() {
    alertBox.style.display = 'none';
}

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
    document.querySelectorAll('input, select').forEach(el => el.classList.remove('invalid'));
}

/* ---------- Validation ---------- */
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const phoneRegex = /^[+]?[\d\s\-()]{7,20}$/;

function validateForm() {
    clearAllErrors();
    let valid = true;

    const role = document.querySelector('input[name="account_type"]:checked').value;

    /* ---- Common Fields ---- */
    const fullName = document.getElementById('fullName').value.trim();
    if (fullName.length < 3) {
        setError('fullName', 'Full name must be at least 3 characters.');
        valid = false;
    }

    const email = document.getElementById('email').value.trim();
    if (!emailRegex.test(email)) {
        setError('email', 'Please enter a valid email address.');
        valid = false;
    }

    const phone = document.getElementById('phone').value.trim();
    if (!phoneRegex.test(phone)) {
        setError('phone', 'Please enter a valid phone number.');
        valid = false;
    }

    const password = document.getElementById('password').value;
    if (password.length < 8) {
        setError('password', 'Password must be at least 8 characters.');
        valid = false;
    } else if (!/(?=.*[A-Za-z])(?=.*\d)/.test(password)) {
        setError('password', 'Password must contain letters and numbers.');
        valid = false;
    }

    const confirmPassword = document.getElementById('confirmPassword').value;
    if (confirmPassword !== password) {
        setError('confirmPassword', 'Passwords do not match.');
        valid = false;
    }

    /* ---- Applicant Fields ---- */
    if (role === 'applicant') {
        if (!document.getElementById('university').value.trim()) {
            setError('university', 'University / College is required.');
            valid = false;
        }
        if (!document.getElementById('course').value.trim()) {
            setError('course', 'Course / Field of study is required.');
            valid = false;
        }
        const gradYear = document.getElementById('gradYear').value;
        if (!gradYear || gradYear < 1980 || gradYear > 2100) {
            setError('gradYear', 'Enter a valid graduation year.');
            valid = false;
        }
        if (!document.getElementById('applicantLocation').value.trim()) {
            setError('applicantLocation', 'Location is required.');
            valid = false;
        }
    }

    /* ---- Recruiter Fields ---- */
    if (role === 'recruiter') {
        if (!document.getElementById('companyName').value.trim()) {
            setError('companyName', 'Company name is required.');
            valid = false;
        }
        const compEmail = document.getElementById('companyEmail').value.trim();
        if (!emailRegex.test(compEmail)) {
            setError('companyEmail', 'Please enter a valid company email.');
            valid = false;
        }
        const compPhone = document.getElementById('companyPhone').value.trim();
        if (!phoneRegex.test(compPhone)) {
            setError('companyPhone', 'Please enter a valid company phone.');
            valid = false;
        }
        if (!document.getElementById('companyLocation').value.trim()) {
            setError('companyLocation', 'Company location is required.');
            valid = false;
        }
        if (!document.getElementById('industry').value) {
            setError('industry', 'Please select an industry.');
            valid = false;
        }
    }

    return valid;
}

/* ---------- Live Validation (on blur) ---------- */
document.querySelectorAll('input, select').forEach(field => {
    field.addEventListener('blur', () => {
        if (field.value.trim()) clearError(field.id);
    });
});

/* ---------- Form Submission ---------- */
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAlert();

    if (!validateForm()) {
        showAlert('Please fix the errors above before continuing.', 'error');
        return;
    }

    const role = document.querySelector('input[name="account_type"]:checked').value;

    // Build payload
    const payload = {
        account_type: role,
        full_name: document.getElementById('fullName').value.trim(),
        email: document.getElementById('email').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        password: document.getElementById('password').value,
        confirm_password: document.getElementById('confirmPassword').value,
    };

    if (role === 'applicant') {
        payload.university = document.getElementById('university').value.trim();
        payload.course = document.getElementById('course').value.trim();
        payload.graduation_year = document.getElementById('gradYear').value;
        payload.location = document.getElementById('applicantLocation').value.trim();
    } else {
        payload.company_name = document.getElementById('companyName').value.trim();
        payload.company_email = document.getElementById('companyEmail').value.trim();
        payload.company_phone = document.getElementById('companyPhone').value.trim();
        payload.company_location = document.getElementById('companyLocation').value.trim();
        payload.industry = document.getElementById('industry').value;
    }

    // Disable button while submitting
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating account...';

    try {
        const response = await fetch('php/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.success) {
            showAlert(result.message || 'Account created! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 1800);
        } else {
            showAlert(result.message || 'Registration failed. Please try again.', 'error');
        }
    } catch (err) {
        showAlert('Network error. Please check your connection and try again.', 'error');
        console.error(err);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Account';
    }
});
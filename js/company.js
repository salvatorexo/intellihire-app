/* =========================================================
   IntelliHire - Recruiter Company Profile
   ========================================================= */

const alertBox = document.getElementById('alertBox');
const logoBox = document.getElementById('logoBox');
const logoImg = document.getElementById('logoImg');
const logoPlaceholder = document.getElementById('logoPlaceholder');
const logoInput = document.getElementById('logoInput');
const form = document.getElementById('companyForm');
const saveBtn = document.getElementById('saveBtn');

document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

function showAlert(msg, type = 'error', timeout = 4000) {
    alertBox.textContent = msg;
    alertBox.className = 'alert ' + type;
    alertBox.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (timeout) setTimeout(() => alertBox.style.display = 'none', timeout);
}

/* ---------- Load Company ---------- */
async function loadCompany() {
    try {
        const res = await fetch('../php/get-company.php');
        const data = await res.json();
        if (!data.success) { showAlert(data.message); return; }

        const c = data.company;
        const s = data.stats;

        // Header
        document.getElementById('headerName').textContent     = c.company_name || 'Company';
        document.getElementById('headerIndustry').textContent = c.industry ? `🏷 ${c.industry}` : '';

        // Header tags
        const tags = [];
        if (c.company_location) tags.push(`📍 ${c.company_location}`);
        if (c.company_email)    tags.push(`✉️ ${c.company_email}`);
        if (c.company_phone)    tags.push(`📞 ${c.company_phone}`);
        document.getElementById('headerTags').innerHTML = tags.map(t => `<span>${escapeHtml(t)}</span>`).join('');

        // Logo
        if (c.company_logo) {
            logoImg.src = '../' + c.company_logo;
            logoImg.style.display = 'block';
            logoPlaceholder.style.display = 'none';
        } else {
            logoPlaceholder.textContent = (c.company_name || 'C').charAt(0).toUpperCase();
            logoImg.style.display = 'none';
            logoPlaceholder.style.display = 'block';
        }

        // Mini stats
        document.getElementById('statTotalVacancies').textContent  = s.total_vacancies;
        document.getElementById('statActiveVacancies').textContent = s.active_vacancies;
        document.getElementById('statApplications').textContent    = s.total_applications;

        // Form fields
        document.getElementById('company_name').value     = c.company_name || '';
        document.getElementById('industry').value         = c.industry || '';
        document.getElementById('company_email').value    = c.company_email || '';
        document.getElementById('company_phone').value    = c.company_phone || '';
        document.getElementById('company_location').value = c.company_location || '';
        document.getElementById('website').value          = c.website || '';
        document.getElementById('description').value      = c.description || '';
    } catch (err) {
        console.error(err);
        showAlert('Failed to load company profile.');
    }
}

/* ---------- Save Company ---------- */
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    alertBox.style.display = 'none';

    const payload = {
        company_name:     document.getElementById('company_name').value.trim(),
        industry:         document.getElementById('industry').value,
        company_email:    document.getElementById('company_email').value.trim(),
        company_phone:    document.getElementById('company_phone').value.trim(),
        company_location: document.getElementById('company_location').value.trim(),
        website:          document.getElementById('website').value.trim(),
        description:      document.getElementById('description').value.trim()
    };

    if (!payload.company_name || !payload.industry || !payload.company_email ||
        !payload.company_phone || !payload.company_location) {
        showAlert('Please fill in all required fields.');
        return;
    }

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    try {
        const res = await fetch('../php/update-company.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.__getCsrfToken ? window.__getCsrfToken() : ''
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            showAlert('✅ Company profile updated.', 'success');
            loadCompany();
        } else {
            showAlert(data.message || 'Update failed.');
        }
    } catch (err) {
        console.error(err);
        showAlert('Network error.');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';
    }
});

/* ---------- Logo Upload ---------- */
logoBox.addEventListener('click', () => logoInput.click());

logoInput.addEventListener('change', async (e) => {
    if (!e.target.files.length) return;
    const file = e.target.files[0];

    // Validate
    const allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    if (!allowed.includes(file.type)) {
        showAlert('Only PNG, JPG, or WEBP allowed.');
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        showAlert('Logo must be under 2 MB.');
        return;
    }

    const fd = new FormData();
    fd.append('logo', file);

    try {
        const res = await fetch('../php/upload-logo.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-Token': window.__getCsrfToken ? window.__getCsrfToken() : ''
            },
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            showAlert('✅ Logo updated.', 'success');
            loadCompany();
        } else {
            showAlert(data.message || 'Upload failed.');
        }
    } catch (err) {
        showAlert('Network error uploading logo.');
    }
});

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

loadCompany();
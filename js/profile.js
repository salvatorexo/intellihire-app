/* =========================================================
   IntelliHire - Applicant Profile
   Loads, edits and saves applicant profile data.
   ========================================================= */

const form = document.getElementById('profileForm');
const alertBox = document.getElementById('alertBox');
const saveBtn = document.getElementById('saveBtn');

/* ---------- Sidebar Toggle ---------- */
document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

/* ---------- Alert ---------- */
function showAlert(msg, type = 'error') {
    alertBox.textContent = msg;
    alertBox.className = 'alert ' + type;
    alertBox.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ---------- Load Profile ---------- */
async function loadProfile() {
    try {
        const res = await fetch('../php/get-profile.php');
        const data = await res.json();

        if (!data.success) {
            showAlert('Could not load profile.');
            return;
        }

        const p = data.profile;
        ['full_name','email','phone','location','university','course',
         'graduation_year','skills','certifications','work_experience','education']
            .forEach(key => {
                const el = document.getElementById(key);
                if (el && p[key] !== null && p[key] !== undefined) el.value = p[key];
            });
    } catch (err) {
        console.error(err);
        showAlert('Network error loading profile.');
    }
}

/* ---------- Save Profile ---------- */
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    alertBox.style.display = 'none';

    const payload = {};
    ['full_name','phone','location','university','course',
     'graduation_year','skills','certifications','work_experience','education']
        .forEach(key => {
            const el = document.getElementById(key);
            if (el) payload[key] = el.value.trim();
        });

    if (!payload.full_name || !payload.phone) {
        showAlert('Full name and phone are required.');
        return;
    }

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    try {
        const res = await fetch('../php/update-profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            showAlert('Profile updated successfully!', 'success');
        } else {
            showAlert(data.message || 'Failed to update profile.');
        }
    } catch (err) {
        console.error(err);
        showAlert('Network error saving profile.');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';
    }
});

loadProfile();
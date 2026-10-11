/* =========================================================
   IntelliHire - My Vacancies (Recruiter)
   List, edit, close, delete, view applications
   ========================================================= */

let vacancies = [];
const alertBox = document.getElementById('alertBox');
const listEl   = document.getElementById('vacancyList');
const editModal = document.getElementById('editModal');

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

/* ---------- Load Vacancies ---------- */
async function loadVacancies() {
    try {
        const res = await fetch('../php/get-recruiter-jobs.php');
        const data = await res.json();
        if (!data.success) { showAlert(data.message); return; }
        vacancies = data.jobs || [];
        renderVacancies();
    } catch (err) {
        console.error(err);
        showAlert('Failed to load vacancies.');
    }
}

function renderVacancies() {
    if (!vacancies.length) {
        listEl.innerHTML = '<p class="empty">You haven\'t posted any vacancies yet.</p>';
        return;
    }

    listEl.innerHTML = vacancies.map(v => `
        <div class="vacancy-card">
            <span class="status-badge ${v.status}">${v.status === 'open' ? '🟢 Open' : '🔴 Closed'}</span>
            <h3>${escapeHtml(v.title)}</h3>
            <div class="vacancy-meta">
                <span>📍 ${escapeHtml(v.location || 'N/A')}</span>
                <span>💼 ${escapeHtml(v.job_type || 'Full-time')}</span>
                ${v.deadline ? `<span>⏰ ${v.deadline}</span>` : ''}
            </div>
            <p style="color:var(--gray);font-size:0.86rem;">
                <strong>${v.application_count}</strong> application(s) · 
                ${v.avg_score ? `<strong>Avg AI score: ${v.avg_score}%</strong>` : 'No AI scores yet'}
            </p>
            <div class="vacancy-actions">
                <button class="btn btn-outline" onclick="viewApplications(${v.id})">📋 Applications</button>
                <button class="btn btn-outline" onclick="openEdit(${v.id})">✏️ Edit</button>
            </div>
            <div class="vacancy-actions">
                <button class="btn btn-outline" onclick="toggleStatus(${v.id}, '${v.status}')">
                    ${v.status === 'open' ? '🔒 Close' : '🔓 Reopen'}
                </button>
                <button class="btn btn-outline" style="color:#dc2626;border-color:#dc2626;" onclick="deleteVacancy(${v.id})">
                    🗑 Delete
                </button>
            </div>
        </div>
    `).join('');
}

/* ---------- Navigate to Applications ---------- */
function viewApplications(jobId) {
    window.location.href = `applications.html?job_id=${jobId}`;
}

/* ---------- Edit Modal ---------- */
function openEdit(jobId) {
    const v = vacancies.find(x => x.id === jobId);
    if (!v) return;
    document.getElementById('edit_id').value = v.id;
    document.getElementById('edit_title').value = v.title || '';
    document.getElementById('edit_description').value = v.description || '';
    document.getElementById('edit_location').value = v.location || '';
    document.getElementById('edit_job_type').value = v.job_type || 'Full-time';
    document.getElementById('edit_salary_range').value = v.salary_range || '';
    document.getElementById('edit_deadline').value = v.deadline || '';
    document.getElementById('edit_education_requirements').value = v.education_requirements || '';
    document.getElementById('edit_requirements').value = v.requirements || '';
    document.getElementById('edit_skills').value = v.skills || '';
    document.getElementById('edit_preferred_skills').value = v.preferred_skills || '';
    editModal.classList.add('active');
}

function closeEdit() { editModal.classList.remove('active'); }
document.getElementById('editClose').addEventListener('click', closeEdit);
document.getElementById('cancelEdit').addEventListener('click', closeEdit);
editModal.addEventListener('click', e => { if (e.target === editModal) closeEdit(); });

document.getElementById('editForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    const payload = {
        id: document.getElementById('edit_id').value,
        title: document.getElementById('edit_title').value.trim(),
        description: document.getElementById('edit_description').value.trim(),
        location: document.getElementById('edit_location').value.trim(),
        job_type: document.getElementById('edit_job_type').value,
        salary_range: document.getElementById('edit_salary_range').value.trim(),
        deadline: document.getElementById('edit_deadline').value,
        education_requirements: document.getElementById('edit_education_requirements').value.trim(),
        requirements: document.getElementById('edit_requirements').value.trim(),
        skills: document.getElementById('edit_skills').value.trim(),
        preferred_skills: document.getElementById('edit_preferred_skills').value.trim()
    };

    try {
        const res = await fetch('../php/update-job.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            showAlert('✅ Vacancy updated.', 'success');
            closeEdit();
            loadVacancies();
        } else {
            showAlert(data.message || 'Update failed.');
        }
    } catch (err) {
        showAlert('Network error.');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';
    }
});

/* ---------- Toggle Status ---------- */
async function toggleStatus(jobId, currentStatus) {
    const newStatus = currentStatus === 'open' ? 'closed' : 'open';
    if (!confirm(`Are you sure you want to ${newStatus === 'closed' ? 'close' : 'reopen'} this vacancy?`)) return;

    try {
        const res = await fetch('../php/update-job.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: jobId, status: newStatus })
        });
        const data = await res.json();
        if (data.success) {
            showAlert(`✅ Vacancy ${newStatus}.`, 'success');
            loadVacancies();
        } else {
            showAlert(data.message);
        }
    } catch (err) { showAlert('Network error.'); }
}

/* ---------- Delete ---------- */
async function deleteVacancy(jobId) {
    if (!confirm('Delete this vacancy? This cannot be undone. All applications for this vacancy will also be removed.')) return;
    try {
        const res = await fetch('../php/delete-job.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: jobId })
        });
        const data = await res.json();
        if (data.success) {
            showAlert('🗑 Vacancy deleted.', 'success');
            loadVacancies();
        } else {
            showAlert(data.message);
        }
    } catch (err) { showAlert('Network error.'); }
}

/* ---------- Helpers ---------- */
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

loadVacancies();
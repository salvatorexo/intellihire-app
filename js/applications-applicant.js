/* =========================================================
   IntelliHire - Applicant: My Applications
   ========================================================= */

let allApps = [];
let currentFilter = '';
const alertBox = document.getElementById('alertBox');
const listEl   = document.getElementById('applicationsList');

document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

function showAlert(msg, type = 'error') {
    alertBox.textContent = msg;
    alertBox.className = 'alert ' + type;
    alertBox.style.display = 'block';
    setTimeout(() => alertBox.style.display = 'none', 4000);
}

/* ---------- Load Applications ---------- */
async function loadApplications() {
    try {
        const res = await fetch('../php/get-my-applications.php');
        const data = await res.json();
        if (!data.success) { showAlert(data.message); return; }
        allApps = data.applications || [];
        render();
    } catch (err) {
        console.error(err);
        listEl.innerHTML = '<p class="empty">Failed to load applications.</p>';
    }
}

/* ---------- Render ---------- */
function render() {
    const filtered = currentFilter
        ? allApps.filter(a => a.status === currentFilter)
        : allApps;

    if (!filtered.length) {
        listEl.innerHTML = `
            <div class="panel" style="text-align:center;padding:40px;">
                <p style="font-size:2.5rem;margin-bottom:10px;">📭</p>
                <p style="color:var(--gray);">
                    ${currentFilter ? 'No applications with this status.' : 'You haven\'t applied to any jobs yet.'}
                </p>
                ${!currentFilter ? '<a href="find-jobs.html" class="btn btn-primary" style="margin-top:16px;">Find Jobs</a>' : ''}
            </div>
        `;
        return;
    }

    listEl.innerHTML = filtered.map(a => {
        const score = a.match_score !== null ? Math.round(a.match_score) : null;
        const scoreClass = score === null ? '' 
            : score >= 80 ? 'color:#16a34a' 
            : score >= 60 ? 'color:#d97706' 
            : 'color:#dc2626';

        return `
            <div class="app-card">
                <div>
                    <h3>${escapeHtml(a.job_title)}</h3>
                    <div class="company">${escapeHtml(a.company_name)}</div>
                    <div class="app-meta">
                        <span>📍 ${escapeHtml(a.location || 'N/A')}</span>
                        <span>💼 ${escapeHtml(a.job_type || 'Full-time')}</span>
                        <span>📅 Applied ${a.applied_at}</span>
                    </div>
                    <div style="margin-top:12px;">
                        <span class="status-pill ${a.status}">${statusLabel(a.status)}</span>
                        ${a.recommendation ? `<span class="status-pill" style="background:#f1f5f9;color:#475569;margin-left:6px;">🤖 ${a.recommendation}</span>` : ''}
                    </div>
                </div>
                ${score !== null ? `
                    <div class="app-score">
                        <div class="num" style="${scoreClass}">${score}%</div>
                        <div class="lbl">AI Match</div>
                    </div>
                ` : `
                    <div class="app-score">
                        <div class="num" style="color:#cbd5e1;">—</div>
                        <div class="lbl">Pending</div>
                    </div>
                `}
            </div>
        `;
    }).join('');
}

/* ---------- Filter Buttons ---------- */
document.querySelectorAll('[data-filter]').forEach(btn => {
    btn.addEventListener('click', () => {
        currentFilter = btn.dataset.filter;
        document.querySelectorAll('[data-filter]').forEach(b => {
            b.classList.toggle('btn-primary', b === btn);
            b.classList.toggle('btn-outline', b !== btn);
        });
        render();
    });
});

/* ---------- Helpers ---------- */
function statusLabel(s) {
    const map = {
        applied: 'Applied',
        review: 'Under Review',
        shortlisted: 'Shortlisted ⭐',
        interview: 'Interview 🎤',
        accepted: 'Accepted ✅',
        rejected: 'Rejected ❌',
        hired: 'Hired 🎉'
    };
    return map[s] || s;
}
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

loadApplications();
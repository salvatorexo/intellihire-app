/* =========================================================
   IntelliHire - Recruiter Applications (AI Ranked)
   ========================================================= */

let allApps = [];
let recruiterJobs = [];
const alertBox = document.getElementById('alertBox');

const urlParams   = new URLSearchParams(window.location.search);
const initialJob  = urlParams.get('job_id') || '';
const initialFilt = urlParams.get('filter') || '';

document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

function showAlert(msg, type = 'error') {
    alertBox.textContent = msg;
    alertBox.className = 'alert ' + type;
    alertBox.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setTimeout(() => alertBox.style.display = 'none', 4000);
}

/* ---------- Initial title from filter ---------- */
if (initialFilt === 'shortlisted') document.getElementById('pageTitle').textContent = 'Shortlisted Candidates';
if (initialFilt === 'interview')   document.getElementById('pageTitle').textContent = 'Interview Schedule';

/* ---------- Load Jobs Dropdown ---------- */
async function loadJobDropdown() {
    try {
        const res = await fetch('../php/get-recruiter-jobs.php');
        const data = await res.json();
        if (!data.success) return;
        recruiterJobs = data.jobs || [];
        const sel = document.getElementById('filterJob');
        sel.innerHTML = '<option value="">All Vacancies</option>' +
            recruiterJobs.map(j => `<option value="${j.id}">${escapeHtml(j.title)}</option>`).join('');
        if (initialJob) sel.value = initialJob;
    } catch (err) { console.error(err); }
}

/* ---------- Load Applications ---------- */
async function loadApplications() {
    try {
        const res = await fetch('../php/get-applications.php');
        const data = await res.json();
        if (!data.success) { showAlert(data.message); return; }
        allApps = data.applications || [];
        renderApps();
    } catch (err) {
        console.error(err);
        document.getElementById('appsBody').innerHTML =
            `<tr><td colspan="8" class="empty">Failed to load.</td></tr>`;
    }
}

function renderApps() {
    const jobId  = document.getElementById('filterJob').value;
    const status = initialFilt || document.getElementById('filterStatus').value;
    const minS   = parseFloat(document.getElementById('filterScore').value) || 0;

    let filtered = allApps.filter(a => {
        if (jobId && String(a.job_id) !== String(jobId)) return false;
        if (status && a.status !== status) return false;
        if (a.match_score !== null && a.match_score < minS) return false;
        if (a.match_score === null && minS > 0) return false;
        return true;
    });

    filtered.sort((a, b) => (b.match_score || 0) - (a.match_score || 0));

    const tbody = document.getElementById('appsBody');
    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="8" class="empty">No applications match your filters.</td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map((a, i) => {
        const score = a.match_score !== null ? a.match_score + '%' : '—';
        const recClass = a.recommendation ? a.recommendation.toLowerCase().split(' ')[0] : '';
        const recColor = recClass === 'strong' ? 'color:#16a34a;font-weight:600'
                       : recClass === 'good'   ? 'color:#d97706;font-weight:600'
                       : recClass === 'weak'   ? 'color:#dc2626;font-weight:600'
                       : 'color:#64748b';

        return `
            <tr>
                <td><strong>#${i + 1}</strong></td>
                <td>${escapeHtml(a.candidate_name)}</td>
                <td>${escapeHtml(a.job_title)}</td>
                <td><strong>${score}</strong></td>
                <td style="${recColor}">${a.recommendation || 'Pending'}</td>
                <td>${a.applied_at}</td>
                <td>
                    <select class="status-select" onchange="updateStatus(${a.id}, this.value)">
                        ${statusOptions(a.status)}
                    </select>
                </td>
                <td>
                    <button class="btn btn-outline" style="padding:6px 12px;font-size:0.8rem;"
                            onclick="viewCandidate(${a.id})">View</button>
                </td>
            </tr>
        `;
    }).join('');
}

function statusOptions(current) {
    const opts = [
        ['applied',     'Applied'],
        ['review',      'Under Review'],
        ['shortlisted', 'Shortlisted'],
        ['interview',   'Interview'],
        ['accepted',    'Accepted'],
        ['rejected',    'Rejected']
    ];
    return opts.map(([val, label]) =>
        `<option value="${val}" ${val === current ? 'selected' : ''}>${label}</option>`
    ).join('');
}

/* ---------- Update Status ---------- */
async function updateStatus(appId, newStatus) {
    try {
        const res = await fetch('../php/update-application-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ application_id: appId, status: newStatus })
        });
        const data = await res.json();
        if (data.success) {
            showAlert('✅ Status updated to ' + newStatus + '.', 'success');
            const app = allApps.find(x => x.id === appId);
            if (app) app.status = newStatus;
        } else {
            showAlert(data.message || 'Update failed.');
        }
    } catch (err) {
        showAlert('Network error.');
    }
}

/* ---------- Open Candidate Detail ---------- */
function viewCandidate(appId) {
    window.location.href = `candidate.html?id=${appId}`;
}

/* ---------- Filters ---------- */
document.getElementById('filterJob').addEventListener('change', renderApps);
document.getElementById('filterStatus').addEventListener('change', renderApps);
document.getElementById('filterScore').addEventListener('input', renderApps);
document.getElementById('resetBtn').addEventListener('click', () => {
    document.getElementById('filterJob').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterScore').value = '';
    history.replaceState({}, '', 'applications.html');
    document.getElementById('pageTitle').textContent = 'Applications';
    renderApps();
});

/* ---------- Helpers ---------- */
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

/* ---------- Init ---------- */
(async () => {
    await loadJobDropdown();
    await loadApplications();
})();
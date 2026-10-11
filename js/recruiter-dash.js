/* =========================================================
   IntelliHire - Recruiter Dashboard
   ========================================================= */

document.addEventListener('DOMContentLoaded', () => {
    loadRecruiterInfo();
    loadStats();
    loadRecentApplications();
    loadTopCandidates();
});

document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

async function loadRecruiterInfo() {
    try {
        const res = await fetch('../php/recruiter-stats.php');
        const data = await res.json();
        if (data.success) {
            const name = data.user.full_name || 'Recruiter';
            document.getElementById('userName').textContent = name;
            document.getElementById('welcomeName').textContent = name.split(' ')[0];
            document.getElementById('userAvatar').textContent = name.charAt(0).toUpperCase();
            document.getElementById('welcomeCompany').textContent =
                data.company ? `Manage vacancies at ${data.company.company_name}` : '';
        }
    } catch (err) { console.error(err); }
}

async function loadStats() {
    try {
        const res = await fetch('../php/recruiter-stats.php?stats=1');
        const data = await res.json();
        if (data.success) {
            document.getElementById('statVacancies').textContent    = data.stats.vacancies;
            document.getElementById('statApplications').textContent = data.stats.applications;
            document.getElementById('statShortlisted').textContent  = data.stats.shortlisted;
            document.getElementById('statInterviews').textContent   = data.stats.interviews;
            document.getElementById('statRejected').textContent     = data.stats.rejected;
        }
    } catch (err) { console.error(err); }
}

async function loadRecentApplications() {
    const tbody = document.getElementById('recentAppsBody');
    try {
        const res = await fetch('../php/recruiter-stats.php?recent=1');
        const data = await res.json();
        if (!data.success || !data.recent.length) {
            tbody.innerHTML = `<tr><td colspan="5" class="empty">No applications yet.</td></tr>`;
            return;
        }
        tbody.innerHTML = data.recent.map(a => `
            <tr>
                <td>${escapeHtml(a.candidate_name)}</td>
                <td>${escapeHtml(a.job_title)}</td>
                <td>${a.applied_at}</td>
                <td>${a.match_score ? a.match_score + '%' : '—'}</td>
                <td><span class="status-pill status-${a.status}">${capitalize(a.status)}</span></td>
            </tr>
        `).join('');
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" class="empty">Failed to load.</td></tr>`;
    }
}

async function loadTopCandidates() {
    const box = document.getElementById('topCandidates');
    try {
        const res = await fetch('../php/recruiter-stats.php?top=1');
        const data = await res.json();
        if (!data.success || !data.top.length) {
            box.innerHTML = `<p class="empty">No ranked candidates yet.</p>`;
            return;
        }
        box.innerHTML = data.top.map((c, i) => {
            const rankClass = i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : '';
            const scoreClass = c.match_score >= 80 ? 'score-strong'
                             : c.match_score >= 60 ? 'score-good' : 'score-weak';
            return `
                <div class="candidate-row">
                    <div class="candidate-rank ${rankClass}">${i + 1}</div>
                    <div class="candidate-info">
                        <strong>${escapeHtml(c.candidate_name)}</strong>
                        <small>${escapeHtml(c.job_title)}</small>
                    </div>
                    <div class="candidate-score ${scoreClass}">${c.match_score}%</div>
                </div>
            `;
        }).join('');
    } catch (err) {
        box.innerHTML = `<p class="empty">Failed to load candidates.</p>`;
    }
}

/* Helpers */
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
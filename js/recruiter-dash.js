/* =========================================================
   IntelliHire - Recruiter Dashboard (Single Fetch)
   Loads everything in ONE AJAX call.
   ========================================================= */

document.addEventListener('DOMContentLoaded', async () => {
    const toggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggle) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }

    try {
        const res = await fetch('../php/recruiter-stats.php');
        const data = await res.json();

        if (!data.success) {
            console.error(data.message);
            return;
        }

        /* ---------- User ---------- */
        const name = data.user.full_name || 'Recruiter';
        document.getElementById('userName').textContent = name;
        document.getElementById('welcomeName').textContent = name.split(' ')[0];
        document.getElementById('userAvatar').textContent = name.charAt(0).toUpperCase();
        if (data.company) {
            document.getElementById('welcomeCompany').textContent =
                `Manage vacancies at ${data.company.company_name}`;
        }

        /* ---------- Stats ---------- */
        document.getElementById('statVacancies').textContent    = data.stats.vacancies;
        document.getElementById('statApplications').textContent = data.stats.applications;
        document.getElementById('statShortlisted').textContent  = data.stats.shortlisted;
        document.getElementById('statInterviews').textContent   = data.stats.interviews;
        document.getElementById('statRejected').textContent     = data.stats.rejected;

        /* ---------- Recent Applications ---------- */
        const tbody = document.getElementById('recentAppsBody');
        if (tbody) {
            if (!data.recent.length) {
                tbody.innerHTML = `<tr><td colspan="5" class="empty">No applications yet.</td></tr>`;
            } else {
                tbody.innerHTML = data.recent.map(a => `
                    <tr>
                        <td>${escapeHtml(a.candidate_name)}</td>
                        <td>${escapeHtml(a.job_title)}</td>
                        <td>${a.applied_at}</td>
                        <td>${a.match_score ? a.match_score + '%' : '—'}</td>
                        <td><span class="status-pill status-${a.status}">${capitalize(a.status)}</span></td>
                    </tr>
                `).join('');
            }
        }

        /* ---------- Top Candidates ---------- */
        const box = document.getElementById('topCandidates');
        if (box) {
            if (!data.top.length) {
                box.innerHTML = `<p class="empty">No ranked candidates yet.</p>`;
            } else {
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
            }
        }
    } catch (err) {
        console.error('Dashboard load error:', err);
    }
});

/* ---------- Helpers ---------- */
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
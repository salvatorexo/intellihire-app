/* =========================================================
   IntelliHire - Admin Dashboard
   ========================================================= */

document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

async function loadStats() {
    try {
        const res = await fetch('../php/admin-stats.php');
        const data = await res.json();
        if (!data.success) { console.error(data.message); return; }

        const s = data.stats;
        document.getElementById('userName').textContent = data.user.full_name;
        document.getElementById('welcomeName').textContent = data.user.full_name.split(' ')[0];
        document.getElementById('userAvatar').textContent = data.user.full_name.charAt(0).toUpperCase();

        document.getElementById('statApplicants').textContent      = s.applicants;
        document.getElementById('statRecruiters').textContent      = s.recruiters;
        document.getElementById('statCompanies').textContent       = s.companies;
        document.getElementById('statVacancies').textContent       = s.vacancies;
        document.getElementById('statApplications').textContent    = s.applications;
        document.getElementById('statShortlisted').textContent     = s.shortlisted;
        document.getElementById('statInterviews').textContent      = s.interviews;
        document.getElementById('statActiveVacancies').textContent = s.active_vacancies;

        renderTrendChart(data.trend || []);
        renderStatusChart(s);
        renderRecentUsers(data.recent_users || []);
        renderRecentApplications(data.recent_applications || []);
    } catch (err) {
        console.error(err);
    }
}

/* ---------- Trend Chart ---------- */
let trendChart = null;
function renderTrendChart(trend) {
    const labels = trend.map(t => t.day);
    const values = trend.map(t => t.count);
    const ctx = document.getElementById('trendChart').getContext('2d');
    if (trendChart) trendChart.destroy();
    trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Applications',
                data: values,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.1)',
                fill: true,
                tension: 0.35,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}

/* ---------- Status Chart ---------- */
let statusChart = null;
function renderStatusChart(s) {
    const ctx = document.getElementById('statusChart').getContext('2d');
    if (statusChart) statusChart.destroy();
    statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Applied', 'Shortlisted', 'Interviews', 'Rejected'],
            datasets: [{
                data: [s.applied_only || 0, s.shortlisted, s.interviews, s.rejected],
                backgroundColor: ['#3b82f6', '#22c55e', '#8b5cf6', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

/* ---------- Recent Users ---------- */
function renderRecentUsers(users) {
    const box = document.getElementById('recentUsers');
    if (!users.length) {
        box.innerHTML = '<p class="empty">No signups yet.</p>';
        return;
    }
    box.innerHTML = users.map(u => `
        <div class="activity-item">
            <div class="activity-avatar">${u.full_name.charAt(0).toUpperCase()}</div>
            <div class="activity-info">
                <strong>${escapeHtml(u.full_name)}</strong>
                <small>${escapeHtml(u.email)} · ${u.account_type}</small>
            </div>
        </div>
    `).join('');
}

/* ---------- Recent Applications ---------- */
function renderRecentApplications(apps) {
    const box = document.getElementById('recentApplications');
    if (!apps.length) {
        box.innerHTML = '<p class="empty">No applications yet.</p>';
        return;
    }
    box.innerHTML = apps.map(a => `
        <div class="activity-item">
            <div class="activity-avatar">${a.candidate_name.charAt(0).toUpperCase()}</div>
            <div class="activity-info">
                <strong>${escapeHtml(a.candidate_name)}</strong>
                <small>${escapeHtml(a.job_title)} · ${a.match_score ? a.match_score + '%' : '—'}</small>
            </div>
        </div>
    `).join('');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

loadStats();
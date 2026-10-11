/* =========================================================
   IntelliHire - Recruiter Reports & Analytics
   ========================================================= */

document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

async function loadReports() {
    try {
        const res = await fetch('../php/recruiter-reports.php');
        const data = await res.json();

        if (!data.success) {
            console.error(data.message);
            return;
        }

        const r = data.reports;

        /* KPIs */
        document.getElementById('kpiVacancies').textContent      = r.total_vacancies;
        document.getElementById('kpiActive').textContent         = r.active_vacancies;
        document.getElementById('kpiApplications').textContent   = r.total_applications;
        document.getElementById('kpiShortlistRate').textContent  = r.shortlist_rate + '%';
        document.getElementById('kpiInterviewRate').textContent  = r.interview_rate + '%';
        document.getElementById('kpiAvgScore').textContent       = r.avg_score ? r.avg_score + '%' : '—';

        /* Charts */
        renderMonthly(r.monthly);
        renderStatus(r.status_breakdown);
        renderScore(r.score_distribution);
        renderVacancies(r.top_vacancies);
        renderTopVacancy(r.best_vacancy);
    } catch (err) {
        console.error('Reports error:', err);
    }
}

/* ---------- Monthly Line Chart ---------- */
function renderMonthly(monthly) {
    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: monthly.labels,
            datasets: [{
                label: 'Applications',
                data: monthly.values,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.12)',
                fill: true,
                tension: 0.35,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
}

/* ---------- Status Doughnut ---------- */
function renderStatus(s) {
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Applied', 'Review', 'Shortlisted', 'Interview', 'Rejected'],
            datasets: [{
                data: [s.applied, s.review, s.shortlisted, s.interview, s.rejected],
                backgroundColor: ['#3b82f6', '#eab308', '#22c55e', '#8b5cf6', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
}

/* ---------- Score Distribution Bar ---------- */
function renderScore(buckets) {
    new Chart(document.getElementById('scoreChart'), {
        type: 'bar',
        data: {
            labels: ['0–20', '21–40', '41–60', '61–80', '81–100'],
            datasets: [{
                label: 'Candidates',
                data: buckets,
                backgroundColor: ['#ef4444','#f97316','#eab308','#22c55e','#16a34a']
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
}

/* ---------- Top Vacancies Horizontal Bar ---------- */
function renderVacancies(v) {
    new Chart(document.getElementById('vacanciesChart'), {
        type: 'bar',
        data: {
            labels: v.labels,
            datasets: [{
                label: 'Applications',
                data: v.values,
                backgroundColor: '#2563eb'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
}

/* ---------- Best Vacancy Card ---------- */
function renderTopVacancy(v) {
    const box = document.getElementById('topVacancy');
    if (!v || !v.title) {
        box.innerHTML = '<p class="empty">Not enough data yet.</p>';
        return;
    }
    box.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div>
                <h4 style="font-size:1.1rem;margin-bottom:4px;">${escapeHtml(v.title)}</h4>
                <p style="color:var(--gray);font-size:0.85rem;">${escapeHtml(v.location || 'N/A')} · ${escapeHtml(v.job_type || 'Full-time')}</p>
            </div>
            <div style="text-align:right;">
                <div style="font-size:1.6rem;font-weight:700;color:var(--primary);">${v.applications}</div>
                <div style="font-size:0.78rem;color:var(--gray);">applications</div>
            </div>
        </div>
    `;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

loadReports();
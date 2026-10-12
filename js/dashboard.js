/* =========================================================
   IntelliHire - Applicant Dashboard (Optimized)
   Loads all data in parallel for faster rendering.
   ========================================================= */

document.addEventListener('DOMContentLoaded', async () => {
    setupSidebarToggle();

    // Fire all requests in parallel
    await Promise.all([
        loadStats(),
        loadProfileCompletion(),
        loadCVStatus(),
        loadRecentApplications(),
        loadRecommendedJobs()
    ]);
});

/* ---------- Sidebar Toggle ---------- */
function setupSidebarToggle() {
    const toggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggle) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }
}

/* ---------- Stats + User Info ---------- */
async function loadStats() {
    try {
        const res = await fetch('../php/get-applicant-stats.php');
        const data = await res.json();

        if (data.success) {
            // User info
            const name = data.user.full_name || 'Applicant';
            document.getElementById('userName').textContent = name;
            document.getElementById('welcomeName').textContent = name.split(' ')[0];
            document.getElementById('userAvatar').textContent = name.charAt(0).toUpperCase();

            // Stats
            document.getElementById('statApplications').textContent = data.stats.total_applications;
            document.getElementById('statPending').textContent      = data.stats.pending;
            document.getElementById('statShortlisted').textContent  = data.stats.shortlisted;
            document.getElementById('statAvailableJobs').textContent = data.stats.available_jobs;
            document.getElementById('notifCount').textContent        = data.stats.unread_notifications || 0;
        }
    } catch (err) {
        console.error('Stats error:', err);
    }
}

/* ---------- Profile Completion ---------- */
async function loadProfileCompletion() {
    try {
        const res = await fetch('../php/get-profile.php');
        const data = await res.json();
        if (!data.success) return;

        const p = data.profile;
        const fields = [
            { key: 'full_name',  label: 'Full name',   done: !!p.full_name },
            { key: 'email',      label: 'Email',       done: !!p.email },
            { key: 'phone',      label: 'Phone',       done: !!p.phone },
            { key: 'location',   label: 'Location',    done: !!p.location },
            { key: 'university', label: 'University',  done: !!p.university },
            { key: 'course',     label: 'Course',      done: !!p.course },
            { key: 'skills',     label: 'Skills',      done: !!p.skills }
        ];

        const done = fields.filter(f => f.done).length;
        const percent = Math.round((done / fields.length) * 100);

        document.getElementById('profileProgressFill').style.width = percent + '%';
        document.getElementById('profilePercent').textContent = percent + '%';

        const list = document.getElementById('profileChecklist');
        if (list) {
            list.innerHTML = fields.map(f =>
                `<li class="${f.done ? 'done' : ''}">${f.done ? '✅' : '⬜'} ${f.label}</li>`
            ).join('');
        }
    } catch (err) {
        console.error('Profile completion error:', err);
    }
}

/* ---------- CV Status ---------- */
async function loadCVStatus() {
    const box = document.getElementById('cvStatusBox');
    if (!box) return;
    try {
        const res = await fetch('../php/get-cv.php');
        const data = await res.json();

        if (data.success && data.cv) {
            box.innerHTML = `
                <div class="cv-icon">📄</div>
                <div class="cv-name">${data.cv.file_name}</div>
                <div class="cv-meta">Uploaded ${data.cv.uploaded_at}</div>
                <a href="cv.html" class="btn btn-outline btn-block">Replace CV</a>
            `;
        } else {
            box.innerHTML = `
                <div class="cv-icon">📤</div>
                <div class="empty">No CV uploaded yet.</div>
                <a href="cv.html" class="btn btn-primary btn-block">Upload CV</a>
            `;
        }
    } catch (err) {
        box.innerHTML = `<p class="empty">Could not load CV status.</p>`;
    }
}

/* ---------- Recent Applications ---------- */
async function loadRecentApplications() {
    const tbody = document.getElementById('recentAppsBody');
    if (!tbody) return;
    try {
        const res = await fetch('../php/get-applicant-stats.php?recent=1');
        const data = await res.json();

        if (!data.success || !data.recent || data.recent.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="empty">No applications yet.</td></tr>`;
            return;
        }

        tbody.innerHTML = data.recent.map(app => `
            <tr>
                <td>${escapeHtml(app.job_title)}</td>
                <td>${escapeHtml(app.company_name)}</td>
                <td>${app.applied_at}</td>
                <td><span class="status-pill status-${app.status}">${capitalize(app.status)}</span></td>
                <td>${app.match_score ? app.match_score + '%' : '—'}</td>
            </tr>
        `).join('');
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" class="empty">Could not load applications.</td></tr>`;
    }
}

/* ---------- Recommended Jobs ---------- */
async function loadRecommendedJobs() {
    const grid = document.getElementById('jobsGrid');
    if (!grid) return;
    try {
        const res = await fetch('../php/get-applicant-stats.php?jobs=1');
        const data = await res.json();

        if (!data.success || !data.jobs || data.jobs.length === 0) {
            grid.innerHTML = `<p class="empty">No jobs available right now.</p>`;
            return;
        }

        grid.innerHTML = data.jobs.slice(0, 4).map(job => `
            <div class="job-card" onclick="window.location.href='find-jobs.html'">
                <h4>${escapeHtml(job.title)}</h4>
                <div class="company">${escapeHtml(job.company_name)}</div>
                <div class="meta">
                    <span class="tag">📍 ${escapeHtml(job.location || 'N/A')}</span>
                    <span class="tag">${escapeHtml(job.job_type || 'Full-time')}</span>
                </div>
            </div>
        `).join('');
    } catch (err) {
        grid.innerHTML = `<p class="empty">Could not load jobs.</p>`;
    }
}

/* ---------- Helpers ---------- */
function capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
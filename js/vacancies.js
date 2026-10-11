/* =========================================================
   IntelliHire - Vacancies Page
   Listing, filtering, job details, apply flow
   ========================================================= */

let allJobs = [];
let appliedJobIds = new Set();
let currentJob = null;

const alertBox     = document.getElementById('alertBox');
const jobsList     = document.getElementById('jobsList');
const jobCount     = document.getElementById('jobCount');
const searchInput  = document.getElementById('searchInput');
const filterCat    = document.getElementById('filterCategory');
const filterLoc    = document.getElementById('filterLocation');
const filterType   = document.getElementById('filterJobType');
const sortSelect   = document.getElementById('sortSelect');
const modal        = document.getElementById('jobModal');
const modalBody    = document.getElementById('modalBody');

/* ---------- Sidebar Toggle ---------- */
document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

/* ---------- Alert ---------- */
function showAlert(msg, type = 'error', timeout = 4000) {
    alertBox.textContent = msg;
    alertBox.className = 'alert ' + type;
    alertBox.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (timeout) setTimeout(() => alertBox.style.display = 'none', timeout);
}

/* ---------- Load All Jobs ---------- */
async function loadJobs() {
    try {
        const res = await fetch('../php/get-jobs.php');
        const data = await res.json();

        if (!data.success) {
            showAlert(data.message || 'Failed to load jobs.');
            return;
        }

        allJobs = data.jobs || [];
        appliedJobIds = new Set(data.applied_job_ids || []);
        renderJobs();
    } catch (err) {
        console.error(err);
        showAlert('Network error loading jobs.');
    }
}

/* ---------- Filter + Render ---------- */
function renderJobs() {
    const keyword  = searchInput.value.trim().toLowerCase();
    const category = filterCat.value;
    const location = filterLoc.value.trim().toLowerCase();
    const jobType  = filterType.value;
    const sortBy   = sortSelect.value;

    let filtered = allJobs.filter(job => {
        if (keyword) {
            const haystack = `${job.title} ${job.company_name} ${job.skills || ''} ${job.description || ''}`.toLowerCase();
            if (!haystack.includes(keyword)) return false;
        }
        if (category && job.industry !== category) return false;
        if (location && !(job.location || '').toLowerCase().includes(location)) return false;
        if (jobType && job.job_type !== jobType) return false;
        return true;
    });

    // Sort
    if (sortBy === 'newest') {
        filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    } else if (sortBy === 'deadline') {
        filtered.sort((a, b) => new Date(a.deadline || '9999-12-31') - new Date(b.deadline || '9999-12-31'));
    } else if (sortBy === 'title') {
        filtered.sort((a, b) => a.title.localeCompare(b.title));
    }

    jobCount.textContent = filtered.length;

    if (filtered.length === 0) {
        jobsList.innerHTML = '<p class="empty">No jobs match your search.</p>';
        return;
    }

    jobsList.innerHTML = filtered.map(job => {
        const isApplied = appliedJobIds.has(job.id);
        const daysLeft = job.deadline ? daysUntil(job.deadline) : null;
        const deadlineClass = daysLeft !== null && daysLeft <= 3 ? 'urgent' : '';

        return `
            <div class="job-item">
                <h3>${escapeHtml(job.title)}</h3>
                <div class="company-name">${escapeHtml(job.company_name)}</div>
                <p class="job-desc">${escapeHtml(job.description || '').slice(0, 140)}${(job.description || '').length > 140 ? '...' : ''}</p>
                <div class="job-meta">
                    <span>📍 ${escapeHtml(job.location || 'N/A')}</span>
                    <span>💼 ${escapeHtml(job.job_type || 'Full-time')}</span>
                    ${job.industry ? `<span>🏷 ${escapeHtml(job.industry)}</span>` : ''}
                </div>
                ${job.deadline ? `<div class="deadline ${deadlineClass}">⏰ Deadline: ${formatDate(job.deadline)}${daysLeft !== null ? ` (${daysLeft} days left)` : ''}</div>` : ''}

                ${isApplied
                    ? `<div class="applied-badge">✅ Applied</div>`
                    : `<div class="job-actions">
                        <button class="btn btn-outline" onclick="openJobDetail(${job.id})">View Details</button>
                        <button class="btn btn-primary" onclick="applyToJob(${job.id})">Apply</button>
                       </div>`
                }
            </div>
        `;
    }).join('');
}

/* ---------- View Details ---------- */
async function openJobDetail(jobId) {
    try {
        const res = await fetch(`../php/get-job-details.php?id=${jobId}`);
        const data = await res.json();

        if (!data.success) {
            showAlert(data.message || 'Failed to load job.');
            return;
        }

        currentJob = data.job;
        const isApplied = appliedJobIds.has(currentJob.id);

        modalBody.innerHTML = `
            <h2>${escapeHtml(currentJob.title)}</h2>
            <p class="modal-company">${escapeHtml(currentJob.company_name)}</p>

            <div class="modal-tags">
                <span>📍 ${escapeHtml(currentJob.location || 'N/A')}</span>
                <span>💼 ${escapeHtml(currentJob.job_type || 'Full-time')}</span>
                ${currentJob.industry ? `<span>🏷 ${escapeHtml(currentJob.industry)}</span>` : ''}
                ${currentJob.deadline ? `<span>⏰ Deadline: ${formatDate(currentJob.deadline)}</span>` : ''}
            </div>

            <div class="modal-section">
                <h4>Description</h4>
                <p>${nl2br(escapeHtml(currentJob.description || 'No description provided.'))}</p>
            </div>

            <div class="modal-section">
                <h4>Requirements</h4>
                <p>${nl2br(escapeHtml(currentJob.requirements || 'No specific requirements listed.'))}</p>
            </div>

            <div class="modal-section">
                <h4>Education Requirements</h4>
                <p>${escapeHtml(currentJob.education_requirements || 'Not specified.')}</p>
            </div>

            ${currentJob.skills ? `
                <div class="modal-section">
                    <h4>Skills</h4>
                    <p>${escapeHtml(currentJob.skills)}</p>
                </div>
            ` : ''}

            <div class="modal-apply-bar">
                ${isApplied
                    ? `<button class="btn btn-outline btn-block" disabled style="background:#dcfce7;color:#166534;border-color:#166534;">✅ Already Applied</button>`
                    : `<button class="btn btn-primary btn-block" onclick="applyToJob(${currentJob.id})">Apply Now</button>`
                }
            </div>
        `;

        modal.classList.add('active');
    } catch (err) {
        console.error(err);
        showAlert('Failed to load job details.');
    }
}

function closeJobModal() {
    modal.classList.remove('active');
    currentJob = null;
}

document.getElementById('modalClose').addEventListener('click', closeJobModal);
modal.addEventListener('click', (e) => {
    if (e.target === modal) closeJobModal();
});

/* ---------- Apply to Job ---------- */
async function applyToJob(jobId) {
    if (appliedJobIds.has(jobId)) {
        showAlert('You have already applied to this job.', 'error');
        return;
    }

    try {
        const res = await fetch('../php/apply-job.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ job_id: jobId })
        });
        const data = await res.json();

        if (data.success) {
            appliedJobIds.add(jobId);
            closeJobModal();
            showAlert(`✅ Application submitted! Status: ${data.status || 'Applied'}. ${data.message || ''}`, 'success', 6000);

            // Re-render so the button becomes "Applied"
            renderJobs();
        } else {
            showAlert(data.message || 'Failed to apply.', 'error');
        }
    } catch (err) {
        console.error(err);
        showAlert('Network error submitting application.');
    }
}

/* ---------- Event Listeners ---------- */
searchInput.addEventListener('input', renderJobs);
filterCat.addEventListener('change', renderJobs);
filterLoc.addEventListener('input', renderJobs);
filterType.addEventListener('change', renderJobs);
sortSelect.addEventListener('change', renderJobs);
document.getElementById('searchBtn').addEventListener('click', renderJobs);

document.getElementById('resetBtn').addEventListener('click', () => {
    searchInput.value = '';
    filterCat.value = '';
    filterLoc.value = '';
    filterType.value = '';
    sortSelect.value = 'newest';
    renderJobs();
});

/* ---------- Helpers ---------- */
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}
function nl2br(str) { return str.replace(/\n/g, '<br>'); }
function formatDate(d) {
    const dt = new Date(d);
    return isNaN(dt) ? d : dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
function daysUntil(d) {
    const diff = Math.ceil((new Date(d) - new Date()) / (1000 * 60 * 60 * 24));
    return diff >= 0 ? diff : 0;
}

/* ---------- Init ---------- */
loadJobs();
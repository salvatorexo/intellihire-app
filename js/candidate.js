/* =========================================================
   IntelliHire - Candidate AI Breakdown
   ========================================================= */

const urlParams = new URLSearchParams(window.location.search);
const appId = urlParams.get('id');
const content = document.getElementById('content');

document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

async function loadCandidate() {
    if (!appId) {
        content.innerHTML = '<p class="empty">No candidate specified.</p>';
        return;
    }

    try {
        const res = await fetch(`../php/get-candidate.php?id=${appId}`);
        const data = await res.json();

        if (!data.success) {
            content.innerHTML = `<p class="empty">${data.message || 'Candidate not found.'}</p>`;
            return;
        }

        const c = data.candidate;
        const overall = c.match_score !== null ? Math.round(c.match_score) : null;
        const rec = c.recommendation || classify(overall);
        const recClass = rec.toLowerCase().split(' ')[0];

        content.innerHTML = `
            <div style="max-width:820px;">
                <div class="panel">
                    <div class="panel-head">
                        <h3>${escapeHtml(c.candidate_name)}</h3>
                        <a href="${c.cv_path ? '../' + c.cv_path : '#'}"
                           target="_blank" class="btn btn-outline">📄 View CV</a>
                    </div>
                    <p style="color:var(--gray);font-size:0.9rem;margin-bottom:12px;">
                        Applying for: <strong>${escapeHtml(c.job_title)}</strong>
                    </p>
                    <p style="color:var(--gray);font-size:0.85rem;">
                        Email: ${escapeHtml(c.email)} · Phone: ${escapeHtml(c.phone || 'N/A')}
                    </p>
                </div>

                <div class="overall-score-box">
                    <div class="score">${overall !== null ? overall + '%' : '—'}</div>
                    <div class="label">Overall Match Score</div>
                    <div class="recommendation ${recClass}">${rec}</div>
                </div>

                <div class="panel">
                    <h3 style="margin-bottom:16px;">AI Screening Breakdown</h3>

                    <div class="screening-grid">
                        ${screeningCard('Skills Match',     c.skills_score)}
                        ${screeningCard('Education Match',  c.education_score)}
                        ${screeningCard('Experience Match', c.experience_score)}
                    </div>

                    ${c.matched_keywords ? `
                        <div class="modal-section">
                            <h4>Matched Keywords from CV</h4>
                            <p>${escapeHtml(c.matched_keywords)}</p>
                        </div>
                    ` : ''}
                </div>

                <div class="panel">
                    <h3 style="margin-bottom:14px;">Recruiter Actions</h3>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button class="btn btn-primary" onclick="setStatus('shortlisted')">⭐ Shortlist</button>
                        <button class="btn btn-primary" onclick="setStatus('interview')">🎤 Invite to Interview</button>
                        <button class="btn btn-outline" style="color:#dc2626;border-color:#dc2626;" onclick="setStatus('rejected')">❌ Reject</button>
                    </div>
                    <p style="color:var(--gray);font-size:0.8rem;margin-top:12px;">
                        Current status: <strong id="currentStatus">${capitalize(c.status)}</strong>
                    </p>
                </div>
            </div>
        `;
    } catch (err) {
        console.error(err);
        content.innerHTML = '<p class="empty">Failed to load candidate.</p>';
    }
}

function screeningCard(label, score) {
    const s = score !== null && score !== undefined ? Math.round(score) : null;
    const cls = s === null ? '' : s >= 80 ? 'high' : s >= 60 ? 'mid' : 'low';
    return `
        <div class="screening-item">
            <div class="label">${label}</div>
            <div class="value ${cls}">${s !== null ? s + '%' : '—'}</div>
        </div>
    `;
}

async function setStatus(status) {
    if (!confirm(`Set this candidate's status to "${status}"?`)) return;
    try {
        const res = await fetch('../php/update-application-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ application_id: parseInt(appId), status })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('currentStatus').textContent = capitalize(status);
            alert('✅ Status updated to ' + status);
        } else {
            alert(data.message || 'Failed to update.');
        }
    } catch (err) {
        alert('Network error.');
    }
}

function classify(score) {
    if (score === null) return 'Pending';
    if (score >= 80) return 'Strong Match';
    if (score >= 60) return 'Good Match';
    return 'Weak Match';
}

function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

loadCandidate();
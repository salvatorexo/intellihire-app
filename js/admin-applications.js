let allApps = [];

document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

async function loadApps() {
    try {
        const res = await fetch('../php/admin-applications.php');
        const data = await res.json();
        if (!data.success) return;
        allApps = data.applications || [];
        render();
    } catch (err) { console.error(err); }
}

function render() {
    const q = document.getElementById('search').value.trim().toLowerCase();
    const status = document.getElementById('filterStatus').value;
    const filtered = allApps.filter(a => {
        if (status && a.status !== status) return false;
        if (q && !`${a.candidate_name} ${a.job_title} ${a.company_name}`.toLowerCase().includes(q)) return false;
        return true;
    });

    const tbody = document.getElementById('appsBody');
    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty">No applications found.</td></tr>';
        return;
    }
    tbody.innerHTML = filtered.map((a, i) => `
        <tr>
            <td>${i + 1}</td>
            <td>${escapeHtml(a.candidate_name)}</td>
            <td>${escapeHtml(a.job_title)}</td>
            <td>${escapeHtml(a.company_name)}</td>
            <td>${a.match_score ? a.match_score + '%' : '—'}</td>
            <td><span class="status-pill status-${a.status}">${a.status}</span></td>
            <td>${a.applied_at}</td>
        </tr>
    `).join('');
}

document.getElementById('search').addEventListener('input', render);
document.getElementById('filterStatus').addEventListener('change', render);

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

loadApps();
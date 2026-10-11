let allVacancies = [];
const alertBox = document.getElementById('alertBox');

document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

function showAlert(msg, type = 'error') {
    alertBox.textContent = msg;
    alertBox.className = 'alert ' + type;
    alertBox.style.display = 'block';
    setTimeout(() => alertBox.style.display = 'none', 4000);
}

async function loadVacancies() {
    try {
        const res = await fetch('../php/admin-vacancies.php');
        const data = await res.json();
        if (!data.success) { showAlert(data.message); return; }
        allVacancies = data.vacancies || [];
        render();
    } catch (err) { console.error(err); }
}

function render() {
    const q = document.getElementById('search').value.trim().toLowerCase();
    const status = document.getElementById('filterStatus').value;
    const filtered = allVacancies.filter(v => {
        if (status && v.status !== status) return false;
        if (q && !`${v.title} ${v.company_name} ${v.location}`.toLowerCase().includes(q)) return false;
        return true;
    });

    const tbody = document.getElementById('vacanciesBody');
    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty">No vacancies found.</td></tr>';
        return;
    }
    tbody.innerHTML = filtered.map((v, i) => `
        <tr>
            <td>${i + 1}</td>
            <td><strong>${escapeHtml(v.title)}</strong></td>
            <td>${escapeHtml(v.company_name)}</td>
            <td>${escapeHtml(v.location || '—')}</td>
            <td>${escapeHtml(v.job_type || '—')}</td>
            <td>${v.application_count}</td>
            <td><span class="status-pill status-${v.status === 'open' ? 'shortlisted' : 'rejected'}">${v.status}</span></td>
            <td class="admin-table-actions">
                <button class="btn btn-outline" onclick="toggleStatus(${v.id}, '${v.status}')">
                    ${v.status === 'open' ? 'Close' : 'Open'}
                </button>
                <button class="btn btn-outline" style="color:#dc2626;border-color:#dc2626;"
                        onclick="deleteVacancy(${v.id}, '${escapeAttr(v.title)}')">Delete</button>
            </td>
        </tr>
    `).join('');
}

async function toggleStatus(id, current) {
    const next = current === 'open' ? 'closed' : 'open';
    if (!confirm(`${next === 'closed' ? 'Close' : 'Reopen'} this vacancy?`)) return;
    try {
        const res = await fetch('../php/admin-toggle-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ job_id: id, status: next })
        });
        const data = await res.json();
        if (data.success) { showAlert('✅ Updated.', 'success'); loadVacancies(); }
        else showAlert(data.message);
    } catch (err) { showAlert('Network error.'); }
}

async function deleteVacancy(id, title) {
    if (!confirm(`Delete vacancy "${title}"? Applications will be removed.`)) return;
    try {
        const res = await fetch('../php/admin-delete-vacancy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) { showAlert('🗑 Vacancy deleted.', 'success'); loadVacancies(); }
        else showAlert(data.message);
    } catch (err) { showAlert('Network error.'); }
}

document.getElementById('search').addEventListener('input', render);
document.getElementById('filterStatus').addEventListener('change', render);

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function escapeAttr(str) { return String(str || '').replace(/'/g, "\\'"); }

loadVacancies();
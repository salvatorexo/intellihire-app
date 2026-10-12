/* =========================================================
   IntelliHire - Admin: Manage Companies
   Uses admin-delete-company.php so it deletes BOTH the
   recruiters row AND the users row.
   ========================================================= */

let allCompanies = [];
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

async function loadCompanies() {
    try {
        const res = await fetch('../php/admin-companies.php');
        const data = await res.json();
        if (!data.success) { showAlert(data.message); return; }
        allCompanies = data.companies || [];
        render();
    } catch (err) { console.error(err); }
}

function render() {
    const q = document.getElementById('search').value.trim().toLowerCase();
    const filtered = allCompanies.filter(c =>
        !q || `${c.company_name} ${c.industry} ${c.company_email} ${c.company_location}`.toLowerCase().includes(q)
    );
    const tbody = document.getElementById('companiesBody');
    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty">No companies found.</td></tr>';
        return;
    }
    tbody.innerHTML = filtered.map((c, i) => `
        <tr>
            <td>${i + 1}</td>
            <td><strong>${escapeHtml(c.company_name)}</strong></td>
            <td>${escapeHtml(c.industry || '—')}</td>
            <td>${escapeHtml(c.company_email || '—')}</td>
            <td>${escapeHtml(c.company_phone || '—')}</td>
            <td>${escapeHtml(c.company_location || '—')}</td>
            <td><strong>${c.vacancy_count}</strong></td>
            <td class="admin-table-actions">
                <button class="btn btn-outline" style="color:#dc2626;border-color:#dc2626;"
                        onclick="deleteCompany(${c.user_id}, '${escapeAttr(c.company_name)}')">Delete</button>
            </td>
        </tr>
    `).join('');
}

async function deleteCompany(userId, name) {
    if (!confirm(`Delete company "${name}" and its recruiter account?\n\nAll their vacancies and applications will also be removed.`)) return;
    try {
        const res = await fetch('../php/admin-delete-company.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: userId })
        });
        const data = await res.json();
        if (data.success) {
            showAlert('🗑 Company deleted.', 'success');
            loadCompanies();
        } else {
            showAlert(data.message || 'Delete failed.');
        }
    } catch (err) {
        showAlert('Network error.');
    }
}

document.getElementById('search').addEventListener('input', render);

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function escapeAttr(str) { return String(str || '').replace(/'/g, "\\'"); }

loadCompanies();
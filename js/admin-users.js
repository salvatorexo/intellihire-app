/* =========================================================
   IntelliHire - Admin: Manage Users
   ========================================================= */

let allUsers = [];
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

async function loadUsers() {
    try {
        const res = await fetch('../php/admin-users.php');
        const data = await res.json();
        if (!data.success) { showAlert(data.message); return; }
        allUsers = data.users || [];
        render();
    } catch (err) {
        console.error(err);
        document.getElementById('usersBody').innerHTML =
            '<tr><td colspan="7" class="empty">Failed to load.</td></tr>';
    }
}

function render() {
    const q    = document.getElementById('search').value.trim().toLowerCase();
    const role = document.getElementById('filterRole').value;

    const filtered = allUsers.filter(u => {
        if (role && u.account_type !== role) return false;
        if (q && !(`${u.full_name} ${u.email}`.toLowerCase().includes(q))) return false;
        return true;
    });

    const tbody = document.getElementById('usersBody');
    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty">No users match.</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map((u, i) => `
        <tr>
            <td>${i + 1}</td>
            <td>${escapeHtml(u.full_name)}</td>
            <td>${escapeHtml(u.email)}</td>
            <td>${escapeHtml(u.phone || '—')}</td>
            <td><span class="status-pill status-${u.account_type}">${capitalize(u.account_type)}</span></td>
            <td>${u.created_at}</td>
            <td class="admin-table-actions">
                <button class="btn btn-outline" style="color:#dc2626;border-color:#dc2626;"
                        onclick="deleteUser(${u.id}, '${escapeAttr(u.full_name)}')">Delete</button>
            </td>
        </tr>
    `).join('');
}

async function deleteUser(id, name) {
    if (!confirm(`Delete user "${name}"? This will remove their profile, CV, and applications.`)) return;
    try {
        const res = await fetch('../php/admin-delete-user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) {
            showAlert('🗑 User deleted.', 'success');
            loadUsers();
        } else {
            showAlert(data.message || 'Delete failed.');
        }
    } catch (err) {
        showAlert('Network error.');
    }
}

document.getElementById('search').addEventListener('input', render);
document.getElementById('filterRole').addEventListener('change', render);

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function escapeAttr(str) { return String(str || '').replace(/'/g, "\\'"); }
function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

loadUsers();
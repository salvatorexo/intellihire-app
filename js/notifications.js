/* =========================================================
   IntelliHire - Applicant Notifications
   ========================================================= */

const listEl = document.getElementById('notifList');

document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

async function loadNotifications() {
    try {
        const res = await fetch('../php/get-notifications.php');
        const data = await res.json();
        if (!data.success) {
            listEl.innerHTML = '<p class="empty">Failed to load.</p>';
            return;
        }

        const notifs = data.notifications || [];
        if (!notifs.length) {
            listEl.innerHTML = `
                <div class="panel" style="text-align:center;padding:40px;">
                    <p style="font-size:2.5rem;margin-bottom:10px;">🔔</p>
                    <p style="color:var(--gray);">No notifications yet.</p>
                </div>
            `;
            return;
        }

        listEl.innerHTML = notifs.map(n => `
            <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}">
                <div class="notif-icon">${n.is_read == 0 ? '🔵' : '⚪'}</div>
                <div class="notif-body">
                    <p>${escapeHtml(n.message)}</p>
                    <small>${n.created_at}</small>
                </div>
            </div>
        `).join('');
    } catch (err) {
        console.error(err);
        listEl.innerHTML = '<p class="empty">Failed to load.</p>';
    }
}

document.getElementById('markAllBtn').addEventListener('click', async () => {
    try {
        const res = await fetch('../php/get-notifications.php', { method: 'POST' });
        const data = await res.json();
        if (data.success) loadNotifications();
    } catch (err) { console.error(err); }
});

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

loadNotifications();
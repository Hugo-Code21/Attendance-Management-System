document.addEventListener('DOMContentLoaded', () => {
    const themeSwitch = document.querySelector('[data-theme-switch]');
    const themeModes = ['light', 'dark'];

    const readCookieTheme = () => {
        const themeCookie = document.cookie
            .split(';')
            .map((cookie) => cookie.trim())
            .find((cookie) => cookie.startsWith('ams-theme='));
        const mode = themeCookie ? themeCookie.slice('ams-theme='.length) : null;
        return themeModes.includes(mode) ? mode : null;
    };

    const saveTheme = (mode) => {
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = `ams-theme=${mode}; Max-Age=31536000; Path=/; SameSite=Lax${secure}`;
    };

    const applyTheme = (mode) => {
        if (!themeModes.includes(mode)) {
            mode = 'light';
        }
        document.documentElement.dataset.themeMode = mode;
        document.documentElement.dataset.theme = mode;
        if (themeSwitch) {
            const isDark = mode === 'dark';
            themeSwitch.setAttribute('aria-checked', String(isDark));
            themeSwitch.title = `Switch to ${isDark ? 'light' : 'dark'} mode`;
        }
    };

    const initialTheme = readCookieTheme() || document.documentElement.dataset.theme;
    applyTheme(initialTheme);

    themeSwitch?.addEventListener('click', () => {
        const mode = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        applyTheme(mode);
        saveTheme(mode);
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    const roleSelect = document.querySelector('[data-role-select]');
    const classField = document.querySelector('[data-role-field="class_name"]');
    const subjectField = document.querySelector('[data-role-field="subject"]');
    const updateRoleFields = () => {
        if (!roleSelect) return;
        const role = roleSelect.value;
        if (classField) {
            classField.hidden = role === 'admin';
            classField.querySelector('input').required = role !== 'admin';
        }
        if (subjectField) {
            subjectField.hidden = role !== 'teacher';
            subjectField.querySelector('input').required = role === 'teacher';
        }
    };
    roleSelect?.addEventListener('change', updateRoleFields);
    updateRoleFields();

    const liveAccounts = document.querySelector('[data-live-accounts]');
    if (liveAccounts) {
        const rows = liveAccounts.querySelector('[data-account-rows]');
        const status = liveAccounts.querySelector('[data-live-account-status]');
        const refreshUrl = liveAccounts.dataset.refreshUrl;
        const csrfToken = liveAccounts.dataset.csrfToken;
        const adminId = liveAccounts.dataset.adminId;

        const createCell = (content, tagName = 'td') => {
            const cell = document.createElement(tagName);
            if (content instanceof Node) {
                cell.append(content);
            } else {
                cell.textContent = content;
            }
            return cell;
        };

        const renderAccounts = (accounts) => {
            rows.replaceChildren();
            if (accounts.length === 0) {
                const emptyRow = document.createElement('tr');
                const emptyCell = createCell('No accounts match this role.');
                emptyCell.colSpan = 6;
                emptyCell.className = 'empty-cell';
                emptyRow.append(emptyCell);
                rows.append(emptyRow);
                return;
            }

            accounts.forEach((account) => {
                const row = document.createElement('tr');
                const personCell = document.createElement('td');
                const name = document.createElement('strong');
                name.textContent = account.full_name;
                const username = document.createElement('small');
                username.className = 'table-sub';
                username.textContent = account.username;
                personCell.append(name, username);

                const role = document.createElement('span');
                role.className = `role-tag role-${account.role}`;
                role.textContent = account.role.charAt(0).toUpperCase() + account.role.slice(1);

                const classCell = document.createElement('td');
                classCell.textContent = account.class_name || '—';
                if (account.subject) {
                    const subject = document.createElement('small');
                    subject.className = 'table-sub';
                    subject.textContent = account.subject;
                    classCell.append(subject);
                }

                const active = Number(account.is_active) === 1;
                const accountStatus = document.createElement('span');
                accountStatus.className = `status ${active ? 'status-present' : 'status-absent'}`;
                accountStatus.textContent = active ? 'Active' : 'Inactive';

                const actions = document.createElement('td');
                actions.className = 'row-actions';
                const editLink = document.createElement('a');
                editLink.className = 'text-link';
                editLink.href = `users.php?edit=${encodeURIComponent(account.id)}`;
                editLink.textContent = 'Edit';
                actions.append(editLink);

                if (String(account.id) !== adminId) {
                    const deleteForm = document.createElement('form');
                    deleteForm.method = 'post';
                    deleteForm.className = 'inline-form';
                    deleteForm.dataset.confirm = 'Delete this account? Its attendance history will also be removed.';
                    [
                        ['csrf_token', csrfToken],
                        ['action', 'delete'],
                        ['id', account.id],
                    ].forEach(([nameValue, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = nameValue;
                        input.value = value;
                        deleteForm.append(input);
                    });
                    const deleteButton = document.createElement('button');
                    deleteButton.type = 'submit';
                    deleteButton.className = 'text-link text-danger';
                    deleteButton.textContent = 'Delete';
                    deleteForm.append(deleteButton);
                    deleteForm.addEventListener('submit', (event) => {
                        if (!window.confirm(deleteForm.dataset.confirm)) {
                            event.preventDefault();
                        }
                    });
                    actions.append(deleteForm);
                }

                row.append(
                    personCell,
                    createCell(`#${account.id}`),
                    createCell(role),
                    classCell,
                    createCell(accountStatus),
                    actions,
                );
                rows.append(row);
            });
        };

        const refreshAccounts = async () => {
            if (document.visibilityState !== 'visible') return;
            try {
                const response = await fetch(refreshUrl, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                if (!response.ok) throw new Error('Account refresh failed.');
                const data = await response.json();
                renderAccounts(data.accounts);
                Object.entries(data.counts).forEach(([role, count]) => {
                    const counter = document.querySelector(`[data-account-count="${role}"]`);
                    if (counter) counter.textContent = count;
                });
                status.textContent = `Live updates active · checked ${new Date().toLocaleTimeString()}.`;
            } catch {
                status.textContent = 'Live update unavailable. Reload the page to retry.';
            }
        };

        window.setInterval(refreshAccounts, 15000);
    }
});

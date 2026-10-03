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
});

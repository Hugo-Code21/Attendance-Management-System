document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.querySelector('[data-theme-toggle]');
    const themeLabel = document.querySelector('[data-theme-label]');
    const colorScheme = window.matchMedia('(prefers-color-scheme: dark)');
    const themeModes = ['system', 'light', 'dark'];

    const applyTheme = (mode) => {
        if (!themeModes.includes(mode)) {
            mode = 'system';
        }
        const isDark = mode === 'dark' || (mode === 'system' && colorScheme.matches);
        document.documentElement.dataset.themeMode = mode;
        document.documentElement.dataset.theme = isDark ? 'dark' : 'light';

        if (themeLabel && themeToggle) {
            const nextMode = themeModes[(themeModes.indexOf(mode) + 1) % themeModes.length];
            const label = mode[0].toUpperCase() + mode.slice(1);
            const nextLabel = nextMode[0].toUpperCase() + nextMode.slice(1);
            themeLabel.textContent = label;
            themeToggle.setAttribute('aria-label', `Color theme: ${label}`);
            themeToggle.title = `Color theme: ${label}. Activate to use ${nextLabel.toLowerCase()} mode.`;
        }
    };

    applyTheme(localStorage.getItem('ams-theme') || 'system');

    themeToggle?.addEventListener('click', () => {
        const currentMode = document.documentElement.dataset.themeMode || 'system';
        const nextMode = themeModes[(themeModes.indexOf(currentMode) + 1) % themeModes.length];
        localStorage.setItem('ams-theme', nextMode);
        applyTheme(nextMode);
    });

    colorScheme.addEventListener('change', () => {
        if (document.documentElement.dataset.themeMode === 'system') {
            applyTheme('system');
        }
    });

    window.addEventListener('storage', (event) => {
        if (event.key === 'ams-theme') {
            applyTheme(event.newValue || 'system');
        }
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

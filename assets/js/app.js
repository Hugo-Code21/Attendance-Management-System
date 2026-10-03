document.addEventListener('DOMContentLoaded', () => {
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

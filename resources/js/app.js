document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    const input = document.getElementById(toggle.getAttribute('aria-controls'));

    if (!input) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isPassword = input.type === 'password';

        input.type = isPassword ? 'text' : 'password';
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        toggle.setAttribute('aria-pressed', String(isPassword));
    });
});

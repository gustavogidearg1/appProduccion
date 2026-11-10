const standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
const installButton = document.querySelector('[data-install-produccion]');
const iosGuide = document.querySelector('[data-ios-produccion]');
const ios = /iPad|iPhone|iPod/.test(navigator.userAgent) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

if (!standalone && ios && iosGuide) iosGuide.hidden = false;

if (!standalone && installButton) {
    let installPrompt;
    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        installPrompt = event;
        installButton.hidden = false;
    });
    installButton.addEventListener('click', async () => {
        const prompt = installPrompt;
        if (!prompt) return;
        installPrompt = null;
        installButton.hidden = true;
        await prompt.prompt();
    });
    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        installButton.hidden = true;
    });
}

const passwordToggle = document.querySelector('[data-password-toggle]');
if (passwordToggle) {
    passwordToggle.addEventListener('click', () => {
        const password = document.getElementById('password');
        const show = password.type === 'password';
        password.type = show ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', String(show));
    });
}

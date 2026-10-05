'use strict';
(() => {
  const base = document.querySelector('meta[name="base-url"]')?.content || '';
  const button = document.querySelector('[data-install-app]');
  const hint = document.querySelector('[data-install-hint]');
  let promptEvent;
  const standalone = () => matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
  if (standalone()) {
    if (button) button.hidden = true;
    if (hint) hint.textContent = 'You’re using the Nexora app.';
  }
  window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault(); promptEvent = event;
    if (button && !standalone()) button.hidden = false;
  });
  button?.addEventListener('click', async () => {
    if (!promptEvent) return;
    button.disabled = true;
    try {
      await promptEvent.prompt();
      await promptEvent.userChoice;
    } finally {
      promptEvent = null; button.hidden = true; button.disabled = false;
    }
  });
  window.addEventListener('appinstalled', () => {
    promptEvent = null;
    if (button) button.hidden = true;
    if (hint) hint.textContent = 'Installed. Find Nexora on your home screen.';
  });
  if ('serviceWorker' in navigator && window.isSecureContext) {
    navigator.serviceWorker.register(base + '/sw.js', {scope: base + '/', updateViaCache: 'none'})
      .catch(() => { if (hint) hint.textContent = 'Refresh to finish setting up the app, or use your browser’s Add to Home screen menu.'; });
  }
})();

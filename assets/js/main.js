'use strict';
(() => {
  const base = document.querySelector('meta[name="base-url"]')?.content || '';
  const money = n => '₱' + Number(n).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const toast = document.getElementById('toast');
  let toastTimer;
  function notify(message, error = false) {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.toggle('error', error);
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 4500);
  }
  document.querySelector('.page-loader')?.remove();
  if ('IntersectionObserver' in window) {
    document.documentElement.classList.add('js-ready');
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('visible'); observer.unobserve(entry.target); }
    }), {threshold: .05});
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
  }
  const header = document.querySelector('.site-header');
  const updateHeader = () => header?.classList.toggle('scrolled', window.scrollY > 15);
  window.addEventListener('scroll', updateHeader, {passive: true}); updateHeader();
  document.querySelectorAll('.navbar .nav-link').forEach(link => {
    if (link.href === location.href) { link.classList.add('active'); link.setAttribute('aria-current', 'page'); }
  });
  const toggle = document.querySelector('[data-nav-toggle]');
  toggle?.addEventListener('click', () => {
    const open = document.getElementById('navigation').classList.toggle('open');
    toggle.setAttribute('aria-expanded', String(open));
  });
  const searchToggle = document.querySelector('[data-search-toggle]');
  const searchPanel = document.querySelector('.search-panel');
  searchToggle?.addEventListener('click', () => {
    searchPanel.hidden = !searchPanel.hidden;
    searchToggle.setAttribute('aria-expanded', String(!searchPanel.hidden));
    if (!searchPanel.hidden) document.getElementById('live-search').focus();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && searchPanel && !searchPanel.hidden) {
      searchPanel.hidden = true; searchToggle.setAttribute('aria-expanded', 'false'); searchToggle.focus();
    }
  });
  // Construct dynamic content with DOM APIs so product/customer text is never HTML.
  const el = (tag, text, className) => {
    const node = document.createElement(tag);
    if (text !== undefined) node.textContent = text;
    if (className) node.className = className;
    return node;
  };
  let searchTimer, searchAbort;
  document.getElementById('live-search')?.addEventListener('input', event => {
    clearTimeout(searchTimer); searchAbort?.abort();
    const query = event.target.value.trim();
    const output = document.querySelector('.search-results');
    output.replaceChildren();
    if (query.length < 2) return;
    searchTimer = setTimeout(async () => {
      searchAbort = new AbortController();
      output.append(el('div', undefined, 'skeleton'));
      try {
        const response = await fetch(`${base}/api.php?action=search&q=${encodeURIComponent(query)}`, {signal: searchAbort.signal});
        if (!response.ok) throw new Error('Search unavailable. Please try again.');
        const data = await response.json(); output.replaceChildren();
        data.products.forEach(product => {
          const link = el('a', undefined, 'search-result'); link.href = product.url;
          const img = el('img'); img.src = product.image; img.alt = '';
          const info = el('div'); info.append(el('strong', product.name), el('small', product.display_price));
          link.append(img, info); output.append(link);
        });
        if (!data.products.length) output.append(el('p', 'No products found. Try another search.'));
      } catch (error) { if (error.name !== 'AbortError') output.replaceChildren(el('p', error.message)); }
    }, 250);
  });
  document.querySelectorAll('[data-gallery]').forEach(button => button.addEventListener('click', () => {
    document.getElementById('main-product-image').src = button.dataset.gallery;
    document.querySelectorAll('[data-gallery]').forEach(b => b.classList.toggle('selected', b === button));
  }));
  const dialog = document.getElementById('quick-view');
  let quickAbort;
  document.querySelectorAll('[data-quick]').forEach(button => button.addEventListener('click', async () => {
    quickAbort?.abort(); quickAbort = new AbortController();
    const output = dialog.querySelector('.quick-content'); output.replaceChildren(el('div', undefined, 'skeleton'));
    dialog.showModal(); dialog.setAttribute('aria-label', 'Product quick view');
    try {
      const response = await fetch(`${base}/api.php?action=quick&id=${encodeURIComponent(button.dataset.quick)}`, {signal: quickAbort.signal});
      if (!response.ok) throw new Error('Unable to load this product.');
      const product = await response.json();
      const layout = el('div', undefined, 'quick-layout'), img = el('img'), details = el('div');
      img.src = product.image; img.alt = product.name;
      const link = el('a', 'View product & choose options ↗', 'btn btn-accent'); link.href = product.url;
      details.append(el('p', 'NEXORA EDIT', 'eyebrow'), el('h2', product.name), el('strong', product.price), el('p', product.description), el('p', `${product.stock} available`, 'stock'), link);
      layout.append(img, details); output.replaceChildren(layout);
    } catch (error) { if (error.name !== 'AbortError') output.replaceChildren(el('p', error.message)); }
  }));
  dialog?.querySelector('.close-dialog').addEventListener('click', () => dialog.close());
  dialog?.addEventListener('click', event => { if (event.target === dialog) { const rect = dialog.getBoundingClientRect(); if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close(); } });
  document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  }));
  document.querySelectorAll('form[data-ajax]').forEach(form => form.addEventListener('submit', async event => {
    event.preventDefault();
    if (form.dataset.busy) return;
    const body = new FormData(form), submitter = event.submitter;
    if (submitter?.name) body.set(submitter.name, submitter.value);
    const action = body.get('action');
    form.dataset.busy = '1';
    const buttons = Array.from(form.querySelectorAll('button')).filter(b => !b.disabled);
    buttons.forEach(b => { b.disabled = true; b.classList.add('is-loading'); });
    try {
      const response = await fetch(form.action, {method: 'POST', body, headers: {'X-Requested-With': 'XMLHttpRequest'}});
      const data = await response.json();
      if (data.redirect) { location.assign(data.redirect); return; }
      if (!response.ok || !data.ok) throw new Error(data.message || 'Unable to save. Try again.');
      document.querySelectorAll('.cart-count').forEach(node => node.textContent = data.count);
      document.querySelectorAll('[data-total]').forEach(node => node.textContent = (node.dataset.total === 'discount' ? '−' : '') + money(data.totals[node.dataset.total]));
      const cartIcon = document.querySelector('.cart-icon');
      if (cartIcon) { cartIcon.classList.remove('bump'); void cartIcon.offsetWidth; cartIcon.classList.add('bump'); }
      if (action === 'cart_update' || action === 'cart_remove') {
        const row = form.closest('[data-cart-row]');
        if (action === 'cart_remove' || Number(body.get('quantity')) === 0) row?.remove();
        else { const total = row.querySelector('.line-total'); total.textContent = money(Number(total.dataset.unit) * Number(body.get('quantity'))); }
        if (!data.count) { location.reload(); return; }
      }
      if (action === 'wishlist') {
        if (location.pathname.endsWith('/wishlist.php')) { location.reload(); return; }
        form.querySelector('.wish-btn')?.classList.toggle('saved');
      }
      notify(data.message);
    } catch (error) { notify(error instanceof SyntaxError ? 'Unable to complete the request. Please refresh and try again.' : error.message, true); }
    finally { delete form.dataset.busy; buttons.forEach(b => { b.disabled = false; b.classList.remove('is-loading'); }); }
  }));
  function paymentPreview() {
    const panel = document.getElementById('payment-demo');
    if (panel) panel.hidden = document.querySelector('[name="payment_method"]:checked')?.value === 'cod';
  }
  document.querySelectorAll('[name="payment_method"]').forEach(input => input.addEventListener('change', paymentPreview)); paymentPreview();
  document.getElementById('checkout-form')?.addEventListener('submit', event => {
    const button = event.currentTarget.querySelector('button[type="submit"]');
    button.disabled = true; button.textContent = 'Placing your order…';
  });
})();

(function () {
  'use strict';

  const body = document.body;
  const colorScheme = window.matchMedia('(prefers-color-scheme: dark)');
  const themeRoot = document.documentElement;
  const themeColor = document.querySelector('[data-six-theme-color]');
  const applyTheme = (preference) => {
    const mode = preference === 'auto' ? (colorScheme.matches ? 'dark' : 'light') : preference;
    themeRoot.dataset.theme = mode;
    themeRoot.dataset.themePreference = preference;
    if (themeColor) themeColor.content = mode === 'dark' ? '#171614' : '#f8f5ef';
    document.querySelectorAll('[data-six-theme-icon]').forEach((icon) => { icon.textContent = mode === 'dark' ? '☀' : '☾'; });
  };
  document.querySelectorAll('[data-six-theme-toggle]').forEach((button) => button.addEventListener('click', () => {
    const next = themeRoot.dataset.theme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem('noveraile-theme', next); } catch {}
    applyTheme(next);
  }));
  colorScheme.addEventListener('change', () => {
    if ((themeRoot.dataset.themePreference || 'auto') === 'auto') applyTheme('auto');
  });
  applyTheme(themeRoot.dataset.themePreference || 'auto');

  const menu = document.querySelector('.mobile-drawer-shell');
  const open = document.querySelector('[data-six-menu-open]');
  document.querySelectorAll('[data-six-menu-close]').forEach((button) => button.addEventListener('click', closeMenu));
  if (open && menu) open.addEventListener('click', () => {
    menu.classList.add('is-open');
    menu.setAttribute('aria-hidden', 'false');
    open.setAttribute('aria-expanded', 'true');
    body.classList.add('menu-open');
  });
  function closeMenu() {
    if (!menu) return;
    menu.classList.remove('is-open');
    menu.setAttribute('aria-hidden', 'true');
    if (open) open.setAttribute('aria-expanded', 'false');
    body.classList.remove('menu-open');
  }

  const heroSlides = Array.from(document.querySelectorAll('[data-six-hero-slide]'));
  const heroMessages = Array.from(document.querySelectorAll('[data-six-hero-message]'));
  const heroDots = Array.from(document.querySelectorAll('[data-six-hero-dot]'));
  if (heroSlides.length > 1) {
    let heroIndex = 0;
    let heroTimer;
    const showHero = (next) => {
      heroIndex = (next + heroSlides.length) % heroSlides.length;
      heroSlides.forEach((slide, index) => slide.classList.toggle('is-active', index === heroIndex));
      heroMessages.forEach((message, index) => message.classList.toggle('is-active', index === heroIndex));
      heroDots.forEach((dot, index) => dot.classList.toggle('is-active', index === heroIndex));
    };
    heroDots.forEach((dot, index) => dot.addEventListener('click', () => { showHero(index); restartHero(); }));
    const restartHero = () => {
      window.clearInterval(heroTimer);
      if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) heroTimer = window.setInterval(() => showHero(heroIndex + 1), 6500);
    };
    restartHero();
  }

  const searchOverlay = document.querySelector('[data-six-search]');
  const searchInput = document.querySelector('#six-site-search');
  const searchForm = document.querySelector('[data-six-search-form]');
  const suggestions = document.querySelector('[data-six-suggestions]');
  document.querySelectorAll('[data-six-search-open]').forEach((button) => button.addEventListener('click', () => {
    closeMenu();
    if (!searchOverlay) return;
    searchOverlay.classList.add('is-open'); searchOverlay.setAttribute('aria-hidden', 'false'); body.classList.add('search-open');
    window.setTimeout(() => searchInput && searchInput.focus(), 50);
  }));
  document.querySelectorAll('[data-six-search-close]').forEach((button) => button.addEventListener('click', closeSearch));
  function closeSearch() {
    if (!searchOverlay) return;
    searchOverlay.classList.remove('is-open'); searchOverlay.setAttribute('aria-hidden', 'true'); body.classList.remove('search-open');
  }

  document.querySelectorAll('[data-switcher]').forEach((container) => {
    const kind = container.dataset.switcher;
    const current = (container.dataset.current || '').toLowerCase();
    const trigger = container.querySelector('.dropdown-toggle');
    const triggerLabel = trigger && trigger.querySelector('.d-none');
    if (!kind || !current || !trigger || !triggerLabel) return;

    const originalLabel = triggerLabel.textContent.trim();
    const shortCode = kind === 'language' ? current.split('-')[0].toUpperCase() : current.toUpperCase();
    triggerLabel.textContent = shortCode;
    trigger.setAttribute('aria-label', originalLabel + ': ' + shortCode);

    container.querySelectorAll('.dropdown-item').forEach((item) => {
      const code = (item.getAttribute('href') || '').toLowerCase();
      const rawLabel = item.textContent.replace(/\s+/g, ' ').trim();
      const parts = rawLabel.split(' ');
      const markText = kind === 'currency' ? parts.shift() : code.split('-')[0].toUpperCase();
      const labelText = kind === 'currency' ? parts.join(' ') : rawLabel;
      const mark = document.createElement('span');
      const label = document.createElement('span');
      const check = document.createElement('span');
      mark.className = 'switcher-mark'; mark.textContent = markText;
      label.className = 'switcher-label'; label.textContent = labelText;
      check.className = 'switcher-check'; check.textContent = '✓'; check.setAttribute('aria-hidden', 'true');
      item.replaceChildren(mark, label, check);
      if (code === current) {
        item.classList.add('is-current');
        item.setAttribute('aria-current', 'true');
      }
    });
  });

  ['currency', 'language'].forEach((kind) => {
    const form = document.querySelector('#form-' + kind + '-mobile');
    if (!form) return;
    form.querySelectorAll('.dropdown-item').forEach((item) => item.addEventListener('click', (event) => {
      event.preventDefault();
      const code = item.getAttribute('href') || '';
      const input = form.querySelector('input[name="code"]');
      if (input && code) input.value = code;
      form.submit();
    }));
  });
  if (searchInput && searchForm && suggestions) {
    let searchTimer;
    searchInput.addEventListener('input', () => {
      window.clearTimeout(searchTimer);
      const query = searchInput.value.trim();
      if (query.length < 2) { suggestions.innerHTML = ''; return; }
      searchTimer = window.setTimeout(async () => {
        try {
          const suggestUrl = new URL(searchForm.dataset.suggestUrl, location.href);
          if (suggestUrl.origin !== location.origin) {
            suggestUrl.protocol = location.protocol;
            suggestUrl.host = location.host;
          }
          suggestUrl.searchParams.set('q', query);
          const response = await fetch(suggestUrl);
          const json = await response.json();
          suggestions.innerHTML = (json.results || []).map((item) => '<a href="' + escapeHtml(item.href) + '"><img src="' + escapeHtml(item.image) + '" alt=""><span><strong>' + escapeHtml(item.name) + '</strong><small>' + escapeHtml(item.model) + '</small></span><b>' + escapeHtml(item.price) + '</b></a>').join('');
        } catch { suggestions.innerHTML = ''; }
      }, 220);
    });
  }
  function escapeHtml(value) {
    const element = document.createElement('span'); element.textContent = String(value || ''); return element.innerHTML;
  }

  const filterPanel = document.querySelector('[data-six-filters]');
  const ajaxFilterForm = document.querySelector('[data-six-ajax-filter]');
  const catalogResults = document.querySelector('#six-catalog-results');
  function bindFilterToggle() {
    const filterToggle = document.querySelector('[data-six-filter-toggle]');
    if (filterPanel && filterToggle) filterToggle.addEventListener('click', () => filterPanel.classList.toggle('is-open'));
  }
  bindFilterToggle();

  if (ajaxFilterForm && catalogResults) {
    let filterTimer;
    const buildFormUrl = () => {
      const url = new URL(ajaxFilterForm.action, location.href);
      const formData = new FormData(ajaxFilterForm);
      for (const [key, value] of formData.entries()) {
        if (key === 'route' || value === '') url.searchParams.delete(key);
        else url.searchParams.set(key, value);
      }
      url.searchParams.delete('page');
      return url;
    };
    const loadResults = async (target, pushState = true) => {
      const browserUrl = new URL(target, location.href);
      const requestUrl = new URL(browserUrl);
      requestUrl.searchParams.set('ajax', '1');
      catalogResults.classList.add('is-loading');
      catalogResults.setAttribute('aria-busy', 'true');
      try {
        const response = await fetch(requestUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) throw new Error('Filter request failed');
        catalogResults.innerHTML = await response.text();
        if (pushState) history.pushState({ sixCatalog: true }, '', browserUrl);
        bindCatalogResults();
      } catch {
        location.assign(browserUrl);
      } finally {
        catalogResults.classList.remove('is-loading');
        catalogResults.removeAttribute('aria-busy');
      }
    };
    const bindCatalogResults = () => {
      bindFilterToggle();
      const sort = catalogResults.querySelector('[data-six-catalog-sort]');
      if (sort) sort.addEventListener('change', () => loadResults(sort.value));
      catalogResults.querySelectorAll('.pagination a, .empty-state a').forEach(link => link.addEventListener('click', event => { event.preventDefault(); loadResults(link.href); }));
    };
    ajaxFilterForm.addEventListener('submit', event => { event.preventDefault(); loadResults(buildFormUrl()); });
    ajaxFilterForm.addEventListener('change', event => {
      if (event.target.matches('input[type="radio"], select')) loadResults(buildFormUrl());
    });
    ajaxFilterForm.querySelectorAll('input[type="number"], input[type="search"]').forEach(input => input.addEventListener('input', () => {
      window.clearTimeout(filterTimer);
      filterTimer = window.setTimeout(() => loadResults(buildFormUrl()), 420);
    }));
    window.addEventListener('popstate', () => location.reload());
    bindCatalogResults();
  }

  // Product imagery: keep the preview edge-to-edge and open a real, controllable lightbox.
  const productGallery = document.querySelector('[data-six-product-gallery]');
  if (productGallery) setupProductZoom(productGallery);
  function setupProductZoom(gallery) {
    const trigger = gallery.querySelector('[data-six-zoom-open]');
    const dialog = gallery.querySelector('[data-six-zoom]');
    const stage = gallery.querySelector('[data-six-zoom-stage]');
    const image = gallery.querySelector('[data-six-zoom-image]');
    const preview = trigger && trigger.querySelector('.product-photo--detail');
    const closeButton = gallery.querySelector('[data-six-zoom-close]');
    const zoomInButton = gallery.querySelector('[data-six-zoom-in]');
    const zoomOutButton = gallery.querySelector('[data-six-zoom-out]');
    const resetButton = gallery.querySelector('[data-six-zoom-reset]');
    const level = gallery.querySelector('[data-six-zoom-level]');
    if (!trigger || !dialog || !stage || !image || !closeButton) return;

    const minScale = 1;
    const maxScale = 4;
    const zoomStep = .5;
    let scale = minScale;
    let panX = 0;
    let panY = 0;
    let dragPointer = null;
    let lastX = 0;
    let lastY = 0;
    let moved = false;
    let returnFocus = trigger;

    function clamp(value, minimum, maximum) {
      return Math.min(maximum, Math.max(minimum, value));
    }

    function applyTransform() {
      const bounds = stage.getBoundingClientRect();
      const maxX = bounds.width * (scale - 1) / 2;
      const maxY = bounds.height * (scale - 1) / 2;
      panX = clamp(panX, -maxX, maxX);
      panY = clamp(panY, -maxY, maxY);
      image.style.transform = 'translate3d(' + panX + 'px,' + panY + 'px,0) scale(' + scale + ')';
      stage.classList.toggle('is-zoomed', scale > minScale);
      if (level) level.textContent = Math.round(scale * 100) + '%';
      if (zoomOutButton) zoomOutButton.disabled = scale <= minScale;
      if (zoomInButton) zoomInButton.disabled = scale >= maxScale;
      if (resetButton) resetButton.disabled = scale <= minScale;
    }

    function setScale(nextScale, clientX, clientY) {
      const next = clamp(nextScale, minScale, maxScale);
      if (next === scale) return;
      const bounds = stage.getBoundingClientRect();
      const pointX = typeof clientX === 'number' ? clientX - bounds.left - bounds.width / 2 : 0;
      const pointY = typeof clientY === 'number' ? clientY - bounds.top - bounds.height / 2 : 0;
      const ratio = next / scale;
      panX = pointX - ratio * (pointX - panX);
      panY = pointY - ratio * (pointY - panY);
      scale = next;
      if (scale === minScale) { panX = 0; panY = 0; }
      applyTransform();
    }

    function resetZoom() {
      scale = minScale;
      panX = 0;
      panY = 0;
      applyTransform();
    }

    function openZoom(event) {
      if (event) event.preventDefault();
      returnFocus = document.activeElement || trigger;
      image.src = trigger.getAttribute('href') || image.src;
      resetZoom();
      dialog.classList.add('is-open');
      dialog.setAttribute('aria-hidden', 'false');
      body.classList.add('product-zoom-is-open');
      window.setTimeout(() => closeButton.focus(), 30);
    }

    function closeZoom() {
      dialog.classList.remove('is-open');
      dialog.setAttribute('aria-hidden', 'true');
      body.classList.remove('product-zoom-is-open');
      resetZoom();
      if (returnFocus && returnFocus.focus) returnFocus.focus();
    }

    trigger.addEventListener('click', openZoom);
    trigger.addEventListener('pointermove', (event) => {
      const bounds = trigger.getBoundingClientRect();
      trigger.style.setProperty('--zoom-x', ((event.clientX - bounds.left) / bounds.width * 100) + '%');
      trigger.style.setProperty('--zoom-y', ((event.clientY - bounds.top) / bounds.height * 100) + '%');
    });
    closeButton.addEventListener('click', closeZoom);
    dialog.addEventListener('click', (event) => { if (event.target === dialog) closeZoom(); });
    if (zoomInButton) zoomInButton.addEventListener('click', () => setScale(scale + zoomStep));
    if (zoomOutButton) zoomOutButton.addEventListener('click', () => setScale(scale - zoomStep));
    if (resetButton) resetButton.addEventListener('click', resetZoom);

    stage.addEventListener('wheel', (event) => {
      event.preventDefault();
      setScale(scale + (event.deltaY < 0 ? zoomStep : -zoomStep), event.clientX, event.clientY);
    }, { passive: false });
    stage.addEventListener('click', (event) => {
      if (moved) { moved = false; return; }
      if (scale === minScale) setScale(2.5, event.clientX, event.clientY);
    });
    stage.addEventListener('pointerdown', (event) => {
      if (scale === minScale || !event.isPrimary) return;
      dragPointer = event.pointerId;
      lastX = event.clientX;
      lastY = event.clientY;
      moved = false;
      stage.setPointerCapture(event.pointerId);
      stage.classList.add('is-dragging');
    });
    stage.addEventListener('pointermove', (event) => {
      if (dragPointer !== event.pointerId) return;
      const deltaX = event.clientX - lastX;
      const deltaY = event.clientY - lastY;
      if (Math.abs(deltaX) + Math.abs(deltaY) > 2) moved = true;
      panX += deltaX;
      panY += deltaY;
      lastX = event.clientX;
      lastY = event.clientY;
      applyTransform();
    });
    function endDrag(event) {
      if (dragPointer !== event.pointerId) return;
      dragPointer = null;
      stage.classList.remove('is-dragging');
      if (stage.hasPointerCapture(event.pointerId)) stage.releasePointerCapture(event.pointerId);
    }
    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);

    gallery.querySelectorAll('[data-six-zoom-source]').forEach((source) => source.addEventListener('click', (event) => {
      event.preventDefault();
      gallery.querySelectorAll('[data-six-zoom-source]').forEach((item) => item.removeAttribute('aria-current'));
      source.setAttribute('aria-current', 'true');
      trigger.setAttribute('href', source.getAttribute('href') || trigger.getAttribute('href'));
      if (preview && source.getAttribute('href')) preview.src = source.getAttribute('href');
    }));

    document.addEventListener('keydown', (event) => {
      if (!dialog.classList.contains('is-open')) return;
      if (event.key === 'Escape') { closeZoom(); return; }
      if (event.key === '+' || event.key === '=') { event.preventDefault(); setScale(scale + zoomStep); }
      if (event.key === '-') { event.preventDefault(); setScale(scale - zoomStep); }
      if (event.key === '0') { event.preventDefault(); resetZoom(); }
      if (event.key === 'ArrowLeft') { event.preventDefault(); panX += 40; applyTransform(); }
      if (event.key === 'ArrowRight') { event.preventDefault(); panX -= 40; applyTransform(); }
      if (event.key === 'ArrowUp') { event.preventDefault(); panY += 40; applyTransform(); }
      if (event.key === 'ArrowDown') { event.preventDefault(); panY -= 40; applyTransform(); }
      if (event.key === 'Tab') {
        const focusable = Array.from(dialog.querySelectorAll('button:not(:disabled)'));
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
      }
    });

    applyTransform();
  }

  // Give every successful add-to-bag action the same tactile, branded response.
  const pendingCartTriggers = [];
  const cartFlightTimes = new WeakMap();
  let lastQueuedTrigger = null;
  let lastQueuedAt = 0;

  function markCartTrigger(button, queueForAjax) {
    if (!button) return;
    button.classList.remove('is-sending-to-bag');
    void button.offsetWidth;
    button.classList.add('is-sending-to-bag');
    window.setTimeout(() => button.classList.remove('is-sending-to-bag'), 2400);

    if (!queueForAjax) return;
    const now = Date.now();
    if (lastQueuedTrigger === button && now - lastQueuedAt < 500) return;
    pendingCartTriggers.push(button);
    lastQueuedTrigger = button;
    lastQueuedAt = now;
  }

  function cartCountFromTotal(total) {
    const match = String(total || '').match(/^\s*(\d+)/);
    return match ? match[1] : '';
  }

  function updateCartBadge(total, trigger) {
    let count = cartCountFromTotal(total);
    if (!count) {
      const current = Number.parseInt(document.querySelector('.bag span')?.textContent || '0', 10) || 0;
      const quantity = Number.parseInt(trigger?.form?.querySelector('[name="quantity"]')?.value || '1', 10) || 1;
      count = String(current + quantity);
    }
    document.querySelectorAll('.bag span').forEach((badge) => { badge.textContent = count; });
  }

  function pulseCartTarget(target) {
    if (!target) return;
    const badge = target.querySelector('span');
    target.classList.remove('six-bag-received');
    if (badge) badge.classList.remove('six-bag-count-pulse');
    void target.offsetWidth;
    target.classList.add('six-bag-received');
    if (badge) badge.classList.add('six-bag-count-pulse');
    window.setTimeout(() => {
      target.classList.remove('six-bag-received');
      if (badge) badge.classList.remove('six-bag-count-pulse');
    }, 700);
  }

  function flyToBag(trigger, total, confirmed = true, updateBadge = true) {
    if (!trigger || !trigger.isConnected) return;
    if (confirmed) {
      if (updateBadge) updateCartBadge(total, trigger);
      trigger.classList.remove('is-sending-to-bag');
      trigger.classList.add('is-added-to-bag');
      window.setTimeout(() => trigger.classList.remove('is-added-to-bag'), 850);
    }

    const now = Date.now();
    if (now - (cartFlightTimes.get(trigger) || 0) < 1800) return;
    cartFlightTimes.set(trigger, now);

    const target = document.querySelector('.site-header .bag') || document.querySelector('.bag');
    if (!target || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      pulseCartTarget(target);
      return;
    }

    const sourceRect = trigger.getBoundingClientRect();
    const targetRect = target.getBoundingClientRect();
    const size = 64;
    const startX = sourceRect.left + sourceRect.width / 2 - size / 2;
    const startY = sourceRect.top + sourceRect.height / 2 - size / 2;
    const deltaX = targetRect.left + targetRect.width / 2 - (startX + size / 2);
    const deltaY = targetRect.top + targetRect.height / 2 - (startY + size / 2);
    const arc = Math.max(90, Math.min(240, Math.abs(deltaY) * .32));
    const flight = document.createElement('span');
    flight.className = 'six-cart-flight';
    flight.setAttribute('aria-hidden', 'true');
    flight.style.left = startX + 'px';
    flight.style.top = startY + 'px';
    flight.innerHTML = '<span class="six-cart-flight__spark six-cart-flight__spark--one"></span><span class="six-cart-flight__spark six-cart-flight__spark--two"></span><span class="six-cart-flight__bag"><span class="six-cart-flight__handle"></span><i class="six-cart-flight__item six-cart-flight__item--one"></i><i class="six-cart-flight__item six-cart-flight__item--two"></i><i class="six-cart-flight__item six-cart-flight__item--three"></i><span class="six-cart-flight__body"></span></span>';
    document.body.appendChild(flight);
    requestAnimationFrame(() => flight.classList.add('is-active'));

    const animation = flight.animate([
      { transform: 'translate3d(0,0,0) scale(.35) rotate(-8deg)', opacity: 0, offset: 0 },
      { transform: 'translate3d(0,-12px,0) scale(1.08) rotate(3deg)', opacity: 1, offset: .16 },
      { transform: 'translate3d(0,-12px,0) scale(1) rotate(0)', opacity: 1, offset: .34 },
      { transform: 'translate3d(' + (deltaX * .42) + 'px,' + (deltaY * .42 - arc) + 'px,0) scale(.86) rotate(-10deg)', opacity: 1, offset: .64 },
      { transform: 'translate3d(' + deltaX + 'px,' + deltaY + 'px,0) scale(.3) rotate(8deg)', opacity: 1, offset: .91 },
      { transform: 'translate3d(' + deltaX + 'px,' + deltaY + 'px,0) scale(.08) rotate(8deg)', opacity: 0, offset: 1 }
    ], { duration: 1300, easing: 'cubic-bezier(.2,.72,.18,1)', fill: 'forwards' });

    animation.finished.then(() => pulseCartTarget(target)).catch(() => {}).finally(() => flight.remove());
  }

  document.addEventListener('click', (event) => {
    const button = event.target.closest('button, input[type="submit"]');
    if (!button) return;
    const form = button.form;
    const action = button.getAttribute('formaction') || (form && form.getAttribute('action')) || '';
    if (button.matches('[data-six-bundle-add]')) {
      markCartTrigger(button, false);
      flyToBag(button, null, false);
    } else if (button.id === 'button-cart' || action.includes('checkout/cart.add')) {
      markCartTrigger(button, true);
      flyToBag(button, null, false);
    }
  }, true);

  document.addEventListener('submit', (event) => {
    const form = event.target;
    const button = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
    const action = (button && button.getAttribute('formaction')) || form.getAttribute('action') || '';
    if ((button && button.id === 'button-cart') || action.includes('checkout/cart.add')) markCartTrigger(button, true);
  }, true);

  if (window.jQuery) {
    window.jQuery(document).on('ajaxSuccess.sixBagFlight', function (_event, xhr, settings) {
      if (!String(settings.url || '').includes('checkout/cart.add')) return;
      let json = xhr.responseJSON;
      if (!json) {
        try { json = JSON.parse(xhr.responseText || '{}'); } catch { json = {}; }
      }
      const trigger = pendingCartTriggers.shift();
      if (json.success && trigger) flyToBag(trigger, json.total, true, trigger.id !== 'button-cart');
    });
  }

  document.querySelectorAll('[data-six-bundle-add]').forEach((button) => button.addEventListener('click', async () => {
    const form = document.querySelector('#form-product');
    const status = button.parentElement.querySelector('.six-form-status');
    if (!form) return;
    button.disabled = true;
    try {
      const cartResponse = await fetch(button.dataset.cartAction, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const cartJson = await cartResponse.json();
      if (cartJson.error) {
        const messages = typeof cartJson.error === 'string' ? [cartJson.error] : Object.values(cartJson.error);
        if (status) status.textContent = messages.join(' '); return;
      }
      const pair = new FormData(); pair.set('product_id', button.dataset.productId); pair.set('paired_id', button.dataset.pairedId);
      const bundleResponse = await fetch(button.dataset.bundleAction, { method: 'POST', body: pair, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const bundleJson = await bundleResponse.json();
      if (status) status.textContent = bundleJson.success || bundleJson.error || '';
      if (bundleJson.total) document.querySelectorAll('.bag span').forEach((element) => { element.textContent = bundleJson.total; });
      if (bundleJson.success) flyToBag(button, bundleJson.total);
    } catch { if (status) status.textContent = 'Please try again.'; }
    finally { button.disabled = false; }
  }));

  document.querySelectorAll('[data-six-ajax-form]').forEach((form) => form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const status = form.querySelector('.six-form-status') || form.parentElement.querySelector('.six-form-status');
    const button = form.querySelector('button[type="submit"]');
    if (button) button.disabled = true;
    try {
      const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await response.json();
      if (status) status.textContent = json.success || json.error || '';
      if (json.success) form.reset();
    } catch {
      if (status) status.textContent = 'Please try again.';
    } finally {
      if (button) button.disabled = false;
    }
  }));

  const rail = document.querySelector('[data-six-rail]');
  if (rail && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    let paused = false;
    rail.addEventListener('mouseenter', () => { paused = true; });
    rail.addEventListener('mouseleave', () => { paused = false; });
    window.setInterval(() => {
      if (paused || rail.scrollWidth <= rail.clientWidth) return;
      const next = rail.scrollLeft + Math.min(396, rail.clientWidth * .8);
      rail.scrollTo({ left: next >= rail.scrollWidth - rail.clientWidth - 10 ? 0 : next, behavior: 'smooth' });
    }, 4200);
  }

  const quiz = document.querySelector('[data-six-quiz]');
  if (quiz) setupQuiz(quiz);
  function setupQuiz(root) {
    const form = root.querySelector('.quiz-form');
    const steps = Array.from(form.querySelectorAll('[data-step]'));
    const next = form.querySelector('[data-quiz-next]');
    const back = form.querySelector('[data-quiz-back]');
    const progress = root.querySelector('.quiz-progress span');
    const results = root.querySelector('.quiz-results');
    let rules = {};
    try { rules = JSON.parse(root.dataset.rules || '{}'); } catch {}
    let index = 0;
    next.addEventListener('click', () => {
      const selected = steps[index].querySelector('input:checked');
      if (!selected) { steps[index].classList.add('has-error'); return; }
      steps[index].classList.remove('has-error');
      if (index === steps.length - 1) return showResults();
      steps[index].hidden = true; steps[++index].hidden = false;
      back.hidden = false; next.textContent = index === steps.length - 1 ? root.dataset.finish || 'Show my edit' : 'Continue';
      progress.style.width = ((index + 1) / steps.length * 100) + '%';
    });
    back.addEventListener('click', () => {
      if (!index) return; steps[index].hidden = true; steps[--index].hidden = false; back.hidden = index === 0; next.textContent = index === 0 ? 'Begin' : index === steps.length - 1 ? root.dataset.finish || 'Show my edit' : 'Continue'; progress.style.width = ((index + 1) / steps.length * 100) + '%';
    });
    function showResults() {
      const values = Object.fromEntries(new FormData(form).entries());
      const cards = Array.from(results.querySelectorAll('[data-tags]'));
      let shown = 0;
      cards.forEach((card) => {
        const tags = card.dataset.tags || '';
        const price = Number(card.dataset.price || 0);
        const configured = rules[values.occasion] || rules[values.occasion === 'self-purchase' ? 'self' : values.occasion] || {};
        const occasionTags = Array.isArray(configured.tags) ? configured.tags : [values.occasion];
        const occasion = tags.includes(values.occasion) || occasionTags.some((tag) => tags.includes(tag));
        const budget = price <= Number(values.budget || 999999);
        const type = !values.type || tags.includes(values.type);
        const metal = !values.metal || tags.includes(values.metal);
        const stone = !values.stone || tags.includes(values.stone);
        const visible = occasion && budget && type && metal && stone && shown < 8;
        card.hidden = !visible; if (visible) shown++;
      });
      if (!shown) cards.slice(0, 4).forEach((card) => { card.hidden = false; });
      const choice = steps[0].querySelector('input:checked + span');
      results.querySelector('[data-quiz-moment]').textContent = choice ? choice.textContent : '';
      form.hidden = true; results.hidden = false; results.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

})();

(function () {
  'use strict';

  const body = document.body;

  // On the first visit, select the closest installed storefront language from
  // the browser preferences. A manual selection always wins afterwards.
  const languageForms = Array.from(document.querySelectorAll('#form-language, #form-language-mobile'));
  if (languageForms.length) {
    languageForms.forEach((form) => form.addEventListener('click', (event) => {
      if (event.target.closest('[name="code"]')) localStorage.setItem('six-language-selected', 'manual');
    }));
    if (!localStorage.getItem('six-language-selected')) {
      const aliases = { en: 'en-gb', de: 'de-de', cs: 'cs-cz', cz: 'cs-cz', ru: 'ru-ru', uk: 'uk-ua', ua: 'uk-ua' };
      const preferred = (navigator.languages || [navigator.language || 'en']).map((value) => aliases[String(value).toLowerCase().split('-')[0]]).find(Boolean);
      const current = String(body.dataset.language || '').toLowerCase();
      localStorage.setItem('six-language-selected', 'auto');
      if (preferred && preferred !== current) {
        const control = languageForms.flatMap((form) => Array.from(form.querySelectorAll('[name="code"]'))).find((item) => String(item.value || item.getAttribute('value')).toLowerCase() === preferred);
        if (control) window.setTimeout(() => control.click(), 0);
      }
    }
  }
  const statusMessage = (value) => {
    if (!value) return '';
    if (typeof value === 'string') return value;
    if (Array.isArray(value)) return value.filter(Boolean).join(' ');
    if (typeof value === 'object') return Object.values(value).filter(Boolean).join(' ');
    return String(value);
  };
  const setFormStatus = (status, value, type = '') => {
    if (!status) return;
    const message = statusMessage(value);
    status.textContent = message;
    status.classList.toggle('is-success', Boolean(message) && type === 'success');
    status.classList.toggle('is-error', Boolean(message) && type === 'error');
    status.classList.toggle('is-info', Boolean(message) && type === 'info');
    status.setAttribute('role', type === 'error' ? 'alert' : 'status');
  };

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

  // Turn OpenCart's native rating radios into a clear, keyboard-friendly star picker.
  const reviewRating = document.querySelector('.product-reviews #input-rating');
  if (reviewRating && !reviewRating.dataset.sixEnhanced) {
    const ratingInputs = Array.from(reviewRating.querySelectorAll('input[type="radio"]'));
    const edgeLabels = Array.from(reviewRating.childNodes)
      .filter((node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim())
      .map((node) => node.textContent.trim());

    if (ratingInputs.length) {
      const bad = document.createElement('span');
      bad.className = 'six-rating-edge';
      bad.textContent = edgeLabels[0] || '';

      const stars = document.createElement('span');
      stars.className = 'six-rating-stars';
      ratingInputs.forEach((input) => {
        const choice = document.createElement('label');
        choice.className = 'six-rating-choice';
        choice.title = input.value + ' / 5';
        input.setAttribute('aria-label', input.value + ' / 5');
        const star = document.createElement('span');
        star.className = 'six-rating-star';
        star.setAttribute('aria-hidden', 'true');
        star.textContent = '★';
        choice.append(input, star);
        stars.append(choice);
      });

      const good = document.createElement('span');
      good.className = 'six-rating-edge';
      good.textContent = edgeLabels[edgeLabels.length - 1] || '';

      const value = document.createElement('output');
      value.className = 'six-rating-value';
      value.setAttribute('aria-live', 'polite');
      value.textContent = '—';
      ratingInputs.forEach((input) => input.addEventListener('change', () => { value.textContent = input.value + '/5'; }));

      reviewRating.replaceChildren(bad, stars, good, value);
      reviewRating.dataset.sixEnhanced = 'true';
    }
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

  const ajaxFilterForm = document.querySelector('[data-six-ajax-filter]');
  const catalogResults = document.querySelector('#six-catalog-results');
  let lastFilterToggle = null;
  const getFilterPanel = () => document.querySelector('[data-six-filters]');
  const setFilterPanelOpen = (open, restoreFocus = false) => {
    const panel = getFilterPanel();
    if (!panel) return;
    panel.classList.toggle('is-open', open);
    panel.setAttribute('aria-modal', open ? 'true' : 'false');
    document.body.classList.toggle('six-filters-open', open);
    document.querySelectorAll('[data-six-filter-toggle]').forEach((toggle) => toggle.setAttribute('aria-expanded', open ? 'true' : 'false'));
    if (open) {
      window.setTimeout(() => panel.querySelector('[data-six-filter-close]')?.focus(), 0);
    } else if (restoreFocus && lastFilterToggle) {
      lastFilterToggle.focus();
    }
  };
  function bindFilterToggle() {
    const open = Boolean(getFilterPanel()?.classList.contains('is-open'));
    document.querySelectorAll('[data-six-filter-toggle]').forEach((toggle) => toggle.setAttribute('aria-expanded', open ? 'true' : 'false'));
  }
  bindFilterToggle();

  document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-six-filter-toggle]');
    if (toggle) {
      event.preventDefault();
      lastFilterToggle = toggle;
      setFilterPanelOpen(!getFilterPanel()?.classList.contains('is-open'));
      return;
    }
    if (event.target.closest('[data-six-filter-close]')) {
      event.preventDefault();
      setFilterPanelOpen(false, true);
      return;
    }
    const panel = getFilterPanel();
    if (document.body.classList.contains('six-filters-open') && panel && !panel.contains(event.target)) {
      setFilterPanelOpen(false);
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.body.classList.contains('six-filters-open')) setFilterPanelOpen(false, true);
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth > 980 && document.body.classList.contains('six-filters-open')) setFilterPanelOpen(false);
  });

  document.querySelectorAll('[data-six-price-range]').forEach((range) => {
    const numberLower = range.querySelector('input[name="price_min"]');
    const numberUpper = range.querySelector('input[name="price_max"]');
    const sliderLower = range.querySelector('[data-six-price-lower]');
    const sliderUpper = range.querySelector('[data-six-price-upper]');
    const slider = range.querySelector('.price-range__slider');
    if (!numberLower || !numberUpper || !sliderLower || !sliderUpper || !slider) return;

    const floor = Number(range.dataset.min || sliderLower.min || 0);
    const ceiling = Number(range.dataset.max || sliderUpper.max || floor + 1);
    const span = Math.max(1, ceiling - floor);
    const clamp = (value) => Math.min(ceiling, Math.max(floor, Number(value)));
    const paint = () => {
      const lower = clamp(sliderLower.value);
      const upper = clamp(sliderUpper.value);
      slider.style.setProperty('--range-start', ((lower - floor) / span * 100) + '%');
      slider.style.setProperty('--range-end', ((upper - floor) / span * 100) + '%');
    };
    const publish = (input, value) => {
      input.value = String(value);
      input.dispatchEvent(new Event('input', { bubbles: true }));
    };

    sliderLower.addEventListener('input', () => {
      const value = Math.min(clamp(sliderLower.value), clamp(sliderUpper.value));
      sliderLower.value = String(value);
      publish(numberLower, value);
      paint();
    });
    sliderUpper.addEventListener('input', () => {
      const value = Math.max(clamp(sliderUpper.value), clamp(sliderLower.value));
      sliderUpper.value = String(value);
      publish(numberUpper, value);
      paint();
    });
    numberLower.addEventListener('input', () => {
      const value = numberLower.value === '' ? floor : clamp(numberLower.value);
      sliderLower.value = String(Math.min(value, clamp(sliderUpper.value)));
      paint();
    });
    numberUpper.addEventListener('input', () => {
      const value = numberUpper.value === '' ? ceiling : clamp(numberUpper.value);
      sliderUpper.value = String(Math.max(value, clamp(sliderLower.value)));
      paint();
    });
    paint();
  });

  if (ajaxFilterForm && catalogResults) {
    let filterTimer;
    const buildFormUrl = () => {
      const url = new URL(ajaxFilterForm.action, location.href);
      const formData = new FormData(ajaxFilterForm);
      for (const [key, value] of formData.entries()) {
        // `route` used to be dropped here on the assumption that the form
        // action is a keyword address that already identifies the page. The
        // catalog is an extension route and never gets a keyword, so its
        // action carries the route in the query string — deleting it asked for
        // a page with no route at all, which OpenCart answers with the store
        // front page, and pushState then wrote that into the address bar.
        if (value === '') url.searchParams.delete(key);
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
  if (productGallery) {
    setupProductZoom(productGallery);
    setupProductMedia(productGallery);
  }

  // Studio footage shares the gallery with the stills: picking a clip takes
  // over the stage, picking a photo hands it back.
  function setupProductMedia(gallery) {
    const frames = Array.from(gallery.querySelectorAll('[data-six-video]'));
    if (!frames.length) return;
    const still = gallery.querySelector('[data-six-zoom-open]');
    const thumbs = Array.from(gallery.querySelectorAll('[data-six-zoom-source], [data-six-video-source]'));

    thumbs.forEach((thumb) => thumb.addEventListener('click', () => {
      const index = thumb.getAttribute('data-six-video-source');
      frames.forEach((frame) => {
        const active = index !== null && frame.getAttribute('data-six-video') === index;
        frame.hidden = !active;
        const player = frame.querySelector('video');
        if (player && !active) player.pause();
      });
      if (still) still.hidden = index !== null;
      thumbs.forEach((item) => item.removeAttribute('aria-current'));
      thumb.setAttribute('aria-current', 'true');
      if (index === null) return;
      const player = frames[Number(index)] && frames[Number(index)].querySelector('video');
      // A click is a user gesture, so playback is allowed; ignore a refusal.
      if (player) Promise.resolve(player.play()).catch(() => {});
    }));
  }
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
    const previousButton = gallery.querySelector('[data-six-zoom-previous]');
    const nextButton = gallery.querySelector('[data-six-zoom-next]');
    const level = gallery.querySelector('[data-six-zoom-level]');
    const sources = Array.from(gallery.querySelectorAll('[data-six-zoom-source]'));
    if (!trigger || !dialog || !stage || !image || !closeButton) return;

    const minScale = 1;
    const touchViewport = window.matchMedia('(pointer: coarse)').matches || window.innerWidth <= 680;
    const maxScale = touchViewport ? 2 : 4;
    const zoomStep = touchViewport ? .25 : .5;
    let scale = minScale;
    let panX = 0;
    let panY = 0;
    let dragPointer = null;
    let lastX = 0;
    let lastY = 0;
    let moved = false;
    let returnFocus = trigger;
    let openedAt = 0;
    let currentSourceIndex = Math.max(0, sources.findIndex((source) => source.getAttribute('aria-current') === 'true'));

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

    function selectSource(index, updatePreview) {
      if (!sources.length) return;
      currentSourceIndex = (index + sources.length) % sources.length;
      const source = sources[currentSourceIndex];
      const href = source.getAttribute('href');
      sources.forEach((item) => item.removeAttribute('aria-current'));
      source.setAttribute('aria-current', 'true');
      if (href) {
        trigger.setAttribute('href', href);
        image.src = href;
        if (updatePreview && preview) preview.src = href;
      }
      resetZoom();
    }

    function openZoom(event) {
      if (event) event.preventDefault();
      returnFocus = document.activeElement || trigger;
      image.src = trigger.getAttribute('href') || image.src;
      resetZoom();
      openedAt = Date.now();
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
    if (previousButton) previousButton.addEventListener('click', () => selectSource(currentSourceIndex - 1, true));
    if (nextButton) nextButton.addEventListener('click', () => selectSource(currentSourceIndex + 1, true));

    stage.addEventListener('wheel', (event) => {
      event.preventDefault();
      setScale(scale + (event.deltaY < 0 ? zoomStep : -zoomStep), event.clientX, event.clientY);
    }, { passive: false });
    stage.addEventListener('click', (event) => {
      if (moved) { moved = false; return; }
      // A tap that opens the dialog can land on the newly visible stage in
      // iOS Safari. Never turn that touch into an automatic 250% zoom.
      if (touchViewport || Date.now() - openedAt < 400) return;
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

    sources.forEach((source, index) => source.addEventListener('click', (event) => {
      event.preventDefault();
      selectSource(index, true);
    }));

    document.addEventListener('keydown', (event) => {
      if (!dialog.classList.contains('is-open')) return;
      if (event.key === 'Escape') { closeZoom(); return; }
      if (event.key === '+' || event.key === '=') { event.preventDefault(); setScale(scale + zoomStep); }
      if (event.key === '-') { event.preventDefault(); setScale(scale - zoomStep); }
      if (event.key === '0') { event.preventDefault(); resetZoom(); }
      if (event.key === 'ArrowLeft') { event.preventDefault(); selectSource(currentSourceIndex - 1, true); }
      if (event.key === 'ArrowRight') { event.preventDefault(); selectSource(currentSourceIndex + 1, true); }
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

  function syncFloatingBag(count) {
    const quantity = Number.parseInt(String(count || '0'), 10) || 0;
    document.querySelectorAll('.floating-bag').forEach((bag) => {
      const visible = quantity > 0;
      bag.classList.toggle('is-visible', visible);
      bag.setAttribute('aria-hidden', String(!visible));
      bag.setAttribute('tabindex', visible ? '0' : '-1');
    });
  }

  const headerBagBadge = document.querySelector('.site-header .bag span');
  if (headerBagBadge) {
    syncFloatingBag(headerBagBadge.textContent);
    new MutationObserver(() => syncFloatingBag(headerBagBadge.textContent)).observe(headerBagBadge, { childList: true, characterData: true, subtree: true });
  }

  function updateCartBadge(total, trigger) {
    let count = cartCountFromTotal(total);
    if (!count) {
      const current = Number.parseInt(document.querySelector('.bag span')?.textContent || '0', 10) || 0;
      const quantity = Number.parseInt(trigger?.form?.querySelector('[name="quantity"]')?.value || '1', 10) || 1;
      count = String(current + quantity);
    }
    document.querySelectorAll('.bag span').forEach((badge) => { badge.textContent = count; });
    syncFloatingBag(count);
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
    setFormStatus(status, '');
    button.disabled = true;
    try {
      const cartResponse = await fetch(button.dataset.cartAction, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const cartJson = await cartResponse.json();
      if (cartJson.error) {
        setFormStatus(status, cartJson.error, 'error'); return;
      }
      const pair = new FormData(); pair.set('product_id', button.dataset.productId); pair.set('paired_id', button.dataset.pairedId);
      const bundleResponse = await fetch(button.dataset.bundleAction, { method: 'POST', body: pair, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const bundleJson = await bundleResponse.json();
      setFormStatus(status, bundleJson.success || bundleJson.error, bundleJson.success ? 'success' : 'error');
      if (bundleJson.total) document.querySelectorAll('.bag span').forEach((element) => { element.textContent = bundleJson.total; });
      if (bundleJson.success) flyToBag(button, bundleJson.total);
    } catch { setFormStatus(status, 'Please try again.', 'error'); }
    finally { button.disabled = false; }
  }));

  document.querySelectorAll('[data-six-ajax-form]').forEach((form) => form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const status = form.querySelector('.six-form-status') || form.parentElement.querySelector('.six-form-status');
    const button = form.querySelector('button[type="submit"]');
    setFormStatus(status, '');
    if (button) button.disabled = true;
    try {
      const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await response.json();
      setFormStatus(status, json.success || json.error, json.success ? 'success' : 'error');
      if (json.success) form.reset();
    } catch {
      setFormStatus(status, 'Please try again.', 'error');
    } finally {
      if (button) button.disabled = false;
    }
  }));

  const simpleCheckout = document.querySelector('[data-six-simple-checkout]');
  if (simpleCheckout) setupSimpleCheckout(simpleCheckout);
  function setupSimpleCheckout(root) {
    const guest = root.querySelector('#input-guest');
    const account = root.querySelector('#input-register');
    const guestChoice = guest && guest.closest('.form-check');
    const accountChoice = account && account.closest('.form-check');
    const choiceRow = guestChoice && guestChoice.parentElement === accountChoice?.parentElement ? guestChoice.parentElement : null;

    if (guestChoice && root.dataset.guestLabel) guestChoice.querySelector('label').textContent = root.dataset.guestLabel;
    if (accountChoice && root.dataset.accountLabel) accountChoice.querySelector('label').textContent = root.dataset.accountLabel;
    if (choiceRow) {
      choiceRow.classList.add('checkout-page-account-choice');
      choiceRow.insertBefore(guestChoice, accountChoice);
    }

    // Keep account creation available, but make the shortest checkout the default.
    if (guest && account?.checked) guest.click();

    const registerButton = root.querySelector('#button-register');
    if (registerButton && root.dataset.continueLabel) registerButton.textContent = root.dataset.continueLabel;

    const autocomplete = {
      firstname: 'given-name', lastname: 'family-name', email: 'email',
      shipping_company: 'organization', shipping_address_1: 'address-line1',
      shipping_address_2: 'address-line2', shipping_city: 'address-level2',
      shipping_postcode: 'postal-code', shipping_country_id: 'country', shipping_zone_id: 'address-level1'
    };
    Object.entries(autocomplete).forEach(([name, value]) => {
      const field = root.querySelector(`[name="${name}"]`);
      if (field) field.setAttribute('autocomplete', value);
    });

    const optionalInputs = ['shipping_company', 'shipping_address_2']
      .map((name) => root.querySelector(`[name="${name}"]`))
      .filter(Boolean);
    const optionalFields = optionalInputs.map((input) => input.closest('.col')).filter(Boolean);
    const optionalRow = optionalFields[0]?.parentElement;
    if (optionalFields.length && optionalRow && root.dataset.optionalLabel) {
      const toggleCell = document.createElement('div');
      const toggle = document.createElement('button');
      const initiallyOpen = optionalInputs.some((input) => String(input.value || '').trim());
      toggleCell.className = 'col-12 checkout-page-optional-cell';
      toggle.className = 'checkout-page-optional-toggle';
      toggle.type = 'button';
      toggle.textContent = root.dataset.optionalLabel;
      toggle.setAttribute('aria-expanded', String(initiallyOpen));
      optionalFields.forEach((field) => { field.hidden = !initiallyOpen; });
      toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(open));
        optionalFields.forEach((field) => { field.hidden = !open; });
        if (open) optionalInputs[0]?.focus();
      });
      toggleCell.appendChild(toggle);
      optionalRow.insertBefore(toggleCell, optionalFields[0]);
    }

    const note = root.querySelector('#checkout-payment-method .mb-2');
    if (note && root.dataset.noteLabel) {
      const details = document.createElement('details');
      const summary = document.createElement('summary');
      details.className = 'checkout-page-note';
      summary.textContent = root.dataset.noteLabel;
      details.appendChild(summary);
      note.parentElement.insertBefore(details, note);
      details.appendChild(note);
    }

    const openNextChoice = (selector) => {
      const button = root.querySelector(selector);
      if (!button || button.disabled) return;
      button.scrollIntoView({ behavior: 'smooth', block: 'center' });
      window.setTimeout(() => button.click(), 260);
    };
    if (window.jQuery) {
      window.jQuery(document).on('ajaxSuccess.sixSimpleCheckout', function (_event, xhr, settings) {
        const url = String(settings.url || '');
        let json = xhr.responseJSON;
        if (!json) {
          try { json = JSON.parse(xhr.responseText || '{}'); } catch { json = {}; }
        }
        if (!json.success) return;
        if (url.includes('checkout/register.save') || url.includes('checkout/shipping_address.save')) {
          openNextChoice('#button-shipping-methods');
        } else if (url.includes('checkout/shipping_method.save')) {
          openNextChoice('#button-payment-methods');
        }
      });
    }
  }

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
  const TYPE_TAGS = {
    ring: ['ring', 'rings'],
    earring: ['earring', 'earrings', 'ear_ring'],
    necklace: ['necklace', 'necklaces'],
    bracelet: ['bracelet', 'bracelets']
  };
  function setupQuiz(root) {
    const form = root.querySelector('.quiz-form');
    const steps = Array.from(form.querySelectorAll('[data-step]'));
    const next = form.querySelector('[data-quiz-next]');
    const back = form.querySelector('[data-quiz-back]');
    const progress = root.querySelector('.quiz-progress span');
    const progressSteps = Array.from(root.querySelectorAll('.quiz-progress i'));
    const current = root.querySelector('[data-quiz-current]');
    const error = root.querySelector('[data-quiz-error]');
    const results = root.querySelector('.quiz-results');
    let rules = {};
    try { rules = JSON.parse(root.dataset.rules || '{}'); } catch {}
    let index = 0;
    updateProgress();
    form.addEventListener('change', () => { error.classList.remove('is-visible'); });
    next.addEventListener('click', () => {
      const selected = steps[index].querySelector('input:checked');
      if (!selected) { steps[index].classList.add('has-error'); error.classList.add('is-visible'); return; }
      steps[index].classList.remove('has-error');
      error.classList.remove('is-visible');
      if (index === steps.length - 1) return showResults();
      steps[index].hidden = true; steps[++index].hidden = false;
      back.hidden = false; next.querySelector('span').textContent = index === steps.length - 1 ? root.dataset.finish || 'Show my edit' : root.dataset.next || 'Continue';
      updateProgress();
      steps[index].querySelector('input:checked')?.focus();
    });
    back.addEventListener('click', () => {
      if (!index) return; steps[index].hidden = true; steps[--index].hidden = false; back.hidden = index === 0; next.querySelector('span').textContent = index === 0 ? root.dataset.start || 'Begin' : index === steps.length - 1 ? root.dataset.finish || 'Show my edit' : root.dataset.next || 'Continue'; error.classList.remove('is-visible'); updateProgress();
    });
    function updateProgress() {
      progress.style.width = ((index + 1) / steps.length * 100) + '%';
      current.textContent = String(index + 1).padStart(2, '0');
      progressSteps.forEach((step, stepIndex) => step.classList.toggle('is-active', stepIndex <= index));
    }
    function showResults() {
      const values = Object.fromEntries(new FormData(form).entries());
      const cards = Array.from(results.querySelectorAll('[data-tags]'));
      const [budgetMin, budgetMax] = String(values.budget || '0:999999').split(':').map(Number);
      const ranked = cards.map((card, order) => {
        const tags = card.dataset.tags || '';
        const price = Number(card.dataset.price || 0);
        const configured = rules[values.occasion] || rules[values.occasion === 'self-purchase' ? 'self' : values.occasion] || {};
        const occasionTags = Array.isArray(configured.tags) ? configured.tags : [values.occasion];
        const occasion = tags.includes(values.occasion) || occasionTags.some((tag) => tags.includes(tag));
        const budget = price >= (budgetMin || 0) && price <= (budgetMax || 999999);
        // Matched against whole tags rather than as substrings: "earrings"
        // contains "ring", so a request for rings also answered with studs.
        const tagList = tags.split(',').map((tag) => tag.trim());
        const type = !values.type || (TYPE_TAGS[values.type] || [values.type]).some((tag) => tagList.includes(tag));
        const metal = !values.metal || tags.includes(values.metal);
        const stone = !values.stone || tags.includes(values.stone);
        const score = (occasion ? 16 : 0) + (budget ? 8 : 0) + (type ? 4 : 0) + (metal ? 2 : 0) + (stone ? 1 : 0);
        return { card, order, score, occasion, budget, type, metal, stone, exact: occasion && budget && type && metal && stone };
      });
      const exact = ranked.filter((item) => item.exact).sort((a, b) => b.score - a.score || a.order - b.order);
      const selected = exact.slice(0, 8);
      if (selected.length < Math.min(4, cards.length)) {
        ranked.filter((item) => !selected.includes(item) && item.type && item.metal && item.stone).sort((a, b) => b.score - a.score || a.order - b.order).slice(0, Math.min(4, cards.length) - selected.length).forEach((item) => selected.push(item));
      }
      cards.forEach((card) => { card.hidden = !selected.some((item) => item.card === card); });
      const choice = steps[0].querySelector('input:checked + span');
      results.querySelector('[data-quiz-moment]').textContent = choice ? choice.textContent : '';
      form.hidden = true; results.hidden = false; results.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

})();

(function () {
  'use strict';

  const body = document.body;
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
        } catch (_) { suggestions.innerHTML = ''; }
      }, 220);
    });
  }
  function escapeHtml(value) {
    const element = document.createElement('span'); element.textContent = String(value || ''); return element.innerHTML;
  }

  const filterPanel = document.querySelector('[data-six-filters]');
  const filterToggle = document.querySelector('[data-six-filter-toggle]');
  if (filterPanel && filterToggle) filterToggle.addEventListener('click', () => filterPanel.classList.toggle('is-open'));

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
    } catch (_) { if (status) status.textContent = 'Please try again.'; }
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
    } catch (_) {
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
    try { rules = JSON.parse(root.dataset.rules || '{}'); } catch (_) {}
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

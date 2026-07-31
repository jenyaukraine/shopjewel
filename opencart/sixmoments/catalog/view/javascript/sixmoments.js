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
    let index = 0;
    next.addEventListener('click', () => {
      const selected = steps[index].querySelector('input:checked');
      if (!selected) { steps[index].classList.add('has-error'); return; }
      steps[index].classList.remove('has-error');
      if (index === steps.length - 1) return showResults();
      steps[index].hidden = true; steps[++index].hidden = false;
      back.hidden = false; next.textContent = index === steps.length - 1 ? root.querySelector('[data-quiz-moment]')?.dataset.finish || 'Show my edit' : 'Continue';
      progress.style.width = ((index + 1) / steps.length * 100) + '%';
    });
    back.addEventListener('click', () => {
      if (!index) return; steps[index].hidden = true; steps[--index].hidden = false; back.hidden = index === 0; next.textContent = index === 0 ? 'Begin' : 'Continue'; progress.style.width = ((index + 1) / steps.length * 100) + '%';
    });
    function showResults() {
      const values = Object.fromEntries(new FormData(form).entries());
      const cards = Array.from(results.querySelectorAll('[data-tags]'));
      let shown = 0;
      cards.forEach((card) => {
        const tags = card.dataset.tags || '';
        const price = Number(card.dataset.price || 0);
        const occasion = tags.includes(values.occasion);
        const budget = price <= Number(values.budget || 999999);
        const stone = !values.stone || tags.includes(values.stone);
        const visible = occasion && budget && stone && shown < 8;
        card.hidden = !visible; if (visible) shown++;
      });
      if (!shown) cards.slice(0, 4).forEach((card) => { card.hidden = false; });
      const choice = steps[0].querySelector('input:checked + span');
      results.querySelector('[data-quiz-moment]').textContent = choice ? choice.textContent : '';
      form.hidden = true; results.hidden = false; results.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  try {
    if (!localStorage.getItem('sixmoments-language-selected')) {
      localStorage.setItem('sixmoments-language-selected', '1');
      const current = body.dataset.language || 'en-gb';
      const browser = (navigator.language || '').toLowerCase();
      const match = browser.startsWith('de') ? 'de-de' : browser.startsWith('cs') ? 'cs-cz' : browser.startsWith('ru') ? 'ru-ru' : browser.startsWith('uk') ? 'uk-ua' : 'en-gb';
      if (current === 'en-gb' && match !== current) {
        const url = new URL(location.href); url.searchParams.set('language', match); location.replace(url.toString());
      }
    }
  } catch (_) {}
})();

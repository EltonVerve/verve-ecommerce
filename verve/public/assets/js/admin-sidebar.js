(() => {
  const sidebar = document.querySelector('.admin-sidebar');
  const key = document.currentScript?.dataset.storageKey;
  if (!sidebar || !key) return;

  let savedTop = 0;
  try {
    savedTop = Number(sessionStorage.getItem(key)) || 0;
  } catch (_) {
    // Navigation still works when browser storage is unavailable.
  }

  let interacted = false;
  const restore = () => {
    if (!interacted && Number.isFinite(savedTop)) sidebar.scrollTop = savedTop;
  };
  const save = () => {
    // On narrow screens the sidebar is part of the page, not a scroll panel.
    if (sidebar.scrollHeight <= sidebar.clientHeight) return;
    try {
      sessionStorage.setItem(key, String(sidebar.scrollTop));
    } catch (_) {}
  };

  // Run directly after the sidebar markup, before the new page is painted.
  restore();
  window.addEventListener('load', restore, { once: true });
  ['wheel', 'touchstart', 'pointerdown', 'keydown'].forEach((event) => {
    sidebar.addEventListener(event, () => { interacted = true; }, { passive: true });
  });
  sidebar.addEventListener('click', save);
  window.addEventListener('pagehide', save);
})();

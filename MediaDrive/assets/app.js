(function () {
  const key = "mediad_drive_theme";
  const root = document.documentElement;

  function apply(theme) {
    if (!theme) {
      root.removeAttribute("data-theme");
      return;
    }
    root.setAttribute("data-theme", theme);
  }

  // init: localStorage > system preference
  const saved = localStorage.getItem(key);
  if (saved) {
    apply(saved);
  } else {
    const prefersLight = window.matchMedia && window.matchMedia("(prefers-color-scheme: light)").matches;
    apply(prefersLight ? "light" : null);
  }

  const btn = document.getElementById("themeToggle");
  if (btn) {
    btn.addEventListener("click", () => {
      const cur = root.getAttribute("data-theme");
      const next = cur === "light" ? null : "light";
      if (next) localStorage.setItem(key, next);
      else localStorage.removeItem(key);
      apply(next);
    });
  }
})();

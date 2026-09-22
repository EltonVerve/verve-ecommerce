/* =========================================================
   RADA CART — main.js
   Mobile nav toggle, live header search suggestions, quantity
   steppers, product gallery thumbnails, wishlist toggling and
   a small toast helper used after add-to-cart actions.
   ========================================================= */
(function () {
  "use strict";
  const carousel = document.querySelector("[data-carousel]");
  if (carousel) {
    const slides = [...carousel.querySelectorAll(".carousel-slide")];
    const dots = [...carousel.querySelectorAll("[data-slide]")];
    const pause = carousel.querySelector("[data-pause]");
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    let current = 0, timer;
    let paused = reducedMotion.matches;
    let hovered = false;
    const schedule = () => {
      clearTimeout(timer);
      if (!paused && !hovered && !document.hidden && !carousel.contains(document.activeElement)) {
        timer = setTimeout(() => show(current + 1, false), 6500);
      }
    };
    const show = (index, announce = true) => {
      current = (index + slides.length) % slides.length;
      slides.forEach((slide, i) => { slide.hidden = i !== current; });
      dots.forEach((dot, i) => {
        if (i === current) dot.setAttribute("aria-current", "true");
        else dot.removeAttribute("aria-current");
      });
      if (announce) carousel.querySelector(".carousel-status").textContent = slides[current].getAttribute("aria-label");
      schedule();
    };
    const updatePause = () => { pause.textContent = paused ? "Play slideshow" : "Pause slideshow"; };
    carousel.querySelector(".carousel-controls").hidden = false;
    carousel.querySelector("[data-prev]").addEventListener("click", () => show(current - 1));
    carousel.querySelector("[data-next]").addEventListener("click", () => show(current + 1));
    dots.forEach((dot, i) => dot.addEventListener("click", () => show(i)));
    pause.addEventListener("click", () => { paused = !paused; updatePause(); schedule(); });
    carousel.addEventListener("mouseenter", () => { hovered = true; schedule(); });
    carousel.addEventListener("mouseleave", () => { hovered = false; schedule(); });
    carousel.addEventListener("focusin", schedule);
    carousel.addEventListener("focusout", () => setTimeout(schedule, 0));
    document.addEventListener("visibilitychange", schedule);
    reducedMotion.addEventListener("change", () => { paused = reducedMotion.matches; updatePause(); schedule(); });
    let touchStart;
    carousel.addEventListener("touchstart", (event) => { touchStart = event.touches[0]; clearTimeout(timer); }, { passive: true });
    carousel.addEventListener("touchend", (event) => {
      if (!touchStart) return;
      const dx = event.changedTouches[0].clientX - touchStart.clientX;
      const dy = event.changedTouches[0].clientY - touchStart.clientY;
      if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy)) show(current + (dx < 0 ? 1 : -1));
      else schedule();
      touchStart = null;
    }, { passive: true });
    updatePause();
    schedule();
  }
  const reviewComposer = document.getElementById("write-review");
  const reviewButton = document.getElementById("open-review-form");
  if (reviewComposer) {
    const revealReview = () => {
      if (window.location.hash === "#write-review") reviewComposer.open = true;
    };
    revealReview();
    window.addEventListener("hashchange", revealReview);
    reviewButton?.addEventListener("click", () => {
      reviewComposer.open = true;
      reviewComposer.querySelector("input[name='author_name']")?.focus({ preventScroll: true });
    });
  }

  const categoryMenu = document.getElementById("categoryMenu");
  if (categoryMenu) {
    const closeCategories = () => { categoryMenu.open = false; };
    document.addEventListener("click", (event) => {
      if (!categoryMenu.contains(event.target)) closeCategories();
    });
    document.addEventListener("focusin", (event) => {
      if (!categoryMenu.contains(event.target)) closeCategories();
    });
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && categoryMenu.open) {
        closeCategories();
        categoryMenu.querySelector("summary").focus();
      }
    });
    window.addEventListener("resize", () => {
      if (window.innerWidth <= 980) closeCategories();
    });
  }

  /* ---------- Mobile nav toggle ---------- */
  const navToggle = document.getElementById("navToggle");
  const mobileNav = document.getElementById("mobileNav");
  if (navToggle && mobileNav) {
    const closeMobileNav = () => {
      navToggle.setAttribute("aria-expanded", "false");
      navToggle.setAttribute("aria-label", "Open menu");
      mobileNav.hidden = true;
    };

    const syncMobileNavState = () => {
      if (window.innerWidth > 980) {
        closeMobileNav();
      } else if (mobileNav.hidden && navToggle.getAttribute("aria-expanded") === "true") {
        navToggle.setAttribute("aria-expanded", "false");
      }
    };

    navToggle.addEventListener("click", () => {
      const isOpen = navToggle.getAttribute("aria-expanded") === "true";
      navToggle.setAttribute("aria-expanded", String(!isOpen));
      navToggle.setAttribute("aria-label", isOpen ? "Open menu" : "Close menu");
      mobileNav.hidden = isOpen;
    });

    mobileNav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", closeMobileNav);
    });

    document.addEventListener("click", (event) => {
      if (!mobileNav.hidden && !mobileNav.contains(event.target) && !navToggle.contains(event.target)) {
        closeMobileNav();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !mobileNav.hidden) {
        closeMobileNav();
        navToggle.focus();
      }
    });
    window.addEventListener("resize", syncMobileNavState);
    syncMobileNavState();
  }

  /* ---------- Toast helper ---------- */
  window.showToast = function (message) {
    let toast = document.getElementById("radaToast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "radaToast";
      toast.style.cssText =
        "position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(10px);" +
        "background:#1C1A17;color:#fff;font-size:14px;font-weight:600;padding:.8rem 1.4rem;" +
        "border-radius:999px;box-shadow:0 10px 30px rgba(0,0,0,.25);z-index:999;opacity:0;" +
        "transition:opacity .25s, transform .25s;pointer-events:none;";
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.style.opacity = "1";
    toast.style.transform = "translateX(-50%) translateY(0)";
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => {
      toast.style.opacity = "0";
      toast.style.transform = "translateX(-50%) translateY(10px)";
    }, 2200);
  };

  window.bumpCartBadge = function (newCount) {
    const badge = document.querySelector(".cart-badge");
    const link = document.querySelector(".cart-link");
    if (!link) return;
    link.setAttribute("aria-label", `Cart, ${newCount} items`);
      if (badge) {
        badge.textContent = newCount;
      } else {
        const span = document.createElement("span");
        span.className = "cart-badge";
        span.textContent = newCount;
        link.appendChild(span);
      }
  };

  /* ---------- Quantity steppers ---------- */
  document.querySelectorAll(".qty-stepper").forEach((stepper) => {
    const input = stepper.querySelector('input[type="number"]');
    const max = parseInt(input?.max || "999", 10);
    stepper.querySelectorAll("button").forEach((btn) => {
      btn.addEventListener("click", (event) => {
        if (btn.type === "submit") return;
        event.preventDefault();
        if (!input) return;
        let value = parseInt(input.value || "1", 10) || 1;
        value += btn.dataset.step === "down" ? -1 : 1;
        value = Math.max(1, Math.min(max, value));
        input.value = value;
        input.dispatchEvent(new Event("change", { bubbles: true }));
      });
    });
  });

  /* ---------- Product gallery thumbnails ---------- */
  const mainImg = document.querySelector("[data-gallery-main]");
  document.querySelectorAll("[data-gallery-thumb]").forEach((thumb) => {
    thumb.addEventListener("click", () => {
      if (!mainImg) return;
      mainImg.src = thumb.dataset.galleryThumb;
      document.querySelectorAll("[data-gallery-thumb]").forEach((t) => t.classList.remove("active"));
      thumb.classList.add("active");
    });
  });
})();

/* ---------- Header live search suggestions ---------- */
(function () {
  "use strict";
  const input = document.getElementById("headerSearchInput");
  const panel = document.getElementById("searchSuggestions");
  if (!input || !panel || !window.fetch) return;

  let timer;
  let controller;

  function render(products) {
    panel.innerHTML = "";
    if (!products.length) {
      panel.hidden = true;
      return;
    }
    products.forEach((p) => {
      const a = document.createElement("a");
      a.href = p.url;
      a.innerHTML = `
        <img src="${p.image}" alt="">
        <span>
          <span class="sugg-name">${p.name}</span><br>
          <span class="sugg-meta">${p.category}</span>
        </span>
        <span class="sugg-price">${p.unavailable ? "Out of stock" : p.price}</span>
      `;
      panel.appendChild(a);
    });
    panel.hidden = false;
  }

  function search() {
    const q = input.value.trim();
    if (q.length < 2) {
      panel.hidden = true;
      return;
    }
    clearTimeout(timer);
    timer = setTimeout(async () => {
      if (controller) controller.abort();
      controller = new AbortController();
      try {
        const url = new URL(input.form.dataset.suggestUrl || (window.BASE_URL + "/pages/search-suggestions.php"), window.location.href);
        url.searchParams.set("q", q);
        const res = await fetch(url, { signal: controller.signal, headers: { Accept: "application/json" } });
        const data = await res.json();
        render(Array.isArray(data.products) ? data.products : []);
      } catch (e) {
        /* silently ignore — the normal form submit still works */
      }
    }, 180);
  }

  input.addEventListener("input", search);
  input.addEventListener("focus", search);
  document.addEventListener("pointerdown", (e) => {
    if (!input.form.contains(e.target)) panel.hidden = true;
  });
})();

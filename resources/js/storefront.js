/* ============================================================
   Nischoyota — storefront UI interactions (drawers, modals, tabs, toasts…)
   ============================================================ */
(function () {
  "use strict";

  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

  /* ---------- Toasts ---------- */
  var ICONS = {
    success: '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg>',
    danger: '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
    info: '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8h.01M12 11v5"/></svg>'
  };

  function toast(message, type) {
    type = type || "success";
    var root = $("#toast-root");
    if (!root) {
      root = document.createElement("div");
      root.id = "toast-root";
      root.className = "toast-root";
      document.body.appendChild(root);
    }
    var el = document.createElement("div");
    el.className = "toast" + (type !== "success" ? " toast-" + type : "");
    el.setAttribute("role", "status");
    el.innerHTML = ICONS[type] + "<span></span>";
    el.querySelector("span").textContent = message;
    root.appendChild(el);
    setTimeout(function () {
      el.classList.add("hide");
      setTimeout(function () { el.remove(); }, 300);
    }, 2600);
  }
  window.dmToast = toast;

  /* Flashed session messages rendered by <x-flash-toasts> */
  $$("[data-flash-toast]").forEach(function (el) {
    toast(el.getAttribute("data-flash-toast"), el.getAttribute("data-flash-toast-type"));
  });

  /* ---------- Overlay helper ---------- */
  function getOverlay() {
    var ov = $("#page-overlay");
    if (!ov) {
      ov = document.createElement("div");
      ov.id = "page-overlay";
      ov.className = "overlay";
      document.body.appendChild(ov);
    }
    return ov;
  }

  var openDrawer = null;

  function showDrawer(drawer) {
    closeDrawer();
    var ov = getOverlay();
    drawer.classList.add("show");
    drawer.setAttribute("aria-hidden", "false");
    ov.classList.add("show");
    document.body.style.overflow = "hidden";
    openDrawer = drawer;
  }

  function closeDrawer() {
    if (!openDrawer) return;
    openDrawer.classList.remove("show");
    openDrawer.setAttribute("aria-hidden", "true");
    getOverlay().classList.remove("show");
    document.body.style.overflow = "";
    openDrawer = null;
  }

  /* ---------- Modals ---------- */
  function openModal(id) {
    var m = document.getElementById(id);
    if (!m) return;
    m.classList.add("show");
    m.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }
  function closeModal(m) {
    m.classList.remove("show");
    m.setAttribute("aria-hidden", "true");
    if (!openDrawer) document.body.style.overflow = "";
  }
  window.dmOpenModal = openModal;

  /* ---------- Cart (F-021) and wishlist (F-022) ---------- */
  /* Sets every badge of a kind ("cart", "wishlist"); adds one to each [data-{kind}-badge-host] when it was 0. */
  function setBadgeCount(kind, count) {
    $$("[data-" + kind + "-badge-host]").forEach(function (host) {
      var badge = $("[data-" + kind + "-count]", host);
      if (!badge && count > 0) {
        badge = document.createElement("span");
        badge.className = "count-badge";
        badge.setAttribute("data-" + kind + "-count", "");
        host.appendChild(badge);
      }
      if (badge) {
        badge.textContent = count;
        badge.hidden = count <= 0;
      }
    });
  }

  /* The page's CSRF token is stale (session expired): reload for a fresh one instead of showing the 419 page. */
  function reloadAfterExpiredSession() {
    toast("Your session expired. Reloading the page…", "info");
    setTimeout(function () { window.location.reload(); }, 1200);
  }

  /* Quantity forms in the cart page and drawer submit shortly after the last stepper click. */
  var qtyTimer = null;
  function submitQtyForm(form) {
    if (!form) return;
    clearTimeout(qtyTimer);
    qtyTimer = setTimeout(function () { form.requestSubmit(); }, 500);
  }
  document.addEventListener("change", function (e) {
    var form = e.target.closest("form[data-qty-submit]");
    if (form) submitQtyForm(form);
  });
  /* Enter submits right away: drop a pending timed submit so the form is not sent twice. */
  document.addEventListener("submit", function (e) {
    if (e.target.closest("form[data-qty-submit]")) clearTimeout(qtyTimer);
  }, true);

  /* Add to Cart forms post with fetch and swap in the new drawer; Buy Now and failures fall back to a normal submit. */
  document.addEventListener("submit", function (e) {
    var form = e.target.closest("form[data-cart-add]");
    if (!form || !window.fetch || (e.submitter && e.submitter.name === "buy_now")) return;
    e.preventDefault();
    var button = e.submitter;
    if (button) button.disabled = true;
    fetch(form.action, {
      method: "POST",
      body: new FormData(form),
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }
    })
      .then(function (response) {
        if (response.status === 419) { reloadAfterExpiredSession(); return { ok: true, expired: true, body: {} }; }
        return response.json().then(function (body) { return { ok: response.ok, status: response.status, body: body }; });
      })
      .then(function (result) {
        if (!result.ok) {
          if (result.status === 422 && result.body.message) { toast(result.body.message, "danger"); return; }
          if (result.status === 429) { toast("Too many tries. Please wait a moment.", "danger"); return; }
          form.submit();
          return;
        }
        if (result.expired) return;
        setBadgeCount("cart", result.body.count);
        var drawer = $("#cart-drawer");
        if (drawer && result.body.drawer) drawer.outerHTML = result.body.drawer;
        toast(result.body.message);
      })
      .catch(function () { form.submit(); })
      .finally(function () { if (button) button.disabled = false; });
  });

  /* Wishlist hearts post with fetch; guests (401) go to the login page. On /wishlist a removed card disappears. */
  document.addEventListener("submit", function (e) {
    var form = e.target.closest("form[data-wishlist-form]");
    if (!form || !window.fetch) return;
    e.preventDefault();
    var heart = e.submitter || $("[data-wishlist]", form);
    fetch(form.action, {
      method: "POST",
      body: new FormData(form),
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }
    })
      .then(function (response) {
        if (response.status === 401) { window.location.href = form.getAttribute("data-login-url"); return null; }
        if (response.status === 419) { reloadAfterExpiredSession(); return null; }
        return response.json().then(function (body) { return { ok: response.ok, body: body }; });
      })
      .then(function (result) {
        if (!result) return;
        if (!result.ok) { toast(result.body.message || "Something went wrong. Please try again.", "danger"); return; }
        heart.classList.toggle("active", result.body.isSaved);
        heart.setAttribute("aria-pressed", result.body.isSaved ? "true" : "false");
        setBadgeCount("wishlist", result.body.count);
        toast(result.body.message, result.body.isSaved ? "success" : "info");
        var page = form.closest("[data-wishlist-page]");
        if (page && !result.body.isSaved) {
          form.closest(".product-card").remove();
          if (!$(".product-card", page)) window.location.reload();
        }
      })
      .catch(function () { form.submit(); });
  });

  /* ---------- Wishlist badge counter (visual only, prototype pages) ---------- */
  function bumpBadge(sel, delta) {
    $$(sel).forEach(function (b) {
      var n = parseInt(b.textContent, 10) || 0;
      b.textContent = Math.max(0, n + delta);
    });
  }

  /* ---------- Global click delegation ---------- */
  document.addEventListener("click", function (e) {
    var t;

    /* Drawer openers: data-drawer="cart-drawer" etc. */
    t = e.target.closest("[data-drawer]");
    if (t) {
      e.preventDefault();
      var d = document.getElementById(t.getAttribute("data-drawer"));
      if (d) showDrawer(d);
      return;
    }

    /* Drawer / overlay closers */
    if (e.target.closest("[data-drawer-close]") || e.target.id === "page-overlay") {
      closeDrawer();
      return;
    }

    /* Modal openers: data-modal="modal-quick-view" */
    t = e.target.closest("[data-modal]");
    if (t) {
      e.preventDefault();
      var confirmForm = t.hasAttribute("data-modal-action") && $("#" + t.getAttribute("data-modal") + " [data-confirm-form]");
      if (confirmForm) confirmForm.setAttribute("action", t.getAttribute("data-modal-action"));
      openModal(t.getAttribute("data-modal"));
      return;
    }

    /* Modal closers */
    t = e.target.closest("[data-modal-close]");
    if (t) { closeModal(t.closest(".modal-root")); return; }
    if (e.target.classList.contains("modal-backdrop")) { closeModal(e.target.closest(".modal-root")); return; }

    /* Add to cart (visual, prototype pages only; real forms are handled by form[data-cart-add] below) */
    t = e.target.closest("[data-add-to-cart]");
    if (t && !t.disabled && !t.closest("form[data-cart-add]")) {
      e.preventDefault();
      bumpBadge("[data-cart-count]", 1);
      toast("Product added to cart");
      return;
    }

    /* Wishlist toggle (visual, prototype pages only; real hearts submit a form[data-wishlist-form]) */
    t = e.target.closest("[data-wishlist]");
    if (t && !t.closest("form[data-wishlist-form]") && !t.hasAttribute("form")) {
      e.preventDefault();
      t.classList.toggle("active");
      var added = t.classList.contains("active");
      t.setAttribute("aria-pressed", added ? "true" : "false");
      bumpBadge("[data-wishlist-count]", added ? 1 : -1);
      toast(added ? "Added to wishlist" : "Removed from wishlist", added ? "success" : "info");
      return;
    }

    /* Remove row/item (visual): data-remove="selector-of-parent" */
    t = e.target.closest("[data-remove]");
    if (t) {
      e.preventDefault();
      var row = t.closest(t.getAttribute("data-remove") || ".cart-row");
      if (row) row.remove();
      bumpBadge("[data-cart-count]", -1);
      toast("Removed from cart", "info");
      return;
    }

    /* Quantity steppers */
    t = e.target.closest("[data-qty-minus], [data-qty-plus]");
    if (t) {
      var wrap = t.closest(".qty-control");
      var input = wrap && wrap.querySelector("input");
      if (input) {
        var v = parseInt(input.value, 10) || 1;
        v += t.hasAttribute("data-qty-plus") ? 1 : -1;
        input.value = Math.min(99, Math.max(1, v));
        submitQtyForm(wrap.closest("form[data-qty-submit]"));
      }
      return;
    }

    /* Tabs */
    t = e.target.closest(".tab-btn");
    if (t) {
      var tabs = t.closest(".tabs");
      $$(".tab-btn", tabs).forEach(function (b) { b.classList.remove("active"); b.setAttribute("aria-selected", "false"); });
      $$(".tab-panel", tabs).forEach(function (p) { p.classList.remove("active"); });
      t.classList.add("active");
      t.setAttribute("aria-selected", "true");
      var panel = document.getElementById(t.getAttribute("aria-controls"));
      if (panel) panel.classList.add("active");
      return;
    }

    /* Accordion */
    t = e.target.closest(".accordion-trigger");
    if (t) {
      var item = t.closest(".accordion-item");
      var isOpen = item.classList.contains("open");
      item.classList.toggle("open", !isOpen);
      t.setAttribute("aria-expanded", !isOpen ? "true" : "false");
      return;
    }

    /* All-categories mega menu */
    t = e.target.closest(".all-categories > button");
    if (t) {
      var ac = t.closest(".all-categories");
      var open = ac.classList.toggle("open");
      t.setAttribute("aria-expanded", open ? "true" : "false");
      return;
    }
    var acOpen = $(".all-categories.open");
    if (acOpen && !e.target.closest(".all-categories")) {
      acOpen.classList.remove("open");
      var acBtn = acOpen.querySelector("button");
      if (acBtn) acBtn.setAttribute("aria-expanded", "false");
    }

    /* Variant / size / swatch selection (single-select per group) */
    t = e.target.closest(".variant-btn:not([disabled]), .size-btn, .swatch");
    if (t) {
      var group = t.closest(".variant-options, .size-options, .color-swatches");
      if (group) {
        $$(".active", group).forEach(function (b) { b.classList.remove("active"); });
        t.classList.add("active");
        var labelTarget = group.getAttribute("data-label");
        if (labelTarget && t.dataset.value) {
          var lbl = document.getElementById(labelTarget);
          if (lbl) lbl.textContent = t.dataset.value;
        }
      }
      return;
    }

    /* Gallery thumbnails */
    t = e.target.closest(".gallery-thumbs button");
    if (t) {
      var thumbs = t.closest(".gallery-thumbs");
      $$("button", thumbs).forEach(function (b) { b.classList.remove("active"); });
      t.classList.add("active");
      var mainImg = $(".gallery-main img");
      var img = t.querySelector("img");
      if (mainImg && img) { mainImg.src = img.src; mainImg.alt = img.alt; }
      return;
    }

    /* Generic toast trigger: data-toast="Message" data-toast-type="info" */
    t = e.target.closest("[data-toast]");
    if (t) {
      if (t.tagName === "A" && t.getAttribute("href") === "#") e.preventDefault();
      toast(t.getAttribute("data-toast"), t.getAttribute("data-toast-type") || "success");
      return;
    }
  });

  /* ---------- Escape key ---------- */
  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    closeDrawer();
    $$(".modal-root.show").forEach(closeModal);
  });

  /* ---------- Repeater rows (admin specifications) ----------
     <section data-repeater> with [data-repeater-rows], <template data-repeater-template>
     (uses __INDEX__ in names), [data-repeater-add] and [data-repeater-remove] buttons. */
  document.addEventListener("click", function (e) {
    var add = e.target.closest("[data-repeater-add]");
    if (add) {
      var repeater = add.closest("[data-repeater]");
      var template = $("[data-repeater-template]", repeater);
      var index = Date.now();
      $("[data-repeater-rows]", repeater).insertAdjacentHTML("beforeend", template.innerHTML.replace(/__INDEX__/g, index));
      return;
    }
    var remove = e.target.closest("[data-repeater-remove]");
    if (remove) {
      var rows = remove.closest("[data-repeater-rows]");
      var row = remove.closest("[data-repeater-row]");
      if (rows.children.length > 1) {
        row.remove();
      } else {
        $$("input", row).forEach(function (input) { input.value = ""; });
      }
    }
  });

  /* ---------- "Select all" checkbox: data-check-all="ids[]" toggles checkboxes with that name ---------- */
  $$("[data-check-all]").forEach(function (master) {
    master.addEventListener("change", function () {
      $$('input[type=checkbox][name="' + master.getAttribute("data-check-all") + '"]').forEach(function (box) {
        box.checked = master.checked;
      });
    });
  });

  /* ---------- Division → District → Upazila selects (<x-address-fields>) ---------- */
  function fillSelect(select, items) {
    var placeholder = select.options[0];
    select.innerHTML = "";
    select.appendChild(placeholder);
    items.forEach(function (item) {
      var option = document.createElement("option");
      option.value = item.id;
      option.textContent = item.name;
      select.appendChild(option);
    });
    select.value = "";
  }
  function loadOptions(url, select) {
    select.disabled = true;
    fetch(url, { headers: { Accept: "application/json" } })
      .then(function (response) { return response.ok ? response.json() : []; })
      .then(function (items) { fillSelect(select, items); })
      .catch(function () { toast("Could not load the list. Please try again.", "danger"); })
      .finally(function () { select.disabled = false; });
  }
  $$("[data-location-fields]").forEach(function (fields) {
    var form = fields.closest("form");
    var division = $("[data-location-division]", fields);
    var district = $("[data-location-district]", fields);
    var upazila = $("[data-location-upazila]", form);
    division.addEventListener("change", function () {
      fillSelect(upazila, []);
      if (!division.value) { fillSelect(district, []); return; }
      loadOptions(fields.getAttribute("data-districts-url").replace("__ID__", division.value), district);
    });
    district.addEventListener("change", function () {
      if (!district.value) { fillSelect(upazila, []); return; }
      loadOptions(fields.getAttribute("data-upazilas-url").replace("__ID__", district.value), upazila);
    });
  });

  /* ---------- Product page variant picker (data from <script id="variant-data">) ----------
     Runs after the generic option-button handler above has marked the clicked button active. */
  var picker = $("[data-variant-picker]");
  var variantData = $("#variant-data");
  if (picker && variantData) {
    var variants = JSON.parse(variantData.textContent || "[]");
    var pickerEl = function (name) { return $("[data-variant-" + name + "]", picker); };

    var showVariant = function (variant) {
      var unavailable = pickerEl("unavailable");
      var canBuy = !!(variant && variant.canBuy);
      if (unavailable) unavailable.hidden = !!variant;
      $$("[data-variant-buy]", picker).forEach(function (button) {
        if (button.tagName === "A") {
          button.classList.toggle("is-disabled", !canBuy);
          button.setAttribute("aria-disabled", canBuy ? "false" : "true");
        } else {
          button.disabled = !canBuy;
        }
      });
      if (!variant) return;
      pickerEl("price").textContent = variant.price;
      var oldPrice = pickerEl("old-price");
      oldPrice.textContent = variant.oldPrice || "";
      oldPrice.hidden = !variant.oldPrice;
      var discount = pickerEl("discount");
      discount.textContent = "-" + variant.discount + "%";
      discount.hidden = !variant.discount;
      var stock = pickerEl("stock");
      stock.textContent = variant.stockText;
      stock.className = "stock-note " + variant.stockClass;
      pickerEl("sku").textContent = variant.sku;
      pickerEl("id").value = variant.id;
      var mainImage = $("[data-gallery-main]");
      if (variant.imageUrl && mainImage) mainImage.src = variant.imageUrl;
    };

    document.addEventListener("click", function (e) {
      if (!e.target.closest("[data-variant-group] .variant-btn")) return;
      var chosen = $$("[data-variant-group] .variant-btn.active", picker).map(function (button) {
        return parseInt(button.getAttribute("data-value-id"), 10);
      });
      showVariant(variants.find(function (variant) {
        return variant.valueIds.length === chosen.length && chosen.every(function (id) { return variant.valueIds.indexOf(id) !== -1; });
      }));
    });
  }

  /* ---------- GET forms that submit when a field changes (shop filters, sort) ---------- */
  /* Empty fields and the page's default sort (data-default-sort) are left out so shared links stay short. */
  function submitClean(form) {
    var defaultSort = form.getAttribute("data-default-sort");
    $$("input, select", form).forEach(function (field) {
      var isDefaultSort = field.name === "sort" && field.value === defaultSort;
      if (field.name && (field.value === "" || isDefaultSort)) field.disabled = true;
    });
    form.submit();
  }
  $$("form[data-auto-submit]").forEach(function (form) {
    form.addEventListener("change", function (e) {
      if (e.target.type === "number") return; /* price boxes submit with their Go button */
      submitClean(form);
    });
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      submitClean(form);
    });
  });

  /* ---------- Prevent demo form submissions ---------- */
  $$("form[data-demo]").forEach(function (f) {
    f.addEventListener("submit", function (e) {
      e.preventDefault();
      var msg = f.getAttribute("data-demo");
      if (msg) toast(msg, "info");
    });
  });

  /* ---------- Countdown timers ---------- */
  /* <div class="countdown" data-countdown-hours="14"> with .cd-num[data-u] boxes */
  $$("[data-countdown-hours]").forEach(function (cd) {
    var end = Date.now() + parseFloat(cd.getAttribute("data-countdown-hours")) * 3600 * 1000;
    var nums = {
      h: cd.querySelector('[data-u="h"]'),
      m: cd.querySelector('[data-u="m"]'),
      s: cd.querySelector('[data-u="s"]')
    };
    function pad(n) { return (n < 10 ? "0" : "") + n; }
    function tick() {
      var diff = Math.max(0, end - Date.now());
      var s = Math.floor(diff / 1000);
      if (nums.h) nums.h.textContent = pad(Math.floor(s / 3600));
      if (nums.m) nums.m.textContent = pad(Math.floor((s % 3600) / 60));
      if (nums.s) nums.s.textContent = pad(s % 60);
    }
    tick();
    setInterval(tick, 1000);
  });

  /* ---------- Checkout (F-023): delivery line and total follow the chosen address ---------- */
  var checkoutForm = $("form[data-checkout]");
  if (checkoutForm) {
    var chosenUpazilaId = function () {
      var saved = $('input[name="address_id"]:checked', checkoutForm);
      if (saved && saved.value) return saved.getAttribute("data-upazila-id");
      var upazila = $("[data-location-upazila]", checkoutForm);
      return upazila ? upazila.value : "";
    };
    // Only the latest request may update the page: a slower earlier answer would show (and submit) another area's total.
    var latestQuote = 0;
    var refreshQuote = function () {
      var requestNumber = ++latestQuote;
      var url = checkoutForm.getAttribute("data-quote-url") + "?upazila_id=" + encodeURIComponent(chosenUpazilaId() || "");
      fetch(url, { headers: { Accept: "application/json" } })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (quote) {
          if (!quote || requestNumber !== latestQuote) return;
          $$("[data-summary-delivery-label]").forEach(function (el) { el.textContent = quote.deliveryLabel; });
          $$("[data-summary-delivery]").forEach(function (el) { el.textContent = quote.delivery; });
          $$("[data-summary-total]").forEach(function (el) { el.textContent = quote.total; });
          $("[data-expected-total]", checkoutForm).value = quote.expectedTotal;
        });
    };
    checkoutForm.addEventListener("change", function (e) {
      if (e.target.name === "address_id" || e.target.name === "upazila_id" || e.target.name === "district_id" || e.target.name === "division_id") refreshQuote();
    });
  }

  /* ---------- Validation errors: bring the first one into view (it may be far below the fold) ---------- */
  var firstError = $(".form-error");
  if (firstError) {
    var invalidField = $(".is-invalid") || $("input, select, textarea", firstError.parentElement);
    firstError.scrollIntoView({ block: "center" });
    if (invalidField) invalidField.focus({ preventScroll: true });
  }

  /* ---------- Tabs: open the one named in the URL hash (#tab-reviews) ---------- */
  function openTabFromHash() {
    if (!location.hash) return;
    var button = $('.tab-btn[aria-controls="' + location.hash.slice(1) + '"]');
    if (!button) return;
    button.click();
    button.scrollIntoView({ block: "start" });
  }
  openTabFromHash();
  window.addEventListener("hashchange", openTabFromHash);

  /* ---------- Payment option selection ---------- */
  $$(".payment-option input[type=radio]").forEach(function (r) {
    r.addEventListener("change", function () {
      $$('.payment-option input[name="' + r.name + '"]').forEach(function (other) { other.closest(".payment-option").classList.remove("selected"); });
      if (r.checked) r.closest(".payment-option").classList.add("selected");
    });
  });
})();

// sidebar_js.js — robust version
(function () {
  "use strict";

  // Run after DOM ready for absolute safety
  function initSidebar() {
    const sidebar = document.getElementById("sidebar");
    const btnToggle = document.getElementById("btnToggleSidebar");
    const mainarea = document.getElementById("mainarea"); // optional

    // safety: jika elemen penting tidak ada, log dan lanjutkan
    if (!sidebar) {
      console.warn("sidebar_js: #sidebar element not found in DOM.");
      return;
    }

    // Initialize bootstrap tooltips if bootstrap is available
    try {
      if (typeof bootstrap !== "undefined") {
        const tooltipTriggerList = Array.prototype.slice.call(
          document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.forEach(function (el) {
          new bootstrap.Tooltip(el);
        });
        // debug info
        // console.info('Initialized', tooltipTriggerList.length, 'tooltips');
      } else {
        console.warn(
          "sidebar_js: bootstrap not found. Make sure bootstrap.bundle.min.js is loaded."
        );
      }
    } catch (err) {
      console.error("sidebar_js: error initializing tooltips", err);
    }

    // restore collapsed state
    try {
      const collapsed = localStorage.getItem("sidebarCollapsed") === "1";
      if (collapsed) sidebar.classList.add("collapsed");
    } catch (err) {
      // localStorage can throw in privacy modes
      console.warn("sidebar_js: localStorage not available", err);
    }

    // Toggle behavior only if button exists
    if (btnToggle) {
      btnToggle.addEventListener("click", function (ev) {
        ev.preventDefault();
        if (window.innerWidth <= 768) {
          // mobile: toggle overlay show
          sidebar.classList.toggle("show");
          document.body.classList.toggle(
            "sidebar-open",
            sidebar.classList.contains("show")
          );
        } else {
          sidebar.classList.toggle("collapsed");
          try {
            localStorage.setItem(
              "sidebarCollapsed",
              sidebar.classList.contains("collapsed") ? "1" : "0"
            );
          } catch (err) {
            // ignore storage errors
          }
        }
      });
    } else {
      // optional: if you expect a toggle button but it's missing, log to console
      // console.info('sidebar_js: no toggle button (#btnToggleSidebar) found — collapse toggle disabled.');
    }

    // close mobile sidebar when clicking outside
    document.addEventListener("click", function (e) {
      if (window.innerWidth <= 768 && sidebar.classList.contains("show")) {
        const target = e.target;
        // check both exist before contains
        if (
          sidebar &&
          !sidebar.contains(target) &&
          (!btnToggle || !btnToggle.contains(target))
        ) {
          sidebar.classList.remove("show");
          document.body.classList.remove("sidebar-open");
        }
      }
    });

    // OPTIONAL: handle escape key to close mobile sidebar
    document.addEventListener("keydown", function (e) {
      if (
        e.key === "Escape" &&
        window.innerWidth <= 768 &&
        sidebar.classList.contains("show")
      ) {
        sidebar.classList.remove("show");
        document.body.classList.remove("sidebar-open");
      }
    });

    // Debug ready
    // console.log('sidebar_js initialized');
  }

  // Use DOMContentLoaded to be safe even without defer
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initSidebar);
  } else {
    initSidebar();
  }
})();

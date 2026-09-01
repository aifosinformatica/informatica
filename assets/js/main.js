(function () {
  "use strict";

  // Menú móvil
  var toggle = document.getElementById("navToggle");
  var nav = document.getElementById("site-nav");
  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      var isOpen = nav.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
    nav.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        nav.classList.remove("is-open");
        toggle.setAttribute("aria-expanded", "false");
      });
    });
  }

  // Animación de entrada muy sutil (respeta prefers-reduced-motion vía CSS)
  var revealEls = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window && revealEls.length) {
    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15 }
    );
    revealEls.forEach(function (el) { observer.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add("is-visible"); });
  }

  // Estado "abierto ahora" en el nav y el footer, calculado en el navegador
  // del visitante (así respeta su huso horario). Horario actual: lunes a
  // viernes de 08:00 a 20:00 — si el horario cambia, actualizar acá también.
  function isOpenNow() {
    var now = new Date();
    var day = now.getDay(); // 0 domingo .. 6 sábado
    var hour = now.getHours() + now.getMinutes() / 60;
    return day >= 1 && day <= 5 && hour >= 8 && hour < 20;
  }

  var navStatus = document.getElementById("navStatus");
  var navStatusText = document.getElementById("navStatusText");
  var footerStatus = document.getElementById("footerStatus");
  if (navStatusText || footerStatus) {
    var open = isOpenNow();
    if (navStatus && navStatusText) {
      navStatusText.textContent = open ? "Abierto ahora" : "Cerrado ahora";
      navStatus.classList.toggle("is-closed", !open);
    }
    if (footerStatus) {
      footerStatus.textContent = open
        ? "Abierto ahora · respondemos por WhatsApp"
        : "Cerrado ahora · dejanos tu consulta por WhatsApp";
    }
  }
})();

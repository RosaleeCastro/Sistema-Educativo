// publico/scripts/app.js
// ============================================================
// ARRANQUE GLOBAL DEL SISTEMA
// ============================================================
// Se carga en TODAS las páginas autenticadas desde cabecera.php
// Inicializa comportamientos que deben estar siempre activos:
//   - Desaparición automática de mensajes flash
//   - Menú hamburguesa en móvil
//   - Marcar link activo en el sidebar
//
// DEPENDE DE: api.js (debe cargarse antes en cabecera.php)
// LO USAN: todas las vistas autenticadas
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
  // ── Mensajes flash ───────────────────────────────────────
  // Los mensajes de éxito/error desaparecen solos a los 4 segundos
  // El usuario los ve pero no tiene que cerrarlos manualmente
  document.querySelectorAll(".flash").forEach((flash) => {
    setTimeout(() => {
      flash.style.transition = "opacity .4s, transform .4s";
      flash.style.opacity = "0";
      flash.style.transform = "translateY(-8px)";
      setTimeout(() => flash.remove(), 400);
    }, 4000);
  });

  // ── Sidebar en móvil ─────────────────────────────────────
  // En pantallas pequeñas el sidebar está oculto.
  // El botón hamburguesa lo muestra/oculta al hacer clic.
  const btnMenu = document.getElementById("btn-menu-movil");
  const sidebar = document.getElementById("sidebar");  const backdrop = document.getElementById("sidebar-backdrop");

  if (btnMenu && sidebar) {
    btnMenu.addEventListener("click", () => {
      sidebar.classList.toggle("abierto");
      backdrop?.classList.toggle("visible");
    });

    // Cerrar sidebar al hacer clic fuera de él
    backdrop?.addEventListener("click", () => {
      sidebar.classList.remove("abierto");
      backdrop.classList.remove("visible");
    });
  }

  // ── Link activo en sidebar ───────────────────────────────
  // Marca automáticamente el link del sidebar que corresponde
  // a la página actual para que el usuario sepa dónde está
  const urlActual = window.location.pathname;
  document.querySelectorAll(".sidebar-link").forEach((link) => {
    const href = link.getAttribute("href");
    if (href && urlActual.startsWith(href) && href !== "/") {
      link.classList.add("activo");
    }
  });

  // ── Confirmaciones de acciones destructivas ──────────────
  // Cualquier botón con data-confirmar="mensaje" mostrará
  // un confirm() antes de ejecutar la acción
  // Uso en HTML: <button data-confirmar="¿Eliminar este curso?">Eliminar</button>
  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-confirmar]");
    if (!btn) return;
    const mensaje = btn.dataset.confirmar || "¿Estás seguro?";
    if (!confirm(mensaje)) e.preventDefault();
  });
});

// ── Utilidades globales ──────────────────────────────────────
// Funciones disponibles en cualquier página sin importar nada

// Muestra una notificación temporal en la esquina
// Uso: Notificacion.mostrar('Guardado correctamente', 'exito')
const Notificacion = (() => {
  // Crea el contenedor si no existe
  const obtenerContenedor = () => {
    let c = document.getElementById("notif-container");
    if (!c) {
      c = document.createElement("div");
      c.id = "notif-container";
      c.style.cssText = `
                position: fixed; bottom: 1.5rem; right: 1.5rem;
                z-index: 9999; display: flex; flex-direction: column; gap: .5rem;
            `;
      document.body.appendChild(c);
    }
    return c;
  };

  const mostrar = (mensaje, tipo = "info", duracion = 3500) => {
    const colores = {
      exito: {
        bg: "var(--exito-bg)",
        borde: "var(--exito-borde)",
        texto: "var(--exito)",
      },
      error: {
        bg: "var(--error-bg)",
        borde: "var(--error-borde)",
        texto: "var(--error)",
      },
      info: {
        bg: "var(--info-bg)",
        borde: "var(--info-borde)",
        texto: "var(--info)",
      },
      aviso: {
        bg: "var(--aviso-bg)",
        borde: "var(--aviso-borde)",
        texto: "var(--aviso)",
      },
    };
    const iconos = { exito: "✅", error: "❌", info: "ℹ️", aviso: "⚠️" };
    const c = colores[tipo] || colores.info;

    const notif = document.createElement("div");
    notif.style.cssText = `
            background: ${c.bg}; border: 1px solid ${c.borde}; color: ${c.texto};
            padding: .75rem 1rem; border-radius: 10px; font-size: .875rem;
            box-shadow: var(--sombra); max-width: 320px;
            animation: slideIn .3s ease; display: flex; align-items: center; gap: .5rem;
        `;
    notif.innerHTML = `<span>${iconos[tipo] || "ℹ️"}</span><span>${mensaje}</span>`;

    // Añadir animación CSS si no existe
    if (!document.getElementById("notif-style")) {
      const style = document.createElement("style");
      style.id = "notif-style";
      style.textContent = `
                @keyframes slideIn {
                    from { opacity: 0; transform: translateX(20px); }
                    to   { opacity: 1; transform: translateX(0); }
                }
            `;
      document.head.appendChild(style);
    }

    obtenerContenedor().appendChild(notif);

    // Desaparece automáticamente
    setTimeout(() => {
      notif.style.transition = "opacity .3s, transform .3s";
      notif.style.opacity = "0";
      notif.style.transform = "translateX(20px)";
      setTimeout(() => notif.remove(), 300);
    }, duracion);
  };

  return { mostrar };
})();

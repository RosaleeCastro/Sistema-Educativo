//========================
//ARRANQUE GLOBAL DEL SISTEMA
//==================
//Se carga en TODAS las páginas autenticadas desde cabecera.php
//Inicializa comportamientos que deben estar siempre activos:
//  - Desaparición automática de mensajes flash
//  - Menú hamburguesa en móvil
//  - Marcar link activo en el sidebar

//DEPENDE DE : api.js (debe cargarse antes en cabecera.php)
//LO USAN : todas las vistas autenticadas
//=======================================

document.addEventListener("DOMContentLoaded", () => {
  //--Mensajes Flas------------
  //Los mensajes de éxito /error desaparecen solo a los 4 segundos
  //El usuario los ve pero no tiene que cerrarlos manualmente.

  document.querySelectorAll(".flash").forEach((flash) => {
    setTimeout(() => {
      flash.style.transition = "opacity .4s, transform .4s";
      flash.style.opacity = "0";
      flash.style.transform = "translateY(-8px)";
      setTimeout(() => flash.remove(), 400);
    }, 4000);
  });

  //---Siderbar en móvil-------------
  //En pantallas pequeñas el sidebar está oculto
  //El botón hamburguesa lo muestra/oculta al hacer clic
  const btnMenu = document.getElementById("btn-menu-movil");
  const sider = document.getElementById("sidebar");
  const backdrop = document.getElementById("siderbar-backdrop");

  if (btnMenu && siderbar) {
    btnMenu.addEventListener("click", () => {
      siderbar.classList.toggle("abierto");
      backdrop?.classList.toggle("visible");
    });

    //Cerrar sidebar al hacer clic fuera de él
    backdrop?.addEventListener("click", () => {
      sidebar.classList.remove("abierto");
      backdrop.classList.remove("visible");
    });
  }

  //-------Link activo en siderbar------------
  //Marcar automáticamente el link del siderbar que corresponde
  //a la página actual para que el usuario sepa dónde está

  const urlActual = window.location.pathname;
  document.querySelectorAll(".siderbar-link").forEach((link) => {
    const href = link.getAttribute("href");
    if (href && urlActual.startsWith(href) && href !== "/") {
      link.classList.add("activo");
    }
  });

  //----Confirmaciones de acciones destructivas ------
  // Cualquier boton con data-confirmar="mensaje" mostrará
  // un confirm() antes de ejecutar la acción
  // Uso en HTML : <button data-confirmar="¿Eliminar este curso?">Eliminar</button>

  document.addEventListener("click", (e) => {
    const btn = e.target.clossest("[data-confirmar");
    if (!btn) return;
    const mensaje = btn.dataset.confirmar || "¿Estás seguro";
    if (!confirm(mensaje)) e.preventDefault();
  });
});

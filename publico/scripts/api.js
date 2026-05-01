//==========================
//  CLIENTE FETCH CENTRALIZADO
//==========================
//Todos los archivos JS del sistema usan estemódulopara
//comunicarse con el backend PHP. No usaremos fetch()
//directamente en otro archivo  - siempre usa APi.get() o Api.post()
//
//CONEXIONES
// <- Lo usan : panel.js asistencia.js, notificaciones.js  chat-ia.js
// -> Llama a : /api/* en index.php
//
//VENTAJAS DE CENTRALIZAR
//  - El token CSRF se añade automaticamente en cada POST
//  - Los errores se manejan en un solo lugar
//  - Si cambia la URL base, solo se toca aquí.

//======================

const Api = (() => {
  // Calcula la URL base desde el meta tag que PHP inyecta en cabecera.php
  const meta = document.querySelector('meta[name="app-url"]');
  const BASE_URL = meta
    ? meta.content.replace(/\/$/, "")
    : window.location.origin + "/sistema-educativo/publico";

  const obtenerCSRF = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ?? "";

  const cabeceras = () => ({
    "Content-Type": "application/json",
    "X-Requested-With": "XMLHttpRequest",
    Accept: "application/json",
  });

  const get = async (endpoint) => {
    try {
      const res = await fetch(BASE_URL + endpoint, {
        method: "GET",
        headers: cabeceras(),
      });
      return await procesar(res);
    } catch (e) {
      console.error("[Api.get]", endpoint, e);
      return { estado: "error", mensaje: "Sin conexión." };
    }
  };

  const post = async (endpoint, datos = {}) => {
    try {
      const res = await fetch(BASE_URL + endpoint, {
        method: "POST",
        headers: { ...cabeceras(), "X-CSRF-Token": obtenerCSRF() },
        body: JSON.stringify(datos),
      });
      return await procesar(res);
    } catch (e) {
      console.error("[Api.post]", endpoint, e);
      return { estado: "error", mensaje: "Sin conexión." };
    }
  };

  const procesar = async (res) => {
    if (res.status === 401) {
      window.location.href = BASE_URL + "/login";
      return;
    }
    if (res.status === 403)
      return { estado: "error", mensaje: "Sin permisos." };
    if (res.status >= 500)
      return { estado: "error", mensaje: "Error del servidor." };
    try {
      return await res.json();
    } catch {
      return { estado: "error", mensaje: "Respuesta inválida." };
    }
  };

  return { get, post, BASE_URL };
})();

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
  //BASE_URL se calcula desde la URL actual para que funcione
  //tanto en Xamm´p local como el servidor de produccción.

  const BASE_URL = () => {
    const url = window.location.href;
    const match = url.match(/(.*\/publico)/);
    return match ? match[1] : "";
  };
  // PHP pone el token CSRF en una meta tag de la cabecera
  //JS lo lee desde aquí para enviarlo en cada POST

  const obtenerCSRF = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ?? "";

  // X-Requested-with le dice a PHP que es una peticion AJAX

  const cabecerasBase = () => ({
    "Content-Type": "application/json",
    "X-Requested-With": "XMLHttpRequest",
    Accept: "application/json",
  });

  //GET - uso : const datos = await Api.get('/api/cursos/alumno');

  const get = async (endpoint) => {
    try {
      const res = await fetch(BASE_URL + endpoint, {
        method: "GET",
        headers: cabecerasBase(),
      });
      return await procesarRespuesta(res);
    } catch (error) {
      console.error(`[Api.get] ${endpoint}:`, error);
      return { estado: "error", mensaje: "Sin conexión al servidor." };
    }
  };
})();

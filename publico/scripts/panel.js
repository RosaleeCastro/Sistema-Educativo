//-------------------
// CARGAT DE DATOS DEL PANEL
//--------------------
//PHP entrega el html vacio con contenedores identificados por ID.
//Este archivo detecta el rol, llama a la API y rellena el HTML
//
// DEPENDE DE : api.js(debe caragrse antes)
//ENDPOINTS:
//   GET /api/cursos/alumno   → lista cursos del alumno
//   GET /api/cursos/profesor → lista cursos del profesor
//   GET /api/stats/admin     → métricas del sistema
//-----------------

document.addEventListener("DOMContentLoaded", () => {
  const rol = document.body.dataset.rol;
  if (rol === "alumno") cargarPanelAlumno();
  if (rol === "profesor") cargarPanelProfesor();
  if (rol === "admin") cargarPanelAdmin();
});

async function cargarPanelAlumno() {
  const c = document.getElementById("cursos-alumno");
  if (!c) return;
  c.innerHTML = skeletons(3);
  const datos = await Api.get("/api/cursos/alumno");
  if (datos.estado === "error") {
    c.innerHTML = errorHtml(datos.mensaje);
    return;
  }
  const cursos = datos.cursos ?? [];
  if (!cursos.length) {
    c.innerHTML = `<div class="tarjeta texto-centro" style="padding:3rem;grid-column:1/-1">
            <div style="font-size:3rem;margin-bottom:1rem">📚</div>
            <h3>No estás inscrito en ningún curso</h3>
            <p class="mt-1">Contacta con tu profesor para inscribirte.</p></div>`;
    return;
  }
  c.innerHTML = cursos.map(tarjetaAlumno).join("");
}

async function cargarPanelProfesor() {
    const c = document.getElementById('cursos-profesor');
    if (!c) return;
    c.innerHTML = skeletons(3);
    const datos = await Api.get('/api/cursos/profesor');
    if (datos.estado === 'error') { c.innerHTML = errorHtml(datos.mensaje); return; }
    const cursos = datos.cursos ?? [];
    if (!cursos.length) {
        c.innerHTML = `<div class="tarjeta texto-centro" style="padding:3rem;grid-column:1/-1">
            <div style="font-size:3rem;margin-bottom:1rem">📚</div>
            <h3>No tienes cursos asignados aún</h3></div>`;
        return;
    }
    c.innerHTML = cursos.map(tarjetaProfesor).join('');
}

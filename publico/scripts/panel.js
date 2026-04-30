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
  const c = document.getElementById("cursos-profesor");
  if (!c) return;
  c.innerHTML = skeletons(3);
  const datos = await Api.get("/api/cursos/profesor");
  if (datos.estado === "error") {
    c.innerHTML = errorHtml(datos.mensaje);
    return;
  }
  const cursos = datos.cursos ?? [];
  if (!cursos.length) {
    c.innerHTML = `<div class="tarjeta texto-centro" style="padding:3rem;grid-column:1/-1">
            <div style="font-size:3rem;margin-bottom:1rem">📚</div>
            <h3>No tienes cursos asignados aún</h3></div>`;
    return;
  }
  c.innerHTML = cursos.map(tarjetaProfesor).join("");
}
async function cargarPanelAdmin() {
  const datos = await Api.get("/api/stats/admin");
  if (datos.estado === "error") return;
  animarContador("stat-alumnos", datos.alumnos ?? 0);
  animarContador("stat-profesores", datos.profesores ?? 0);
  animarContador("stat-cursos", datos.cursos ?? 0);
  animarContador("stat-unidades", datos.unidades ?? 0);
}

function tarjetaAlumno(c) {
    const pct   = parseInt(c.porcentaje_asistencia ?? 0);
    const color = pct>=75?'var(--exito)':pct>=50?'var(--aviso)':'var(--error)';
    const badge = pct>=75?'badge-exito':pct>=50?'badge-aviso':'badge-error';
    const texto = pct>=75?'Al día':pct>=50?'Regular':'Atención';
    return `<a href="${Api.BASE_URL}/alumno/curso/${c.id}" class="tarjeta"
        style="text-decoration:none;display:block;transition:box-shadow .15s"
        onmouseover="this.style.boxShadow='var(--sombra)'"
        onmouseout="this.style.boxShadow='var(--sombra-sm)'">
        <div style="height:90px;border-radius:var(--radio);margin-bottom:1rem;
            background:linear-gradient(135deg,var(--primario),var(--primario-dk));
            display:flex;align-items:center;justify-content:center;font-size:2rem">📖</div>
        <div style="font-weight:600;font-size:.9375rem;color:var(--texto);margin-bottom:.375rem">
            ${esc(c.nombre)}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-bottom:.75rem">
            👨‍🏫 ${esc(c.nombre_profesor??'')}
            ${c.nombre_programa?`· 🎓 ${esc(c.nombre_programa)}`:''}
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.75rem;
            color:var(--texto-suave);margin-bottom:.25rem">
            <span>Asistencia</span><span>${pct}%</span></div>
        <div style="background:var(--borde);border-radius:4px;height:6px;overflow:hidden;margin-bottom:.75rem">
            <div style="width:${pct}%;height:6px;background:${color};border-radius:4px;transition:width .6s ease"></div>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:.75rem">
            <span style="color:var(--texto-suave)">📋 ${c.total_unidades??0} unidades</span>
            <span class="badge ${badge}">${texto}</span>
        </div></a>`;
}
 

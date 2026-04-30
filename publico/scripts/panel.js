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
  const pct = parseInt(c.porcentaje_asistencia ?? 0);
  const color =
    pct >= 75 ? "var(--exito)" : pct >= 50 ? "var(--aviso)" : "var(--error)";
  const badge =
    pct >= 75 ? "badge-exito" : pct >= 50 ? "badge-aviso" : "badge-error";
  const texto = pct >= 75 ? "Al día" : pct >= 50 ? "Regular" : "Atención";
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
            👨‍🏫 ${esc(c.nombre_profesor ?? "")}
            ${c.nombre_programa ? `· 🎓 ${esc(c.nombre_programa)}` : ""}
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.75rem;
            color:var(--texto-suave);margin-bottom:.25rem">
            <span>Asistencia</span><span>${pct}%</span></div>
        <div style="background:var(--borde);border-radius:4px;height:6px;overflow:hidden;margin-bottom:.75rem">
            <div style="width:${pct}%;height:6px;background:${color};border-radius:4px;transition:width .6s ease"></div>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:.75rem">
            <span style="color:var(--texto-suave)">📋 ${c.total_unidades ?? 0} unidades</span>
            <span class="badge ${badge}">${texto}</span>
        </div></a>`;
}
function tarjetaProfesor(c) {
  return `<div class="tarjeta">
        <div style="font-weight:600;font-size:.9375rem;color:var(--texto);margin-bottom:.375rem">
            ${esc(c.nombre)}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-bottom:1rem">
            ${c.nombre_programa ? `🎓 ${esc(c.nombre_programa)}` : '<span class="badge badge-gris">Curso independiente</span>'}
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:1rem">
            <div style="background:var(--primario-lt);border-radius:var(--radio);padding:.625rem;text-align:center">
                <div style="font-size:1.25rem;font-weight:700;color:var(--primario)">${c.total_alumnos ?? 0}</div>
                <div style="font-size:.7rem;color:var(--texto-suave)">Alumnos</div>
            </div>
            <div style="background:var(--exito-bg);border-radius:var(--radio);padding:.625rem;text-align:center">
                <div style="font-size:1.25rem;font-weight:700;color:var(--exito)">${c.total_unidades ?? 0}</div>
                <div style="font-size:.7rem;color:var(--texto-suave)">Unidades</div>
            </div>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="${Api.BASE_URL}/profesor/curso/${c.id}/unidad/nueva"
               class="btn btn-primario btn-sm" style="flex:1;justify-content:center">+ Unidad</a>
            <a href="${Api.BASE_URL}/profesor/unidad/${c.id}/asistencia"
               class="btn btn-secundario btn-sm" style="flex:1;justify-content:center">📋 Asistencia</a>
        </div></div>`;
}

function skeletons(n) {
  return Array(n)
    .fill(
      `<div class="tarjeta" style="animation:pulso 1.5s infinite">
        <div style="height:90px;background:var(--borde);border-radius:var(--radio);margin-bottom:1rem"></div>
        <div style="height:16px;background:var(--borde);border-radius:4px;margin-bottom:.5rem;width:70%"></div>
        <div style="height:12px;background:var(--borde);border-radius:4px;width:50%"></div>
        <style>@keyframes pulso{0%,100%{opacity:1}50%{opacity:.5}}</style></div>`,
    )
    .join("");
}
function errorHtml(msg) {
  return `<div class="flash flash-error" style="grid-column:1/-1">⚠️ ${esc(msg)}</div>`;
}
function animarContador(id, fin) {
  const el = document.getElementById(id);
  if (!el) return;
  let n = 0,
    paso = Math.ceil(fin / 30);
  const t = setInterval(() => {
    n = Math.min(n + paso, fin);
    el.textContent = n;
    if (n >= fin) clearInterval(t);
  }, 30);
}
function esc(s) {
  return String(s ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

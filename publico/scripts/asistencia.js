// publico/scripts/asistencia.js
// ============================================================
// ASISTENCIA EN TIEMPO REAL
// ============================================================
// Gestiona el marcado de asistencia sin recargar la pagina.
//
// FLUJO ALUMNO:
//   1. Alumno ve el boton "Marcar asistencia" en la unidad
//   2. Hace clic → este JS llama POST /api/asistencia
//   3. PHP registra en BD y devuelve JSON
//   4. Este JS actualiza el boton sin recargar
//
// FLUJO PROFESOR:
//   1. Profesor abre /profesor/unidad/:id/asistencia
//   2. Este JS hace polling cada 10 segundos a GET /api/asistencia/:id
//   3. Actualiza la tabla y los contadores en tiempo real
//
// DEPENDE DE: api.js (cargado antes en pie.php)
// ENDPOINTS:
//   POST /api/asistencia           → marcar asistencia
//   GET  /api/asistencia/:id       → obtener lista actualizada
//   GET  /api/asistencia/:id/exportar → descargar CSV
// ============================================================

// ── Modulo del Alumno ─────────────────────────────────────────
const AsistenciaAlumno = (() => {
  /**
   * Marca la asistencia del alumno actual.
   * Se llama al hacer clic en cualquier boton con
   * data-accion="marcar-asistencia"
   */
  const marcar = async (boton) => {
    const unidadId = parseInt(boton.dataset.unidad);
    const estado = boton.dataset.estado ?? "presente";

    if (!unidadId) return;

    // Deshabilitar boton mientras se procesa
    boton.disabled = true;
    boton.textContent = "Enviando...";

    const resultado = await Api.post("/api/asistencia", {
      unidad_id: unidadId,
      estado: estado,
    });

    if (resultado?.registrado === true) {
      // Actualizar UI segun el estado marcado
      actualizarBotonEstado(boton, estado);
      Notificacion.mostrar("Asistencia registrada correctamente", "exito");

      // Actualizar resumen si existe en la pagina
      if (resultado.resumen) {
        actualizarResumen(resultado.resumen);
      }
    } else {
      // Restaurar boton si hay error
      boton.disabled = false;
      boton.textContent = "Reintentar";
      Notificacion.mostrar(resultado?.mensaje ?? "Error al registrar", "error");
    }
  };

  // Cambia el aspecto del boton segun el estado marcado
  const actualizarBotonEstado = (boton, estado) => {
    const config = {
      presente: {
        texto: "✅ Presente",
        color: "var(--exito)",
        bg: "var(--exito-bg)",
      },
      tardanza: {
        texto: "⏰ Tardanza",
        color: "var(--aviso)",
        bg: "var(--aviso-bg)",
      },
      ausente: {
        texto: "❌ Ausente",
        color: "var(--error)",
        bg: "var(--error-bg)",
      },
    };
    const c = config[estado] ?? config.presente;

    // Marcar la fila de la unidad como completada
    const fila = boton.closest("[data-unidad-fila]");
    if (fila) fila.dataset.estado = estado;
  };

  // Actualiza los contadores de resumen (presentes/ausentes/tardanzas)
  const actualizarResumen = (resumen) => {
    const ids = {
      presentes: "contador-presentes",
      ausentes: "contador-ausentes",
      tardanzas: "contador-tardanzas",
      total: "contador-total",
    };
    Object.entries(ids).forEach(([clave, id]) => {
      const el = document.getElementById(id);
      if (el && resumen[clave] !== undefined) {
        el.textContent = resumen[clave];
      }
    });
  };

  // Delegacion de eventos — un solo listener para todos los botones
  // Funciona aunque los botones se carguen despues dinamicamente
  document.addEventListener("click", (e) => {
    const boton = e.target.closest('[data-accion="marcar-asistencia"]');
    if (boton) marcar(boton);
  });

  return { marcar };
})();

// ── Modulo del Profesor ───────────────────────────────────────
const AsistenciaProfesor = (() => {
  let intervaloPolling = null;
  let unidadIdActual = null;

  /**
   * Inicia el polling de asistencia para una unidad.
   * Llama al API cada 10 segundos y actualiza la tabla.
   * Se activa automaticamente si existe #tabla-asistencia en la pagina.
   */
  const iniciarPolling = (unidadId, intervaloSegundos = 10) => {
    unidadIdActual = unidadId;

    // Carga inmediata al entrar
    actualizar(unidadId);

    // Luego cada N segundos
    intervaloPolling = setInterval(
      () => actualizar(unidadId),
      intervaloSegundos * 1000,
    );

    // Limpiar el intervalo al salir de la pagina
    window.addEventListener("beforeunload", detener);
  };

  const detener = () => {
    if (intervaloPolling) {
      clearInterval(intervaloPolling);
      intervaloPolling = null;
    }
  };

  // Llama al API y actualiza la tabla
  const actualizar = async (unidadId) => {
    const datos = await Api.get(`/api/asistencia/${unidadId}`);
    if (datos?.estado !== "ok") return;

    if (datos.asistencias) renderizarTabla(datos.asistencias);
    if (datos.resumen) actualizarContadores(datos.resumen);
  };

  // Renderiza la tabla de asistencia con los datos actualizados
  const renderizarTabla = (asistencias) => {
    const tbody = document.querySelector("#tabla-asistencia tbody");
    if (!tbody) return;

    if (!asistencias.length) {
      tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;
                color:var(--texto-suave);padding:2rem">
                Ningún alumno ha marcado asistencia aún</td></tr>`;
      return;
    }

    const colores = {
      presente: "badge-exito",
      tardanza: "badge-aviso",
      ausente: "badge-error",
    };

    tbody.innerHTML = asistencias
      .map(
        (a) => `
            <tr>
                <td>${escHtml(a.alumno_apellidos)}, ${escHtml(a.alumno_nombre)}</td>
                <td>${escHtml(a.alumno_correo)}</td>
                <td>
                    <span class="badge ${colores[a.estado] ?? "badge-gris"}">
                        ${ucfirst(a.estado)}
                    </span>
                </td>
                <td style="font-size:.75rem;color:var(--texto-suave)">
                    ${formatearFecha(a.created_at)}
                </td>
            </tr>`,
      )
      .join("");
  };

  // Actualiza los contadores numericos del resumen
  const actualizarContadores = (resumen) => {
    const mapa = {
      "contador-presentes": resumen.presentes ?? 0,
      "contador-ausentes": resumen.ausentes ?? 0,
      "contador-tardanzas": resumen.tardanzas ?? 0,
      "contador-total": resumen.total ?? 0,
    };
    Object.entries(mapa).forEach(([id, valor]) => {
      const el = document.getElementById(id);
      if (el) el.textContent = valor;
    });
  };

  // Descarga el CSV de asistencia
  const exportarCsv = (unidadId) => {
    window.location.href = `${Api.BASE_URL}/api/asistencia/${unidadId}/exportar`;
  };

  // Funciones de formato
  const escHtml = (s) =>
    String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");

  const ucfirst = (s) =>
    String(s ?? "")
      .charAt(0)
      .toUpperCase() + String(s ?? "").slice(1);

  const formatearFecha = (fecha) => {
    if (!fecha) return "-";
    const d = new Date(fecha.replace(" ", "T"));
    return isNaN(d)
      ? fecha
      : d.toLocaleTimeString("es-ES", {
          hour: "2-digit",
          minute: "2-digit",
        });
  };

  // Auto-iniciar si estamos en la vista de asistencia del profesor
  document.addEventListener("DOMContentLoaded", () => {
    const tabla = document.getElementById("tabla-asistencia");
    if (!tabla) return;

    const unidadId = parseInt(tabla.dataset.unidad);
    if (unidadId) iniciarPolling(unidadId);

    // Boton de exportar CSV
    document
      .getElementById("btn-exportar-csv")
      ?.addEventListener("click", () => exportarCsv(unidadId));
  });

  return { iniciarPolling, detener, exportarCsv };
})();

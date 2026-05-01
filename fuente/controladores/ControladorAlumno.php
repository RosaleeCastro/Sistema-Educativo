<?php
// fuente/controladores/ControladorAlumno.php
declare(strict_types=1);

namespace App\Controladores;

use App\Modelos\Curso;
use App\Modelos\Asistencia;
use App\Configuracion\GestorSesion;
use App\Servicios\ServicioLog;

class ControladorAlumno extends ControladorBase
{
    private Curso      $modeloCurso;
    private Asistencia $modeloAsistencia;

    public function __construct()
    {
        $this->modeloCurso      = new Curso();
        $this->modeloAsistencia = new Asistencia();
    }

    public function panel(array $params = []): void
    {
        $alumnoId = GestorSesion::obtenerIdUsuario();

        if(!$alumnoId){
            $this->redirigir('/login');
            return;
        }
        
        $cursos   = $this->modeloCurso->listarPorAlumno($alumnoId);

        ServicioLog::registrar('ver_panel', 'alumno', $alumnoId);

        $this->vista('alumno/panel', [
            'tituloPagina' => 'Mi panel — EduSystem',
            'cursos'       => $cursos,
        ]);
    }

    public function misAsistencias(array $params = []): void
    {
        $alumnoId = GestorSesion::obtenerIdUsuario();
        $cursos   = $this->modeloCurso->listarPorAlumno($alumnoId);

        $this->vista('alumno/mis-asistencias', [
            'tituloPagina' => 'Mis asistencias — EduSystem',
            'cursos'       => $cursos,
            'alumnoId'     => $alumnoId,
        ]);
    }
}
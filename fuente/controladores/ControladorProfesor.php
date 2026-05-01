<?php
// fuente/controladores/ControladorProfesor.php
declare(strict_types=1);

namespace App\Controladores;

use App\Modelos\Curso;
use App\Modelos\Unidad;
use App\Configuracion\GestorSesion;
use App\Servicios\ServicioLog;

class ControladorProfesor extends ControladorBase
{
    private Curso   $modeloCurso;
    private Unidad  $modeloUnidad;

    public function __construct()
    {
        $this->modeloCurso  = new Curso();
        $this->modeloUnidad = new Unidad();
    }

    public function panel(array $params = []): void
    {
        $profesorId = GestorSesion::obtenerIdUsuario();
        $cursos     = $this->modeloCurso->listarPorProfesor($profesorId);

        ServicioLog::registrar('ver_panel', 'profesor', $profesorId);

        $this->vista('profesor/panel', [
            'tituloPagina' => 'Panel profesor — EduSystem',
            'cursos'       => $cursos,
        ]);
    }

    public function misCursos(array $params = []): void
    {
        $profesorId = GestorSesion::obtenerIdUsuario();
        $cursos     = $this->modeloCurso->listarPorProfesor($profesorId);

        $this->vista('profesor/cursos', [
            'tituloPagina' => 'Mis cursos — EduSystem',
            'cursos'       => $cursos,
        ]);
    }
}
<?php
// fuente/controladores/ControladorAdmin.php
declare(strict_types=1);

namespace App\Controladores;

use App\Modelos\Usuario;
use App\Modelos\Curso;
use App\Configuracion\GestorSesion;
use App\Servicios\ServicioLog;

class ControladorAdmin extends ControladorBase
{
    private Usuario $modeloUsuario;
    private Curso   $modeloCurso;

    public function __construct()
    {
        $this->modeloUsuario = new Usuario();
        $this->modeloCurso   = new Curso();
    }

    public function panel(array $params = []): void
    {
        $stats = [
            'usuarios' => $this->modeloUsuario->contarPorRol(),
            'cursos'   => $this->modeloCurso->contar(),
        ];

        ServicioLog::registrar('ver_panel', 'admin', null);

        $this->vista('admin/panel', [
            'tituloPagina' => 'Panel Admin — EduSystem',
            'stats'        => $stats,
        ]);
    }

    public function listarUsuarios(array $params = []): void
    {
        $usuarios = $this->modeloUsuario->listarTodos('apellidos');
        $this->vista('admin/usuarios', [
            'tituloPagina' => 'Usuarios — EduSystem',
            'usuarios'     => $usuarios,
        ]);
    }

    public function formularioNuevoUsuario(array $params = []): void
    {
        $token = $this->generarTokenCSRF();
        $this->vista('admin/nuevo-usuario', [
            'tituloPagina' => 'Nuevo usuario — EduSystem',
            'token'        => $token,
        ]);
    }

    public function crearUsuario(array $params = []): void
    {
        $this->verificarCSRF();

        $datos = [
            'nombre'    => $this->param('nombre'),
            'apellidos' => $this->param('apellidos'),
            'correo'    => $this->param('correo'),
            'contrasena'=> $this->param('contrasena'),
            'rol'       => $this->param('rol'),
            'activo'    => 1,
        ];

        if ($this->modeloUsuario->existeRegistro('correo', $datos['correo'])) {
            $this->redirigirConMensaje(
                '/admin/usuario/nuevo', 'error', 'Ese correo ya está registrado.'
            );
            return;
        }

        $id = $this->modeloUsuario->registrar($datos);
        ServicioLog::registrar('crear_usuario', 'usuario', $id);
        $this->redirigirConMensaje('/admin/usuarios', 'exito', 'Usuario creado correctamente.');
    }

    public function listarProgramas(array $params = []): void
    {
        $this->vista('admin/programas', [
            'tituloPagina' => 'Programas — EduSystem',
        ]);
    }

    public function verLogs(array $params = []): void
    {
        $pdo  = \App\Configuracion\BaseDatos::obtenerConexion();
        $logs = $pdo->query(
            'SELECT r.*, CONCAT(u.nombre," ",u.apellidos) AS nombre_usuario
               FROM registros_actividad r
               LEFT JOIN usuarios u ON u.id = r.usuario_id
              ORDER BY r.created_at DESC
              LIMIT 100'
        )->fetchAll();

        $this->vista('admin/logs', [
            'tituloPagina' => 'Logs — EduSystem',
            'logs'         => $logs,
        ]);
    }
}
<?php
//  Gestiona lo relacionado a entrar y salir
// -Muestra el formulario de Login
// - Verifica usuario y contraseña
// - Bloquea tras 5 intentos fallidos
// - Redirige al panel correcto según el rol
// - Cierre la sesión limpiamente

declare(strict_types=1);

namespace App\Controladores;

use App\Modelos\Usuario;
use App\Configuracion\GestorSesion;
use App\Servicios\ServicioLog;

class ControladorAuth extends ControladorBase
{
  private Usuario $modeloUsuario;

  public function __construct()
  {
    $this->modeloUsuario = new Usuario();
  }

  //---Página de inicio -> redirige según rol--------

  public function inicio(array $params = []):void
  {
    if (!GestorSesion::estaAutenticado()){
      $this->redirigir('/login');
      return;
    }
    $this->redirigirSegunRol();
  }

  //----Mostrar formulario de login----------

  public function mostrarLogin(array $params = []): void
{
    if (GestorSesion::estaAutenticado()) {
        $this->redirigirSegunRol();
        return;
    }

    // Aseguramos que la sesión está activa antes de generar el token
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $token = $this->generarTokenCSRF();

    $this->vistaSimple('auth/login', [
        'token' => $token,
        'error' => GestorSesion::obtenerFlash('error'),
        'info'  => GestorSesion::obtenerFlash('info'),
    ]);
}

  //-----Procesar login-------

  public function procesarLogin(array $params =[]):void
  {
    $this->verificarCSRF();

    $correo   = trim($_POST['correo']  ?? '');
    $password = trim($_POST['password'] ?? '');
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';

    //Validacion básica
    if(empty($correo) || empty($password)){
      GestorSesion::flash('error', 'Introduce tu correo y contraseña.');

      $this->redirigir('/login');
      return;
    }

    //Verificar si está bloqueado por fuerza bruta 
    if($this->modeloUsuario->estaBloqueado($ip, $correo)){
      GestorSesion::flash('error',
      'Demasiados intentos fallidos. Espera ' . BLOQUEO_MINUTOS . 'minutos');
      ServicioLog::registrar('login_bloqueado', 'usuario', null,
      ['correo' => $correo, 'ip' => $ip]);
      $this->redirigir('/login');
      return;
    }

    //Intentar autenticar

    $usuario = $this->modeloUsuario->autenticar($correo, $password);

    if(!$usuario){
      //Registrar intento fallido
      $this->modeloUsuario->registrarIntentoFallido($ip, $correo);
      $intentos = $this->modeloUsuario->contarIntentosFallidos($ip, $correo);

      $restantes = INTENTOS_LOGIN_MAX - $intentos;

      $mensaje = $restantes > 0 ? "Correo o contraseña incorrectos. Te quedan {$restantes} intentos." : 'Cuenta bloqueada temporalmente por seguridad';

      GestorSesion::flash('error', $mensaje);
      ServicioLog::registrar('login_fallido', null, null, [
        'correo' => $correo, 'intentos' => $intentos
      ]);

      $this->redirigir('/login');

      return;
    }

    //Login correcto - Limpiar intentos ($ip, #correo);
    $this->modeloUsuario->limpiarIntentos($ip, $correo);
    GestorSesion::iniciarSesionUsuario($usuario);

    ServicioLog::registrar('login_exitoso', 'usuario', $usuario['id'], ['rol' =>$usuario['rol']]);

    //Si había una URL a la que intentaba ir, redirigir ahí
    $urlDeseada = $_SESSION['url_deseada'] ?? null;
    unset($_SESSION['url_deseada']);

    if($urlDeseada){
      $base = rtrim($_ENV['APP_URL'] ?? '','/');
      header("Location: {$base}{$urlDeseada}");
      exit;
    }

    $this->redirigirSegunRol();

  }

  //-----Cerrar sesion-----------

  public function cerrarSesion(array $params = []): void 
  {
    $usuarioId = GestorSesion::obtenerIdUsuario();
    ServicioLog::registrar('logout', 'usuario', $usuarioId);
    GestorSesion::cerrar();
    $this->redirigir('/login');

  }

  //-----Utilidad privada-----------

  private function redirigirSegunRol(): void
  {
    $rol = GestorSesion::obtenerRol();
    match($rol){
      ROL_ALUMNO    => $this->redirigir('/alumno/panel'),
      ROL_PROFESOR  => $this->redirigir('/prodesor/panel'),
      ROL_ADMIN     => $this->redirigir('/admi/panel'),
      default       =>$this->redirigir('/login'),
    };
  }
}
?>
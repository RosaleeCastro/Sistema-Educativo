<?php

//Centarliza toda la lógica de sesion de PHP
//Responsabilidades
//  - Inciiar la sesión de forma segura
//  - Guardar y leer los datos del usuario logueado
//  - Verificar si la sesion ha expirado
//  - Regenerar el ID al hacer login (previene fijación de sesión)
//  - Destruir la sessión la hacer logout

//

declare (strict_types=1);

namespace App\Configuracion;

class GestorSesion{

  //Claves usadas en $_SESSION - constantes para evitar typos

    private const CLAVE_ID       = 'usuario_id';
    private const CLAVE_ROL      = 'usuario_rol';
    private const CLAVE_NOMBRE   = 'usuario_nombre';
    private const CLAVE_CORREO   = 'usuario_correo';
    private const CLAVE_ULTIMA   = 'ultima_actividad';
    private const CLAVE_IP       = 'ip_origen';

  /**
   * Incia la sesión con configuración segura.
   * Llamar una vez al arrancar, desde index.php
   */

  public static function iniciar():void
  {
    if(session_status() === PHP_SESSION_ACTIVE) return;

    // Nombre personalizado de la cookie de sesión
    // Evita exponer que usamos PHP (el nombre 'PHPSESSID' es un dato que
    // los atacantes usan para identificar el lenguaje del servidor)
    session_name('edu_session');

    session_start();

    //Si hay sesión activa, verificamos que no haya expirado

    if(self::estaAutenticado()){
      self::verificarExpiracion();
      self::verificarIP();
    }

  }
  /**
     * Registra al usuario en la sesión tras un login exitoso.
     * Regenera el ID de sesión para prevenir fijación de sesión.
     *
     * La fijación de sesión ocurre cuando un atacante fuerza a su
     * víctima a usar un session_id conocido. Al regenerarlo en el
     * login, el ID previo queda invalidado.
     */

  public static function iniciarSesionUsuario(array $usuario):void{

  //Regeneramos el ID antes de guardar datos de usuario

    session_regenerate_id(true); 
        $_SESSION[self::CLAVE_ID]     = $usuario['id'];
        $_SESSION[self::CLAVE_ROL]    = $usuario['rol'];
        $_SESSION[self::CLAVE_NOMBRE] = $usuario['nombre'] . ' ' . $usuario['apellidos'];
        $_SESSION[self::CLAVE_CORREO] = $usuario['correo'];
        $_SESSION[self::CLAVE_ULTIMA] = time();
        $_SESSION[self::CLAVE_IP]     = $_SERVER['REMOTE_ADDR'] ?? '';
  }

  /**
   * Cierra la sesión completamente
   * Elimina la cookie y destruye todos los datos 
  */

  public static function cerrar():void{

    //vaciomos el array de seion
    $_SESSION = [];

    //eliminamos la cookie del navegador del usuario
    if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
  }
  session_destroy();
  }

  //---------Métodos de lectura----------

  public static function estaAutenticado():bool{
    return isset($_SESSION[self::CLAVE_ID]);
  }
  
  public static function obtenerIdUsuario(): ?int
  {
      return isset($session[self::CLAVE_ID])
      ? (int) $_SESSION[self::CLAVE_ID]
      : null;
  }

  public static function obtenerRol (): ?string{
      return $_SESSION[self::CLAVE_ROL] ?? null;
  }

  public static function obtenerNombre(): string {
    return $_SESSION[self::CLAVE_NOMBRE] ?? 'Invitado';
  }
   public static function obtenerCorreo(): ?string
    {
        return $_SESSION[self::CLAVE_CORREO] ?? null;
    }
 
    public static function esAlumno(): bool
    {
        return self::obtenerRol() === ROL_ALUMNO;
    }
 
    public static function esProfesor(): bool
    {
        return self::obtenerRol() === ROL_PROFESOR;
    }
 
    public static function esAdmin(): bool
    {
        return self::obtenerRol() === ROL_ADMIN;
    }

    /**
     * Devuelve el token de sesión hasheado.
     * Se usa como identificador en la tabla consultas_ia
     * sin exponer el ID real de sesión.
     */

    public static function obtenerTokenSesion(): string
    {
        return hash('sha256', session_id());
    }

     // ── Mensajes flash ───────────────────────────────────────
    // Los mensajes flash se muestran UNA sola vez y desaparecen.
    // Útil para: "Curso creado correctamente", "Contraseña incorrecta", etc.
 
    public static function flash(string $tipo, string $mensaje): void
    {
        $_SESSION['flash'][$tipo] = $mensaje;
    }
    public static function obtenerFlash(string $tipo): ?string
    {
        if (!isset($_SESSION['flash'][$tipo])) return null;
        $mensaje = $_SESSION['flash'][$tipo];
        unset($_SESSION['flash'][$tipo]);   // Se elimina tras leerlo
        return $mensaje;
    }
    public static function hayFlash(string $tipo): bool
    {
        return isset($_SESSION['flash'][$tipo]);
    }

    // ── Seguridad ────────────────────────────────────────────
 
    /**
     * Cierra la sesión si lleva más tiempo del permitido sin actividad.
     * Por defecto: SESION_DURACION_HORAS horas (definido en constantes.php)
     */
    private static function verificarExpiracion(): void
    {
        $duracionSegundos = SESION_DURACION_HORAS * 3600;
        $ultimaActividad  = $_SESSION[self::CLAVE_ULTIMA] ?? 0;
 
        if ((time() - $ultimaActividad) > $duracionSegundos) {
            self::cerrar();
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login?razon=expiracion');
            exit;
        }
 
        // Actualizamos el timestamp de última actividad
        $_SESSION[self::CLAVE_ULTIMA] = time();
    }

     /**
     * Cierra la sesión si la IP cambia durante la misma sesión.
     * Previene el robo de sesión desde otra red.
     */
    private static function verificarIP(): void
    {
        $ipActual   = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipSesion   = $_SESSION[self::CLAVE_IP] ?? '';
 
        if ($ipSesion && $ipActual !== $ipSesion) {
            error_log("[Seguridad] Cambio de IP en sesión. Original: {$ipSesion} | Actual: {$ipActual}");
            self::cerrar();
            header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/login?razon=seguridad');
            exit;
        }
    }

}
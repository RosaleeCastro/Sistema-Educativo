<?php
// fuente/modelos/Usuario.php
// ============================================================
// Gestiona todo lo relacionado con los usuarios:
//   - Autenticación (login con bcrypt)
//   - Registro de nuevos usuarios
//   - Búsqueda por rol (listar alumnos, profesores)
//   - Control de intentos de login (anti fuerza bruta)
// ============================================================

declare(strict_types=1);

namespace App\Modelos;

use PDO;

class Usuario extends ModeloBase
{
    protected string $tabla = 'usuarios';

    // Solo estos campos se pueden insertar o actualizar
    // desde fuera — el campo 'rol' solo lo toca el admin
    protected array $camposPermitidos = [
        'nombre', 'apellidos', 'correo',
        'contrasena', 'rol', 'activo', 'avatar_url',
    ];

    // ── Autenticación ────────────────────────────────────────

    /**
     * Busca un usuario por correo y verifica su contraseña.
     * Devuelve los datos del usuario si es correcto, null si no.
     *
     * password_verify() compara el texto plano con el hash bcrypt
     * de forma segura (resistente a timing attacks).
     */
    public function autenticar(string $correo, string $contrasena): ?array
    {
        $stmt = $this->bd->prepare(
            'SELECT id, nombre, apellidos, correo, contrasena, rol, activo
               FROM usuarios
              WHERE correo = :correo
              LIMIT 1'
        );
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();

        // Si no existe el correo o el usuario está desactivado → null
        if (!$usuario || !$usuario['activo']) return null;

        // Verificamos la contraseña contra el hash almacenado
        if (!password_verify($contrasena, $usuario['contrasena'])) return null;

        // Si el hash antiguo necesita ser actualizado (cambio de coste bcrypt)
        // lo regeneramos automáticamente sin que el usuario note nada
        if (password_needs_rehash($usuario['contrasena'], PASSWORD_BCRYPT)) {
            $nuevoHash = password_hash($contrasena, PASSWORD_BCRYPT);
            $this->actualizar($usuario['id'], ['contrasena' => $nuevoHash]);
        }

        // Nunca devolvemos la contraseña hasheada al controlador
        unset($usuario['contrasena']);
        return $usuario;
    }

    /**
     * Registra un nuevo usuario hasheando su contraseña.
     * Devuelve el ID del usuario creado.
     */
    public function registrar(array $datos): int
    {
        // Nunca guardamos la contraseña en texto plano
        $datos['contrasena'] = password_hash(
            $datos['contrasena'],
            PASSWORD_BCRYPT,
            ['cost' => 12]  // Coste 12: buen equilibrio seguridad/velocidad en 2024
        );

        return $this->crear($datos);
    }

    /**
     * Cambia la contraseña de un usuario verificando la actual.
     */
    public function cambiarContrasena(int $id, string $actual, string $nueva): bool
    {
        $usuario = $this->buscarPorId($id);
        if (!$usuario) return false;

        if (!password_verify($actual, $usuario['contrasena'])) return false;

        $nuevoHash = password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]);
        return $this->actualizar($id, ['contrasena' => $nuevoHash]);
    }

    // ── Búsquedas ─────────────────────────────────────────────

    /**
     * Devuelve todos los usuarios con un rol concreto.
     * Uso: $modelo->listarPorRol('alumno')
     */
    public function listarPorRol(string $rol): array
    {
        $stmt = $this->bd->prepare(
            'SELECT id, nombre, apellidos, correo, activo, created_at
               FROM usuarios
              WHERE rol = :rol
           ORDER BY apellidos ASC, nombre ASC'
        );
        $stmt->execute([':rol' => $rol]);
        return $stmt->fetchAll();
    }

    /**
     * Busca usuarios por nombre, apellidos o correo.
     * Usado por el buscador interno del admin.
     */
    public function buscar(string $termino): array
    {
        $like = '%' . $termino . '%';
        $stmt = $this->bd->prepare(
            'SELECT id, nombre, apellidos, correo, rol, activo
               FROM usuarios
              WHERE nombre    LIKE :t
                 OR apellidos LIKE :t
                 OR correo    LIKE :t
           ORDER BY apellidos ASC'
        );
        $stmt->execute([':t' => $like]);
        return $stmt->fetchAll();
    }

    /**
     * Devuelve los alumnos inscritos en un curso concreto.
     */
    public function alumnosDeUnCurso(int $cursoId): array
    {
        $stmt = $this->bd->prepare(
            'SELECT u.id, u.nombre, u.apellidos, u.correo,
                    i.created_at AS fecha_inscripcion
               FROM usuarios u
               JOIN inscripciones i ON i.alumno_id = u.id
              WHERE i.curso_id = :curso_id
                AND u.rol = :rol
           ORDER BY u.apellidos ASC'
        );
        $stmt->execute([
            ':curso_id' => $cursoId,
            ':rol'      => ROL_ALUMNO,
        ]);
        return $stmt->fetchAll();
    }

    // ── Control de fuerza bruta ──────────────────────────────

    /**
     * Registra un intento fallido de login para una IP + correo.
     */
    public function registrarIntentoFallido(string $ip, string $correo): void
    {
        $stmt = $this->bd->prepare(
            'INSERT INTO intentos_login (ip, correo)
             VALUES (:ip, :correo)'
        );
        $stmt->execute([':ip' => $ip, ':correo' => $correo]);
    }

    /**
     * Cuenta los intentos fallidos recientes (últimos N minutos).
     */
    public function contarIntentosFallidos(string $ip, string $correo): int
    {
        $desde = date('Y-m-d H:i:s', time() - (BLOQUEO_MINUTOS * 60));

        $stmt = $this->bd->prepare(
            'SELECT COUNT(*) FROM intentos_login
              WHERE ip      = :ip
                AND correo  = :correo
                AND created_at >= :desde'
        );
        $stmt->execute([':ip' => $ip, ':correo' => $correo, ':desde' => $desde]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Comprueba si una IP+correo está bloqueada por exceso de intentos.
     */
    public function estaBloqueado(string $ip, string $correo): bool
    {
        return $this->contarIntentosFallidos($ip, $correo) >= INTENTOS_LOGIN_MAX;
    }

    /**
     * Limpia los intentos fallidos tras un login exitoso.
     */
    public function limpiarIntentos(string $ip, string $correo): void
    {
        $stmt = $this->bd->prepare(
            'DELETE FROM intentos_login
              WHERE ip = :ip AND correo = :correo'
        );
        $stmt->execute([':ip' => $ip, ':correo' => $correo]);
    }

    // ── Estadísticas para el dashboard ───────────────────────

    public function contarPorRol(): array
    {
        $stmt = $this->bd->query(
            'SELECT rol, COUNT(*) AS total
               FROM usuarios
              GROUP BY rol'
        );
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
<?php
// fuente/modelos/ModeloBase.php
// ============================================================
// Clase padre de todos los modelos del sistema.
//
// Centraliza las operaciones más comunes contra la BD:
//   - buscarPorId      → SELECT por clave primaria
//   - listarTodos      → SELECT sin filtros
//   - crear            → INSERT
//   - actualizar       → UPDATE por id
//   - eliminar         → DELETE por id
//   - existeRegistro   → SELECT COUNT para verificar existencia
//
// Cada modelo hijo declara su $tabla y sus $camposPermitidos
// y hereda todo esto automáticamente.
//
// Los $camposPermitidos actúan como lista blanca:
// evitan que alguien inyecte columnas extra en un INSERT/UPDATE
// ============================================================

declare(strict_types=1);

namespace App\Modelos;

use App\Configuracion\BaseDatos;
use PDO;

abstract class ModeloBase
{
    protected PDO    $bd;
    protected string $tabla;           // Nombre de la tabla en MySQL
    protected string $clavePrimaria = 'id';
    protected array  $camposPermitidos = []; // Lista blanca de columnas

    public function __construct()
    {
        $this->bd = BaseDatos::obtenerConexion();
    }

    // ── Lectura ──────────────────────────────────────────────

    /**
     * Busca un registro por su ID.
     * Devuelve el array con los datos o null si no existe.
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->bd->prepare(
            "SELECT * FROM {$this->tabla}
              WHERE {$this->clavePrimaria} = :id
              LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Devuelve todos los registros de la tabla.
     * Acepta un ORDER BY opcional.
     */
    public function listarTodos(string $ordenarPor = 'id', string $direccion = 'ASC'): array
    {
        // Saneamos el campo de ordenación para evitar inyección en ORDER BY
        // (PDO no permite parámetros en ORDER BY)
        $columnaSegura   = preg_replace('/[^a-zA-Z0-9_]/', '', $ordenarPor);
        $direccionSegura = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';

        $stmt = $this->bd->query(
            "SELECT * FROM {$this->tabla}
             ORDER BY {$columnaSegura} {$direccionSegura}"
        );
        return $stmt->fetchAll();
    }

    // ── Escritura ────────────────────────────────────────────

    /**
     * Inserta un nuevo registro.
     * Solo usa los campos que están en $camposPermitidos.
     * Devuelve el ID del registro creado.
     */
    public function crear(array $datos): int
    {
        $datosFiltrados = $this->filtrarCampos($datos);

        if (empty($datosFiltrados)) {
            throw new \InvalidArgumentException(
                "No hay campos válidos para insertar en {$this->tabla}"
            );
        }

        $columnas    = implode(', ', array_keys($datosFiltrados));
        $marcadores  = implode(', ', array_map(fn($c) => ":{$c}", array_keys($datosFiltrados)));

        $stmt = $this->bd->prepare(
            "INSERT INTO {$this->tabla} ({$columnas}) VALUES ({$marcadores})"
        );

        // Renombramos las claves para los marcadores con ':'
        $parametros = [];
        foreach ($datosFiltrados as $campo => $valor) {
            $parametros[":{$campo}"] = $valor;
        }

        $stmt->execute($parametros);
        return (int) $this->bd->lastInsertId();
    }

    /**
     * Actualiza un registro existente por su ID.
     * Solo toca los campos que están en $camposPermitidos.
     * Devuelve true si se actualizó al menos una fila.
     */
    public function actualizar(int $id, array $datos): bool
    {
        $datosFiltrados = $this->filtrarCampos($datos);

        if (empty($datosFiltrados)) return false;

        $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($datosFiltrados)));

        $stmt = $this->bd->prepare(
            "UPDATE {$this->tabla}
                SET {$sets}
              WHERE {$this->clavePrimaria} = :__id"
        );

        $parametros = [':__id' => $id];
        foreach ($datosFiltrados as $campo => $valor) {
            $parametros[":{$campo}"] = $valor;
        }

        $stmt->execute($parametros);
        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina un registro por su ID.
     * Devuelve true si se eliminó.
     */
    public function eliminar(int $id): bool
    {
        $stmt = $this->bd->prepare(
            "DELETE FROM {$this->tabla}
              WHERE {$this->clavePrimaria} = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ── Verificación ─────────────────────────────────────────

    /**
     * Comprueba si existe un registro con un valor en una columna.
     * Uso: $this->existeRegistro('correo', 'laura@edu.com')
     */
    public function existeRegistro(string $columna, mixed $valor, ?int $excepto = null): bool
    {
        $columnaSegura = preg_replace('/[^a-zA-Z0-9_]/', '', $columna);
        $sql = "SELECT COUNT(*) FROM {$this->tabla} WHERE {$columnaSegura} = :valor";

        // Si pasamos un ID en $excepto, excluimos ese registro
        // Útil al editar: "¿existe este correo en otro usuario distinto?"
        if ($excepto !== null) {
            $sql .= " AND {$this->clavePrimaria} != :excepto";
        }

        $stmt = $this->bd->prepare($sql);
        $params = [':valor' => $valor];
        if ($excepto !== null) $params[':excepto'] = $excepto;

        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cuenta el total de registros de la tabla.
     */
    public function contar(): int
    {
        return (int) $this->bd->query(
            "SELECT COUNT(*) FROM {$this->tabla}"
        )->fetchColumn();
    }

    // ── Utilidad interna ─────────────────────────────────────

    /**
     * Filtra el array de datos dejando solo las columnas permitidas.
     * Es la defensa contra inserciones de columnas no autorizadas.
     */
    private function filtrarCampos(array $datos): array
    {
        if (empty($this->camposPermitidos)) return $datos;

        return array_filter(
            $datos,
            fn($clave) => in_array($clave, $this->camposPermitidos, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
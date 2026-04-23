<?php
//Gestiona toda la conexión a MySQL usando PDO.
//
//Patrón SIngleton : garantiza que solo tenga una conexión activa en toda la petición
// no importya cuantos modelos lo llamen. Evita abrir conexiones innecesarias.
//
//Uso desde cualquier modelo:
// $pdo = BaseDatos::obtenerConexión();
// $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id);
//---------------------------




declare(strict_types=1);

namespace App\Configuracion;

use PDO;
use PDOException;

final class BaseDatos
{
   //la única instacia que existirá en toda la app
  private static ?PDO $instancia = null;

  //Bloqueamos la instanciación directa y la clonación 
  //nadie puede hacer : new Basedatos() - solo BaseDatos::obtenerConexion();
  private function __construct() {}
  private function __clone()     {}

  /**
   * Devuelve la conexion PDO activa
   * Si aun no existe, la crea. Si ya existe la reutiliza 
   */
  
}

?>
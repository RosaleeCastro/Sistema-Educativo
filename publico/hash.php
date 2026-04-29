<?php
$hash = password_hash('password123', PASSWORD_BCRYPT);
echo $hash;
echo "<br><br>";
echo "Verificación: ";
echo password_verify('password123', $hash) ? "CORRECTO ✅" : "FALLO ❌";
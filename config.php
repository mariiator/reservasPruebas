<?php
// config.php
declare(strict_types=1);

//ini_set('display_errors', '1');
//error_reporting(E_ALL);

// Autoload (ajustado a tu estructura)
require __DIR__ . '/vendor/autoload.php';

/**
 * === AJUSTA SOLO ESTO SI CAMBIA TU ENTORNO ===
 */
$CAS_HOST          = 'login01.globaleduca.com';           // Host del CAS del proveedor
$CAS_PORT          = 443;                                  // Normalmente 443
$CAS_CONTEXT       = '/mi010';                             // Contexto CAS que te da el proveedor
$SERVICE_BASE_URL  = 'https://reservas.maristaschamberi.com/'; // Base pública de tu app
$CAS_CA_CHAIN_FILE = __DIR__ . '/cas-ca-chain.pem';     // Ruta al PEM de la CA del CAS
$USE_CAS_V3        = false;                                 // CAS 3.0 suele ser lo normal

/**
 * (Opcional) Log de phpCAS para depurar
 * Descomenta en producción solo si lo necesitas.
 */
/*COMENTADO POR MARIA
phpCAS::setDebug(__DIR__ . '/log/phpcas.log');
phpCAS::setVerbose(true);
*/

/**
 * IMPORTANTE:
 * En phpCAS >= 1.6 el 5º parámetro de client() es la Service Base URL
 * (string o array). No pases boolean.
 * Dejamos que phpCAS gestione la sesión internamente (no hacemos session_start()).
 */
/*COMENTADO POR MARIA
phpCAS::client(
  $USE_CAS_V3 ? CAS_VERSION_3_0 : CAS_VERSION_2_0,
  $CAS_HOST,
  $CAS_PORT,
  $CAS_CONTEXT,
  $SERVICE_BASE_URL
);
*/

/**
 * Validación TLS del servidor CAS (PRODUCCIÓN)
 */
/*COMENTADO POR MARIA
if (!is_file($CAS_CA_CHAIN_FILE)) {
  throw new RuntimeException("No se encuentra el fichero de CA del CAS: {$CAS_CA_CHAIN_FILE}");
}
phpCAS::setCasServerCACert($CAS_CA_CHAIN_FILE);
*/

/**
 * URL de servicio FIJA.
 * Usa la ruta EXACTA por la que accedes (evita perder el ?ticket=... en redirecciones).
 * En tu caso: /profes/prueba.php
 */
/*COMENTADO POR MARIA
phpCAS::setFixedServiceURL('https://reservas.maristaschamberi.com/');
*/

/**
 * (Opcional) Single Logout si el proveedor lo soporta
 */
/*COMENTADO POR MARIA
phpCAS::handleLogoutRequests(true, [$CAS_HOST]);
*/

?>
<?php
//Note: This file should be included first in every php page.
require_once ('environment.php');

if (defined('TYPE') && in_array(strtolower(TYPE), ['dev', 'development', 'debug'], true)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

define('BASE_PATH', dirname(dirname(__FILE__)));
require_once BASE_PATH . '/helpers/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = isHttpsRequest();
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if ($isSecure) {
        ini_set('session.cookie_secure', '1');
    }
}

if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: frame-ancestors 'self'");
    if (isHttpsRequest()) {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}

define('CURRENT_PAGE', basename($_SERVER['REQUEST_URI']));
define('SCRIPT_NAME', ltrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

if(SCRIPT_NAME === "")
    define('SCRIPT_FOLDER', "");
else
    define('SCRIPT_FOLDER', "/" . SCRIPT_NAME);

require_once BASE_PATH . '/lib/MysqliDb/MysqliDb.php';

/* SAVED QR CODES */
//You can change the folder where the qr code will be saved
define('SAVED_QRCODE_FOLDER', './saved_qrcode/');
define('SAVED_QRCODE_DIRECTORY', BASE_PATH.'/saved_qrcode/');
define('SAVED_QRCODE_URL', base_url(). SCRIPT_FOLDER .'/saved_qrcode/');

//You can change the page name for the redirect and the search parameter (the default is "id")
define('READ_PATH', base_url().'/read.php?id=');


/**
 * Get instance of DB object
 */
function getDbInstance() {
    return new MysqliDb (Array (
        'host' => DATABASE_HOST,
        'username' => DATABASE_USER,
        'password' => DATABASE_PASSWORD,
        'db'=> DATABASE_NAME,
        'port' => DATABASE_PORT,
        'prefix' => DATABASE_PREFIX,
        'charset' => DATABASE_CHARSET));
}

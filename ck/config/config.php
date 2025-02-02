<?php

// Definujem BASE_URL iba ak ešte nie je definované
if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim('/ck/', '/') . '/');
}

// Definujem konštanty iba ak ešte nie sú definované
if (!defined('DB_SERVER')) {
    define('DB_SERVER', 'localhost');
}
if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', 'root');
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', 'root');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'ck');
}
?>
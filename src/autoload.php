<?php

// Default environment
define("__path__", realpath(__DIR__ . "/.."));
define("__current_version__", "v1");

// Date format / timezone
define("__DATE_FORMAT__", "Y-d-m\TG:i:s.u\ZP");
define("__DATE_TIMEZONE__", "Europe/Paris");
date_default_timezone_set(__DATE_TIMEZONE__);

// Require composer
require __path__ . '/vendor/autoload.php';

// Load environment variables
$Dotenv = new \Symfony\Component\Dotenv\Dotenv;
$Dotenv->load(__path__ . "/.env");
define("__debug_mode__", (isset($_ENV["DEBUG_MODE"]) && !empty($_ENV["DEBUG_MODE"]) && $_ENV["DEBUG_MODE"] === "true"? true: false));
if (__debug_mode__) {
    $Dotenv->load(__path__ . "/.env.dev");
}

// Show php error when debugmode is enabled
if (__debug_mode__) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
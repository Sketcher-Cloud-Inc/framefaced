#!/usr/bin/env php
<?php

define("__path__", realpath( __DIR__ . "/../../.."));

// Load composer
require __path__ . "/vendor/autoload.php";

// Load environment variables
$Dotenv = new \Symfony\Component\Dotenv\Dotenv;
$Dotenv->load(__path__ . "/.env");
define("__debug_mode__", (isset($_ENV["DEBUG_MODE"]) && !empty($_ENV["DEBUG_MODE"]) && $_ENV["DEBUG_MODE"] === "true"? true: false));
if (__debug_mode__) {
    $Dotenv->load(__path__ . "/.env.dev");
}

$databases = json_decode(file_get_contents(__path__ . "/src/conf/databases.json"), false) ?? [];
foreach ($databases as $db => &$database) {
    $dbname             = strtoupper($db);
    $database->access   = (object) [
        "username" => (isset($_ENV["DATABASE_{$dbname}_USER"]) && !empty($_ENV["DATABASE_{$dbname}_USER"])? $_ENV["DATABASE_{$dbname}_USER"]: null),
        "password" => (isset($_ENV["DATABASE_{$dbname}_PASSWORD"]) && !empty($_ENV["DATABASE_{$dbname}_PASSWORD"])? $_ENV["DATABASE_{$dbname}_PASSWORD"]: null)
    ];
    if (!empty($database->access->username)) {
        echo "[\e[94mPROCESSING\e[39m] Database \"{$db}\" creation has been processing.\n";
        echo shell_exec("mysql -e 'CREATE DATABASE example;' -u{$database->access->username} -p{$database->access->password}");
    } else {
        echo "[\e[91mERROR\e[39m] Unable to create database \"{$db}\" !";
        exit(0);
    }
}
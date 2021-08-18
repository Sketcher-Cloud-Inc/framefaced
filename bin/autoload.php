<?php

spl_autoload_register(function ($ClassName) {
    [ $namespace, $ClassName ] = explode("\\", $ClassName);
    if ($namespace === "Console") {
        $path = realpath(__path__ . "/bin/methods/{$ClassName}.php");
        if (file_exists($path)) {
            include $path;
            return;
        }
        echo "[\e[91mERROR\e[39m] Unable to locate class file \"$path\" !";
    }
});
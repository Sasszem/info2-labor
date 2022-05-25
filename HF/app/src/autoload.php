<?php

/**
 * Simple autolader checking in a fix set of folders and trying the first matching file
 */
function autoloadHandler($classPath)
{
    $autoload_paths = [
        'view',
        'model',
        'controller',
        'dev',
        'component',
    ];
    $parts = explode('\\', $classPath);
    $className = end($parts);
    //echo "looking for class $className<br>";

    foreach ($autoload_paths as $apath) {
        $filename = "$apath/$className.php";
        if (is_readable($filename)) {
            require_once $filename;
            return;
        }
    }
}
spl_autoload_register("autoloadHandler");

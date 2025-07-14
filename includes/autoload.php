<?php
spl_autoload_register('myAutoLoader');

function myAutoLoader($classname) {
    $extension = ".php";
    
    // Use __DIR__ to get the absolute path of the autoload.php file
    $basePath = __DIR__ . "/.."; // Go up one level from includes/
    
    // Define possible locations for your classes
    $paths = [
        $basePath . "/classes/",     // Project root/classes/
        $basePath . "/includes/",    // Project root/includes/
    ];
    
    foreach ($paths as $path) {
        $fullpath = $path . $classname . $extension;
        if (file_exists($fullpath)) {
            include_once $fullpath;
            return;
        }
    }
    
    // If class not found, log error
    error_log("Class not found: $classname");
}
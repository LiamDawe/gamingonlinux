<?php
/**
* Attempt to automatically load a class based on name
* @param string $classname The name of class to load
**/
spl_autoload_register(function ($class)
{
	$base_dir = realpath(dirname(__FILE__));
	$file = $base_dir . DIRECTORY_SEPARATOR . 'class_' . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
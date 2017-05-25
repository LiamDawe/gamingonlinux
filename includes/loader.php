<?php

/**
* Attempt to automatically load a class based on name
* @param string $classname The name of class to load
**/
function loadClass($classname){
	$basePath = realpath(dirname(__FILE__));
	$incDir = $basePath .  DIRECTORY_SEPARATOR;
	
	if (strpos($classname, '\\') !== false){
		if (file_exists( $incDir . str_replace("\\", DIRECTORY_SEPARATOR, $classname) . ".php" )) {
			include $incDir . str_replace("\\", DIRECTORY_SEPARATOR, $classname) . ".php";
			return true;
		}
	}
	$modelDir = $incDir .  "models" . DIRECTORY_SEPARATOR;
	if (file_exists( $incDir . "class_" . $classname . ".php" )){
		include $incDir . "class_" . $classname . ".php";
		return true;
	} else if (file_exists( $modelDir . "model_" . $classname . ".php" )) {
		include $modelDir . "model_" . $classname . ".php";
		return true;
	}
}

spl_autoload_register("loadClass");
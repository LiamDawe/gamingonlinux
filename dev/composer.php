<?php

if (!file_exists("composer.phar")){
	// Download composer
	copy('https://getcomposer.org/installer', 'composer-setup.php');
	$sig = file_get_contents("https://composer.github.io/installer.sig");
	if (hash_file('SHA384', 'composer-setup.php') === $sig) { 
		echo 'Composer Installer verified'.PHP_EOL; 
	}
	else
	{
		echo 'Composer Installer corrupt'.PHP_EOL;
		unlink('composer-setup.php');
		exit(8);
	}
	exec("php composer-setup.php");
	unlink('composer-setup.php');
}

chdir( realpath( dirname(__FILE__) . "/../" ) );

echo "Now downloading Composer dependancies.".PHP_EOL;
exec("php dev/composer.phar install -n --no-suggest --no-progress");

exit(0);
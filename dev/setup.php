<?php
error_reporting(E_ALL);
define("DOCKER", getenv("DOCKER") === "yes" );

$file_dir = dirname(dirname(__FILE__));
if (DOCKER){
	copy($file_dir."/dev/config.php", $file_dir . '/includes/config.php');
}

function say($str, ...$args){
	echo sprintf($str."\n", ...$args);
}

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_mysql.php');

class setup_mysql extends mysql {

	function pdo_error($exceptionMsg, $page, $sql, $url)
	{
		throw new Exception($exceptionMsg);
	}
}

$db = new setup_mysql($db_conf['host'], $db_conf['username'], $db_conf['password'], $db_conf['database']);

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);


// Check if DB is already setup
function isDbSetup(){
	global $db;
	try {
		$q = $db->sqlquery("SELECT * FROM `config` WHERE `data_key` = 'website_url'");
		return $q->num_rows > 0;
	} catch (Exception $error) {
		return false;
	}
}

if ( !isDbSetup() ){
	say("Setting up DB");

	$sql = file($file_dir . "/tools/SQL.sql");
	if (empty($sql)){ say("! SQL file not found! Aborting setup"); return exit(22); }
	$query = "";
	foreach ($sql as $line) {
		if (empty($line) || substr($line,0,1) == "-"){ continue; } // Skip empty
		if (strpos($line, "DROP") === 0) { continue; } // Skip drop tables
		if (strpos($line, "CREATE TABLE") === 0) {
			$line = str_replace("CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $line);
		}
		$query .= trim($line, "\n");

		if (substr( trim($line, "\n"), -1) == ";"){
			say("\tExecuting query: '%s'", str_replace("\n", "", $query) );
			try {
				$db->sqlquery($query);
			} catch (Exception $e){
				say("!! SQL failed to run. Query has been skipped. Error: %s", $e->getMessage());
			}
			$query = "";
		}
	}
	$db->sqlquery("INSERT INTO `users` SET `username` = ?, `password` = ?, `email` = ?, `gravatar_email` = ?, `user_group` = 3, `secondary_user_group` = 3, `ip` = ?, `register_date` = ?, `last_login` = ?, `theme` = 'default',`activated` = 1, `activation_code` = ?",
		[
			"devuser",
			"golpassword",
			"gol@leviv.nu",
			"contact@gamingonlinux.com",
			"127.0.0.1",
			core::$date,
			core::$date,
			"activate",
		]);

	say("Created new user %s, with password %s", "devuser", "golpassword");
	$db->sqlquery("INSERT INTO `config` (`data_key`, `data_value`) VALUES ('site_title', 'DEV SITE GOL')");
} else {
	say("DB already setup");
}

say("Setup completed");
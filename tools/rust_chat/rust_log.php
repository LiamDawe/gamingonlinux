<?php 
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ));
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include 'config.php';
include $file_dir . '/includes/config.php';
include $file_dir . '/includes/class_db_mysql.php';
$dbl = new db_mysql();

$file_date = date("d.m.Y-G:i:s");

// define some variables
$local_file = $file_dir . '/tools/rust_chat/logs/rust_log.txt.'.$file_date;
$server_file = '/oxide/logs/Logger/logger_chat-' . date('Y-m-d') . '.txt';

// set up basic connection
$conn_id = ftp_connect($ftp['ip']);

if ($conn_id === false)
{
	die("Couldn't connect");
}

// login with username and password
$login_result = ftp_login($conn_id, $ftp['username'], $ftp['password']);

// try to download $server_file and save to $local_file
if (ftp_get($conn_id, $local_file, $server_file, FTP_BINARY)) 
{
	$lines = file($local_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line)
	{
		preg_match("/\[([0-9]{1,2}\/[0-9]{2}\/[0-9]{4} [0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2})\] (.*)\(.*\): (.*)/", $line, $matches);
		echo $matches[1] . ' - ' . $matches[3] .  '<br>';
		$converted_date = date('Y-m-d H:i:s', strtotime($matches[1]));

		// insert it into the database, as long as it doesn't already exist (since we check the same logs often)
		$check_exists = $dbl->run("SELECT 1 FROM `rust_chat` WHERE `username` = ? AND `date` = ? AND `text` = ?", [$matches[2], $converted_date, $matches[3]])->fetchOne();
		if (!$check_exists)
		{
			$dbl->run("INSERT INTO `rust_chat` SET `username` = ?, `date` = ?, `text` = ?", array($matches[2], $converted_date, $matches[3]));
		}
	}
	unlink($local_file);
}
else
{
	echo "There was a problem\n";
	error_log("RUST SERVER - COULDN'T GET LOG!");
}

// close the connection
ftp_close($conn_id);
?>

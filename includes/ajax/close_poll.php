<?php
session_start();

header('Content-Type: application/json');

$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'],$db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

if($_POST)
{
	// make sure the poll is open
	$check = $dbl->run("SELECT `poll_open` FROM `polls` WHERE `poll_id` = ? AND `author_id` = ?", array($_POST['poll_id'], $_SESSION['user_id']))->fetchOne();
	if ($check == 1)
	{
			$dbl->run("UPDATE `polls` SET `poll_open` = 0 WHERE `poll_id` = ?", array($_POST['poll_id']));

			echo json_encode(array("result" => 1));
			return;
	}
	else
	{
		echo json_encode(array("result" => 2));
		return;
	}
}
?>

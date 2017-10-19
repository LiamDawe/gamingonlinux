<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if (!isset($_SESSION['conflict_checked']))
{
	$_SESSION['conflict_checked'] = $_POST['article_ids'];
}
else
{
	$_SESSION['conflict_checked'] = array_merge($_SESSION['conflict_checked'], $_POST['article_ids']);
}

echo json_encode(array("result" => 'done'));

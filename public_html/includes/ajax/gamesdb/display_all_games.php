<?php
session_start();

define("APP_ROOT", dirname( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$filters = NULL;
if (isset($_GET['filters']))
{
	$filters = $_GET['filters'];
}

$gamedb->display_all_games($filters);

echo $templating->output();
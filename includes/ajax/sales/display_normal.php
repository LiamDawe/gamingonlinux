<?php
session_start();

define("APP_ROOT", dirname( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$game_sales = new game_sales($templating, $user, $core);

$filters = NULL;
if (isset($_GET['filters']))
{
	$filters = $_GET['filters'];
}

$game_sales->display_normal($filters);

echo $templating->output();
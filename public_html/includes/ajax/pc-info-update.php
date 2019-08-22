<?php
session_start();
header('Content-Type: application/json');

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	$dbl->run("UPDATE `user_profile_info` SET `date_updated` = ? WHERE `user_id` = ?", array(gmdate("Y-n-d H:i:s"), $_SESSION['user_id']));

	echo json_encode(array("result" => 1));
	return;
}
// not logged in
else
{
	echo json_encode(array("result" => 2));
	return;
}
?>

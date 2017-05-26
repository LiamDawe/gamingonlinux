<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$user = new user($dbl, $core);
$user->check_session();

if($_POST && isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
	if ($user->delete_user_notification($_POST['note_id']))
	{
		echo json_encode(array("result" => 1));
		return true;
	}
}
?>

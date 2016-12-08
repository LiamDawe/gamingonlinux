<?php
session_start();

include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

include('../class_user.php');
$user = new user();

if($_POST && isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
  if ($user->delete_user_notification($_POST['note_id']))
  {
    echo json_encode(array("result" => 1));
    return true;
  }
}
?>

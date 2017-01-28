<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_user.php');
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

<?php
session_start();

$file_dir = dirname( dirname( dirname(__FILE__) ) );

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'],$db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

include($file_dir . '/includes/class_user.php');
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

<?php
header('Content-Type: application/json');

$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	$db->sqlquery("UPDATE `user_profile_info` SET `date_updated` = ? WHERE `user_id` = ?", array(gmdate("Y-n-d H:i:s"), $_SESSION['user_id']));

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

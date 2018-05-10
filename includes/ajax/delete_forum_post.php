<?php
header('Content-Type: application/json');

session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$forum_class = new forum($dbl, $core, $user);

if($_POST)
{
	if (isset($_POST['type']) && $_POST['type'] == 'reply')
	{	
		if (!isset($_POST['post_id']))
		{
			echo json_encode(array("result" => "no_id"));
			return;
		}
		
		$forum_class->delete_reply($_POST['post_id']);
		echo json_encode(array("result" => "removed"));
		return;
	}
}
?>

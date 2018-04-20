<?php
header('Content-Type: application/json');

session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if($_POST)
{
	if (!isset($_POST['comment_id']) || !core::is_number($_POST['comment_id']))
	{
		echo json_encode(array("result" => "no_id"));
		return;
	}
	
	$article_class->delete_comment($_POST['comment_id']);
	echo json_encode(array("result" => "removed"));
	return;
}
?>

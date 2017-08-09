<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if($_POST && isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
	if (isset($_POST['type']))
	{
		if ($_POST['type'] == 'subscribe')
		{
			$article_class->subscribe($_POST['article-id']);
			echo json_encode(array("result" => "subscribed"));
			return;
		}

		if ($_POST['type'] == 'unsubscribe')
		{
			$article_class->unsubscribe($_POST['article-id']);
			echo json_encode(array("result" => "unsubscribed"));
			return;
		}
	}
}
?>

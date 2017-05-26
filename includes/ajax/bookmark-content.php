<?php
header('Content-Type: application/json');

session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$user = new user($dbl, $core);
$user->check_session();

if($_POST && isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
	if ($_POST['method'] == 'add')
	{
		// find if it exists already
		$finder = $dbl->run("SELECT `data_id` FROM `user_bookmarks` WHERE `data_id` = ? AND `user_id` = ? AND `type` = ?", [$_POST['id'], $_SESSION['user_id'], $_POST['type']])->fetchOne();
		if (!$finder)
		{
			$parent_id = NULL;
			if (isset($_POST['parent_id']) && $_POST['parent_id'] != 0)
			{
				$parent_id = $_POST['parent_id'];
			}
			$dbl->run("INSERT INTO `user_bookmarks` SET `user_id` = ?, `data_id` = ?, `type` = ?, `parent_id` = ?", [$_SESSION['user_id'], $_POST['id'], $_POST['type'], $parent_id]);

			echo json_encode(array("result" => 'added'));
			return;
		}
	}
	if ($_POST['method'] == 'remove')
	{
		// find if it exists already
		$dbl->run("DELETE FROM `user_bookmarks` WHERE `data_id` = ? AND `user_id` = ? AND `type` = ?", [$_POST['id'], $_SESSION['user_id'], $_POST['type']]);
		echo json_encode(array("result" => 'removed'));
		return;
	}
}
?>

<?php
session_start();

header('Content-Type: application/json');

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if($_POST && isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
	if ($_POST['type'] == 'comment')
	{
		$pinsid = $_POST['comment_id'];
		$table = 'likes';
		$field = 'data_id';
		$type_insert = "`type` = 'comment', ";
		$type_delete = "`type` = 'comment' AND ";

	}
	if ($_POST['type'] == 'article')
	{
		$pinsid = $_POST['article_id'];
		$table = 'article_likes';
		$field = 'article_id';
		$type_insert = '';
		$type_delete = '';
	}

	$count_notifications = $dbl->run("SELECT COUNT(*) FROM `$table` WHERE `$field` = ? AND user_id = ?", array($pinsid, $_SESSION['user_id']))->fetchOne();
	if($_POST['sta'] == "like")
	{
		if ($_POST['type'] == 'comment')
		{
			// see if there's a notification already for it
			$get_note = $dbl->run("SELECT `owner_id`, `id`, `total` FROM `user_notifications` WHERE `owner_id` = ? AND `type` = 'liked' AND `comment_id` = ?", array($_POST['author_id'], $_POST['comment_id']))->fetch();
			if ($get_note)
			{
				$dbl->run("UPDATE `user_notifications` SET `date` = ?, `notifier_id` = ?, `seen` = 0, `total` = (total + 1) WHERE `id` = ?", array(core::$date, $_SESSION['user_id'], $get_note['id']));
			}
			else
			{
				$dbl->run("INSERT INTO `user_notifications` SET `date` = ?, `owner_id` = ?, `notifier_id` = ?, `article_id` = ?, `comment_id` = ?, `type` = 'liked', `total` = 1", array(core::$date, $_POST['author_id'], $_SESSION['user_id'], $_POST['article_id'], $_POST['comment_id']));
			}
		}
		// insert the actual "like" row
		if ($count_notifications == 0)
		{
			$dbl->run("INSERT INTO `$table` SET $type_insert `$field` = ?, `user_id` = ?, `date` = ?", array($pinsid, $_SESSION['user_id'], core::$date));
			
			$total_likes = $dbl->run("SELECT COUNT(like_id) FROM `$table` WHERE `$field` = ?", array($pinsid))->fetchOne();
			
			echo json_encode(array("result" => 'liked', 'total' => $total_likes));
			return true;
		}
		echo 2; //Bad Checknum
		return true;
	}
	else if($_POST['sta'] == "unlike")
	{
		if ($count_notifications > 0)
		{
			if ($_POST['type'] == 'comment')
			{
				// see if there's any left already for it
				$current_likes = $dbl->run("SELECT `owner_id`, `id`, `total`, `seen`, `seen_date` FROM `user_notifications` WHERE `owner_id` = ? AND `type` = 'liked' AND `comment_id` = ?", array($_POST['author_id'], $_POST['comment_id']))->fetch();
				if ($current_likes['total'] >= 2)
				{
					// find the last available like now (second to last row)
					$last_like = $dbl->run("SELECT `user_id`, `data_id`, `date` FROM `likes` WHERE `data_id` = ? ORDER BY `date` DESC LIMIT 1 OFFSET 1", array($_POST['comment_id']))->fetch();
					
					$seen = '';
					// if the last time they saw this like notification was before the date of the new last like, they haven't seen it
					if ($last_like['date'] > $current_likes['seen_date'])
					{
						$seen = 0;
					}
					else
					{
						$seen = 1;
					}

					$dbl->run("UPDATE `user_notifications` SET `date` = ?, `notifier_id` = ?, `seen` = ?, `total` = (total - 1) WHERE `id` = ?", array($last_like['date'], $last_like['user_id'], $seen, $current_likes['id']));
				}
				// it's the only one, so just delete the notification to completely remove it
				else if ($current_likes['total'] == 1)
				{
					$dbl->run("DELETE FROM `user_notifications` WHERE `id` = ?", array($current_likes['id']));
				}
			}
			$dbl->run("DELETE FROM `$table` WHERE $type_delete `$field` = ? AND user_id = ?", array($pinsid, $_SESSION['user_id']));
			
			$total_likes = $dbl->run("SELECT COUNT(like_id) FROM `$table` WHERE `$field` = ?", array($pinsid))->fetchOne();
			
			echo json_encode(array("result" => 'unliked', 'total' => $total_likes));
			return true;
		}
		echo 2; //Bad Checknum
		return true;
	}
	echo 3; //Bad Status
	return true;
}
echo json_encode(array("result" => 'nope'));

return true;
?>

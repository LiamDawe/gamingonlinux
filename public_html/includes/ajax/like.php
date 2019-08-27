<?php
session_start();

header('Content-Type: application/json');

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

if($_POST && isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
	$types_allowed = array('comment', 'forum_topic', 'forum_reply', 'article');
	if (!isset($_POST['type']) || !in_array($_POST['type'], $types_allowed))
	{
		die('Not allowed.');
	}

	if ($_POST['type'] == 'comment')
	{
		$item_id = $_POST['comment_id'];
		$main_table = 'articles_comments';
		$main_table_id_field = 'comment_id';
		$table = 'likes';
		$like_sql_field = 'data_id';
		$type_insert = "`type` = 'comment', ";
		$type_delete = "`type` = 'comment' AND ";
		$notification_type = 'liked';
		$notification_sql_field = 'comment_id';
		$additional_sql = ', `article_id` = ?';
		$additional_data = $_POST['article_id'];
	}
	if ($_POST['type'] == 'forum_topic')
	{
		$item_id = $_POST['comment_id'];
		$main_table = 'forum_topics';
		$main_table_id_field = 'topic_id';
		$table = 'likes';
		$like_sql_field = 'data_id';
		$type_insert = "`type` = 'forum_topic', ";
		$type_delete = "`type` = 'forum_topic' AND ";
		$notification_type = 'liked_forum_topic';
		$notification_sql_field = 'forum_topic_id';
		$additional_sql = NULL;
		$additional_data = NULL;
	}
	if ($_POST['type'] == 'forum_reply')
	{
		$item_id = $_POST['comment_id'];
		$main_table = 'forum_replies';
		$main_table_id_field = 'post_id';
		$table = 'likes';
		$like_sql_field = 'data_id';
		$type_insert = "`type` = 'forum_reply', ";
		$type_delete = "`type` = 'forum_reply' AND ";
		$notification_type = 'liked_forum_reply';
		$notification_sql_field = 'forum_reply_id';
		$additional_sql = ', `forum_topic_id` = ?';
		$additional_data = $_POST['topic_id'];
	}
	if ($_POST['type'] == 'article')
	{
		$item_id = $_POST['article_id'];
		$main_table = 'articles';
		$main_table_id_field = 'article_id';
		$table = 'article_likes';
		$like_sql_field = 'article_id';
		$type_insert = '';
		$type_delete = '';
	}

	$count_notifications = $dbl->run("SELECT COUNT(*) FROM `$table` WHERE `$like_sql_field` = ? AND `user_id` = ?", array($item_id, $_SESSION['user_id']))->fetchOne();
	if($_POST['sta'] == "like")
	{
		// deal with notifications, either add a new one or update
		if ($_POST['type'] == 'comment' || $_POST['type'] == 'forum_topic' || $_POST['type'] == 'forum_reply')
		{
			// first, check they even want like notifications
			$author_likes = $dbl->run("SELECT `display_like_alerts` FROM `users` WHERE `user_id` = ?", array($_POST['author_id']))->fetchOne();
			if ($author_likes == 1)
			{
				// see if there's a notification already for it
				$get_note = $dbl->run("SELECT `owner_id`, `id`, `total` FROM `user_notifications` WHERE `owner_id` = ? AND `type` = ? AND `$notification_sql_field` = ?", array($_POST['author_id'], $notification_type, $_POST['comment_id']))->fetch();
				if ($get_note)
				{
					$dbl->run("UPDATE `user_notifications` SET `last_date` = ?, `notifier_id` = ?, `seen` = 0, `total` = (total + 1) WHERE `id` = ?", array(core::$sql_date_now, $_SESSION['user_id'], $get_note['id']));
				}
				else
				{
					$new_notification_sql = "INSERT INTO `user_notifications` SET `owner_id` = ?, `notifier_id` = ?, `$notification_sql_field` = ?, `type` = ?, `total` = 1 $additional_sql";

					$notification_data = array($_POST['author_id'], $_SESSION['user_id'], $item_id, $notification_type);

					if (isset($additional_data))
					{
						$notification_data = array_merge($notification_data, [$additional_data]);
					}

					$dbl->run($new_notification_sql, $notification_data);
				}
			}
		}
		// insert the actual "like" row, update counter
		if ($count_notifications == 0)
		{
			$dbl->run("INSERT INTO `$table` SET $type_insert `$like_sql_field` = ?, `user_id` = ?, `date` = ?", array($item_id, $_SESSION['user_id'], core::$date));

			$dbl->run("UPDATE `$main_table` SET `total_likes` = (total_likes + 1) WHERE `$main_table_id_field` = ?", array($item_id));
			$total_likes = $dbl->run("SELECT `total_likes` FROM `$main_table` WHERE `$main_table_id_field` = ?", array($item_id))->fetchOne();

			echo json_encode(array("result" => 'liked', 'total' => $total_likes, 'type' => $_POST['type']));
			return true;
		}
		echo 2; //Bad Checknum
		return true;
	}
	else if($_POST['sta'] == "unlike")
	{
		if ($count_notifications > 0)
		{
			if ($_POST['type'] == 'comment' || $_POST['type'] == 'forum_topic')
			{
				// see if there's any left already for it
				$current_likes = $dbl->run("SELECT `owner_id`, `id`, `total`, `seen`, `seen_date` FROM `user_notifications` WHERE `owner_id` = ? AND `type` = '$notification_type' AND `$notification_sql_field` = ?", array($_POST['author_id'], $_POST['comment_id']))->fetch();
				if ($current_likes['total'] >= 2)
				{
					// find the last available like now (second to last row)
					$last_like = $dbl->run("SELECT `user_id`, `date` FROM `likes` WHERE `data_id` = ? ORDER BY `date` DESC LIMIT 1 OFFSET 1", array($_POST['comment_id']))->fetch();

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

					$new_date = date('Y-m-d H:i:s', $last_like['date']); //likes table uses plain int for date format

					$dbl->run("UPDATE `user_notifications` SET `last_date` = ?, `notifier_id` = ?, `seen` = ?, `total` = (total - 1) WHERE `id` = ?", array($new_date, $last_like['user_id'], $seen, $current_likes['id']));
				}
				// it's the only one, so just delete the notification to completely remove it
				else if ($current_likes['total'] == 1)
				{
					$dbl->run("DELETE FROM `user_notifications` WHERE `id` = ?", array($current_likes['id']));
				}
			}
			$dbl->run("DELETE FROM `$table` WHERE $type_delete `$like_sql_field` = ? AND user_id = ?", array($item_id, $_SESSION['user_id']));

			$dbl->run("UPDATE `$main_table` SET `total_likes` = (total_likes - 1) WHERE `$main_table_id_field` = ?", array($item_id));
			$total_likes = $dbl->run("SELECT `total_likes` FROM `$main_table` WHERE `$main_table_id_field` = ?", array($item_id))->fetchOne();

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

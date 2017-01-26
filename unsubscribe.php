<?php
include('includes/header.php');

$user_id = $_GET['user_id'];
$email = $_GET['email'];
$article_id = $_GET['article_id'];

$empty_check = core::mempty(compact('user_id', 'email', 'article_id'));
if($empty_check !== true)
{
	header("Location: home/message=cannotunsubscribe&extra=$empty_check");
	die();
}

if (isset($_GET['user_id']) && is_numeric($_GET['user_id']) && isset($_GET['email']))
{
	$db->sqlquery("SELECT `email` FROM `users` WHERE `user_id` = ? AND `email` = ?", array($_GET['user_id'], $_GET['email']));
	if ($db->num_rows() == 1)
	{
		if (isset($_GET['article_id']) && is_numeric($_GET['article_id']))
		{

			$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_GET['user_id'], $_GET['article_id']));
			header("Location: home/message=unsubscribed");
		}

		else if (isset($_GET['topic_id']) && is_numeric($_GET['topic_id']))
		{
			$db->sqlquery("DELETE FROM `forum_topics_subscriptions` WHERE `user_id` = ? AND `topic_id` = ?", array($_GET['user_id'], $_GET['topic_id']));
			header("Location: home/message=unsubscribed");
		}
	}

	else
	{
		header("Location: home/message=cannotunsubscribe");
	}

}


?>

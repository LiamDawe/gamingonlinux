<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);
include(APP_ROOT . '/includes/header.php');

$user_id = '';
if (isset($_GET['user_id']))
{
	$user_id = (int) $_GET['user_id'];
}
$email = '';
if (isset($_GET['email']))
{
	$email = $_GET['email'];
}
$post_id = '';
if (isset($_GET['article_id']))
{
	$post_id = (int) $_GET['article_id'];
}
else if (isset($_GET['topic_id']))
{
	$post_id = (int) $_GET['topic_id'];
}
$key = '';
if (isset($_GET['secret_key']))
{
	$key = $_GET['secret_key'];
}

$empty_check = core::mempty(compact('user_id', 'email', 'post_id', 'key'));
if($empty_check !== true)
{
	$_SESSION['message'] = 'empty';
	$_SESSION['message_extra'] = $empty_check;
	header("Location: home/");
	die();
}

$user_exists = $dbl->run("SELECT `email` FROM `users` WHERE `user_id` = ? AND `email` = ?", array($_GET['user_id'], $_GET['email']))->fetchOne();
if ($user_exists)
{
	if (isset($_GET['article_id']) && is_numeric($_GET['article_id']))
	{
		// check secret key
		$sub_exists = $dbl->run("SELECT `secret_key` FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ? AND `secret_key` = ?", array($_GET['user_id'], $_GET['article_id'], $_GET['secret_key']))->fetchOne();

		if ($sub_exists)
		{
			$dbl->run("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_GET['user_id'], $_GET['article_id']));
			$_SESSION['message'] = 'unsubscribed';
			header("Location: home/");
			die();
		}
		else
		{
			$_SESSION['message'] = 'cannotunsubscribe';
			header("Location: home/");
			die();
		}
	}

	else if (isset($_GET['topic_id']) && is_numeric($_GET['topic_id']))
	{
		// check secret key
		$sub_exists = $dbl->run("SELECT `secret_key` FROM `forum_topics_subscriptions` WHERE `user_id` = ? AND `topic_id` = ? AND `secret_key` = ?", array($_GET['user_id'], $_GET['topic_id'], $_GET['secret_key']))->fetchOne();

		if ($sub_exists)
		{
			$dbl->run("DELETE FROM `forum_topics_subscriptions` WHERE `user_id` = ? AND `topic_id` = ?", array($_GET['user_id'], $_GET['topic_id']));
			$_SESSION['message'] = 'unsubscribed';
			header("Location: home/");
			die();
		}
		else
		{
			$_SESSION['message'] = 'cannotunsubscribe';
			header("Location: home/");
			die();
		}
	}
}

else
{
	$_SESSION['message'] = 'cannotunsubscribe';
	header("Location: home/");
	die();
}
?>
<?php
$templating->set_previous('title', 'Unlike everything', 1);

if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
	if (!isset($_POST['yes']) && !isset($_POST['no']))
	{
		$core->yes_no('Are you sure you want to unlike all comments and articles? THIS CANNOT BE UNDONE', 'index.php?module=unlike_all');
	}

	else if (isset($_POST['no']))
	{
		header("Location: " . $core->config('website_url'));
	}

	else if (isset($_POST['yes']))
	{
		$check = $dbl->run("SELECT `user_id` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
		if ($check)
		{
				$dbl->run("DELETE FROM `likes` WHERE `user_id` = ?", array($_SESSION['user_id']));
				$dbl->run("DELETE FROM `article_likes` WHERE `user_id` = ?", array($_SESSION['user_id']));

				$_SESSION['message'] = 'unliked';
				header("Location: " . $core->config('website_url') . 'home/');
		}

		else
		{
			$_SESSION['message'] = 'cannotunlike';
			header("Location: " . $core->config('website_url') . 'home/');
		}
	}
}
else
{
	$_SESSION['message'] = 'cannotunlike';
	header("Location: " . $core->config('website_url') . 'home/');
}
?>

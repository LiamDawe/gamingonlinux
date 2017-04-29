<?php
$templating->set_previous('title', 'Unlike everything', 1);

if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
	if (!isset($_POST['yes']) && !isset($_POST['no']))
	{
		$core->yes_no('Are you sure you want to unlike all comments and articles? CANNOT BE UNDONE', 'index.php?module=unlike_all');
	}

	else if (isset($_POST['no']))
	{
		header("Location: " . core::config('website_url'));
	}

	else if (isset($_POST['yes']))
	{
		$db->sqlquery("SELECT `user_id` FROM `".$dbl->table_prefix."users` WHERE `user_id` = ?", array($_SESSION['user_id']));
		if ($db->num_rows() == 1)
		{
				$db->sqlquery("DELETE FROM `likes` WHERE `user_id` = ?", array($_SESSION['user_id']));
				$db->sqlquery("DELETE FROM `article_likes` WHERE `user_id` = ?", array($_SESSION['user_id']));

				$_SESSION['message'] = 'unliked';
				if (core::config('pretty_urls') == 1)
				{
					header("Location: " . core::config('website_url') . 'home/');
				}
				else
				{
					header("Location: " . core::config('website_url') . 'index.php?module=home');
				}
		}

		else
		{
			$_SESSION['message'] = 'cannotunlike';
			if (core::config('pretty_urls') == 1)
			{
				header("Location: " . core::config('website_url') . 'home/');
			}
			else
			{
				header("Location: " . core::config('website_url') . 'index.php?module=home');
			}
		}
	}
}
else
{
	$_SESSION['message'] = 'cannotunlike';
	if (core::config('pretty_urls') == 1)
	{
		header("Location: " . core::config('website_url') . 'home/');
	}
	else
	{
		header("Location: /index.php?module=home");
	}
}
?>

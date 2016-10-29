<?php
if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
	if (!isset($_POST['yes']) && !isset($_POST['no']))
	{
		$core->yes_no('Are you sure you want to unlike all comments and articles?', 'index.php?module=unlike_all');
	}

	else if (isset($_POST['no']))
	{
		header("Location: https://www.gamingonlinux.com");
	}

	else if (isset($_POST['yes']))
	{
		$db->sqlquery("SELECT `user_id` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
		if ($db->num_rows() == 1)
		{
				$db->sqlquery("DELETE FROM `likes` WHERE `user_id` = ?", array($_SESSION['user_id']));
				$db->sqlquery("DELETE FROM `article_likes` WHERE `user_id` = ?", array($_SESSION['user_id']));

				if (core::config('pretty_urls') == 1)
				{
					header("Location: home/message=unliked");
				}
				else
				{
					header("Location: /index.php?module=home&message=unliked");
				}
		}

		else
		{
			if (core::config('pretty_urls') == 1)
			{
				header("Location: home/message=cannotunlike");
			}
			else
			{
				header("Location: /index.php?module=home&message=cannotunlike");
			}
		}
	}
}
else
{
	if (core::config('pretty_urls') == 1)
	{
		header("Location: home/message=cannotunlike");
	}
	else
	{
		header("Location: /index.php?module=home&message=cannotunlike");
	}
}
?>

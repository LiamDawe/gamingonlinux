<?php
$templating->set_previous('title', 'Remove User Account' . $templating->get('title', 1)  , 1);
$templating->load('usercp_modules/removeacc');

if (!isset($_POST['act']))
{
	$templating->block('main', 'usercp_modules/removeacc');
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'remove_account')
	{
		if ($_SESSION['user_id'] == 1)
		{
			$_SESSION['message'] = 'cannot_remove_admin';
			header("Location: /usercp.php?module=removeacc");
			die();
		}
		if (empty($_POST['password']))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'current password';
			header("Location: /usercp.php?module=removeacc");
			die();
		}

		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->confirmation(array("title" => "Remove Account", "text" => "Are you sure you wish to delete your account? This CANNOT be undone!", "action_url" => "usercp.php?module=removeacc", "act" => "remove_account", "act_2_name" => "password", "act_2_value" => $_POST['password']));
		}

		else if (isset($_POST['no']))
		{
			header("Location: /usercp.php");
		}

		else
		{
			// find current password
			$grab_current_password = $dbl->run("SELECT `username`, `password`, `email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
			
			// check the original matches
			if (!password_verify($_POST['password'], $grab_current_password['password']))
			{
				$_SESSION['message'] = 'password_match';
				header("Location: /usercp.php?module=removeacc");
				die();
			}

			$remove_comments = 0;
			if (isset($_POST['remove_comments']))
			{
				$remove_comments = 1;
			}

			$remove_forum_posts = 0;
			if (isset($_POST['remove_forum']))
			{
				$remove_forum_posts = 1;
			}
			
			$user->delete_user($_SESSION['user_id'], array('remove_comments' => $remove_comments, 'remove_forum_posts' => $remove_forum_posts));
			$user->logout(0,0); // not banned, don't redirect

			$_SESSION['message'] = 'deleted';
			$_SESSION['message_extra'] = 'user account';
			header("Location: " . $core->config('website_url'));
			die();
		}
	}
}
?>

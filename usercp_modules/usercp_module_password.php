<?php
$templating->set_previous('title', 'Change Password' . $templating->get('title', 1)  , 1);

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'nocurrent')
	{
		$core->message('You need to set your current password!', NULL, 1);
	}
	if ($_GET['message'] == 'nomatchoriginal')
	{
		$core->message('The original passwords did not match, please try again!', NULL, 1);
	}
	if ($_GET['message'] == 'newcheck')
	{
		$core->message('If you want to update your password you need to fill all the fields in!', NULL, 1);
	}
	if ($_GET['message'] == 'nochecknew')
	{
		$core->message('The new password didn\'t match the checker, try again!', NULL, 1);
	}
	if ($_GET['message'] == 'done')
	{
		$core->message("Your password has been updated!");
	}
}
$templating->merge('usercp_modules/usercp_module_password');
$templating->block('main');

// find current password
$db->sqlquery("SELECT `password` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
$grab_current_password = $db->fetch();

// steam or twitter login
$current_password = '';
if (!empty($grab_current_password['password']))
{
	$current_password = 'Current Password: <input type="password" name="current_password" /><br />';
}
$templating->set('current_password', $current_password);

if (isset($_POST['Update']))
{
	// find current password
	$db->sqlquery("SELECT `password`, `steam_id`, `oauth_uid` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$grab_current_password = $db->fetch();

	if (!empty($grab_current_password['password']))
	{
		$current_password_test = password_verify($_POST['current_password'], $grab_current_password['password']);

		if (empty($_POST['current_password']))
		{
			header("Location: /usercp.php?module=password&message=nocurrent");
		}
		// check the originals match
		else if ($grab_current_password['password'] != $current_password_test)
		{
			header("Location: /usercp.php?module=password&message=nomatchoriginal");
		}
	}

	// if they have no password, they simply must have a steamid or a twitter oauth id
	if (empty($grab_current_password['password']))
	{
		if (empty($grab_current_password['steam_id']) && empty($grab_current_password['oauth_uid']))
		{
			$user->logout();
			die();
		}
	}

	$new_password_safe = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

	if (empty($_POST['new_password']) || empty($_POST['new_password_check']))
	{
		header("Location: /usercp.php?module=password&message=newcheck");
		die();
	}

	// check the new ones match
	if ($_POST['new_password'] != $_POST['new_password_check'])
	{
		header("Location: /usercp.php?module=password&message=nochecknew");
		die();
	}

	$db->sqlquery("UPDATE `users` SET `password` = ? WHERE `user_id` = ?", array($new_password_safe, $_SESSION['user_id']));
	header("Location: /usercp.php?module=password&message=done");
}
?>

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
	if (empty($_POST['current_password']))
	{
		header("Location: /usercp.php?module=password&message=nocurrent");
		die();
	}

	// find current password
	$db->sqlquery("SELECT `username`, `password`, `steam_id`, `oauth_uid`, `email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
	$grab_current_password = $db->fetch();

	// if they have no password, they simply must have a steamid or a twitter oauth id
	if (empty($grab_current_password['password']))
	{
		if (empty($grab_current_password['steam_id']) && empty($grab_current_password['oauth_uid']))
		{
			$user->logout();
			die();
		}
	}

	if (!empty($grab_current_password['password']))
	{
		// check the original matches
		if (!password_verify($_POST['current_password'], $grab_current_password['password']))
		{
			header("Location: /usercp.php?module=password&message=nomatchoriginal");
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

	// send an email to their old address to let them know
	$subject = "Password changed on GamingOnLinux.com";

	// message
	$html_message = "<p>Hello <strong>{$grab_current_password['username']}</strong>,</p>
	<p>Someone, hopefully you, has changed your password on <a href=\"".core::config('website_url')."\">gamingonlinux.com</a>. If this was you, please ignore this email as it's just a security measure.</p>
	<hr>
		<p>If you haven&#39;t registered at <a href=\"" . core::config('website_url') . "\" target=\"_blank\">" . core::config('website_url') . "</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> !</p>
		<p>Please don&#39;t reply to this automated message. We do not read any mails recieved on this email address.</p>
		<p>-----------------------------------------------------------------------------------------------------------</p>
	</div>";

	$plain_message = PHP_EOL."Hello {$grab_current_password['username']}! Someone, hopefully you, has changed your password on ".core::config('website_url').". If this was you, please ignore this email as it's just a security measure.";

	// Mail it
	if (core::config('send_emails') == 1)
	{
		$mail = new mail($grab_current_password['email'], $subject, $html_message, $plain_message);
		$mail->send();
	}

	$db->sqlquery("UPDATE `users` SET `password` = ? WHERE `user_id` = ?", array($new_password_safe, $_SESSION['user_id']));
	header("Location: /usercp.php?module=password&message=done");
}
?>

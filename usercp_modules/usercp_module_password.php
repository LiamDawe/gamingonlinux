<?php
$templating->set_previous('title', 'Change Password' . $templating->get('title', 1)  , 1);

// find current password
$db->sqlquery("SELECT `username`, `password`, `email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
$grab_current_password = $db->fetch();

$templating->load('usercp_modules/usercp_module_password');

if (empty($grab_current_password['password']))
{
	$templating->block('no_password');
}
else
{
	$templating->block('main');
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'request')
	{
		if (!empty($grab_current_password['password']))
		{
			$_SESSION['message'] = 'existing_password';
			header("Location: /usercp.php?module=password");
			die();
		}
		
		$new_password = core::random_id(15);
		$safe_password = password_hash($new_password, PASSWORD_BCRYPT);
		
		$db->sqlquery("UPDATE `users` SET `password` = ? WHERE `user_id` = ?", array($safe_password, $_SESSION['user_id']));
		
		// send an email to their old address to let them know
		$subject = "Password requested on " . $core->config('site_title');

		// message
		$html_message = "<p>Hello <strong>{$grab_current_password['username']}</strong>,</p>
		<p>Someone, hopefully you, has requested a password for your account on <a href=\"".$core->config('website_url')."\">".$core->config('site_title')."</a>. If this <strong>wasn't you</strong>, then your account has somehow been compromised.</p>
		<p>Your new password is: ".$new_password.", please keep a note of it!</p>";

		$plain_message = PHP_EOL."Hello {$grab_current_password['username']}! Someone, hopefully you, has requested a password for your account on ".$core->config('website_url').". If this wasn't you, then your account has somehow been compromised. Your new password is: ".$new_password . ", please keep a note of it!";

		// Mail it
		if ($core->config('send_emails') == 1)
		{
			$mail = new mailer($core);
			$mail->sendMail($grab_current_password['email'], $subject, $html_message, $plain_message);
		}
		
		$_SESSION['message'] = 'password-sent';
		header("Location: /usercp.php?module=password");
	}
	
	if ($_POST['act'] == 'Update')
	{
		if (empty($_POST['current_password']))
		{
			$_SESSION['message'] = 'nocurrent';
			header("Location: /usercp.php?module=password");
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
				$_SESSION['message'] = 'nomatchoriginal';
				header("Location: /usercp.php?module=password");
				die();
			}
		}

		$new_password_safe = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

		if (empty($_POST['new_password']) || empty($_POST['new_password_check']))
		{
			$_SESSION['message'] = 'newcheck';
			header("Location: /usercp.php?module=password");
			die();
		}

		// check the new ones match
		if ($_POST['new_password'] != $_POST['new_password_check'])
		{
			$_SESSION['message'] = 'nochecknew';
			header("Location: /usercp.php?module=password");
			die();
		}

		// send an email to their old address to let them know
		$subject = "Password changed on " . $core->config('site_title');

		// message
		$html_message = "<p>Hello <strong>{$grab_current_password['username']}</strong>,</p>
		<p>Someone, hopefully you, has changed your password on <a href=\"".$core->config('website_url')."\">".$core->config('site_title')."</a>. If this was you, please ignore this email as it's just a security measure.</p>";

		$plain_message = PHP_EOL."Hello {$grab_current_password['username']}! Someone, hopefully you, has changed your password on ".$core->config('website_url').". If this was you, please ignore this email as it's just a security measure.";

		// Mail it
		if ($core->config('send_emails') == 1)
		{
			$mail = new mailer($core);
			$mail->sendMail($grab_current_password['email'], $subject, $html_message, $plain_message);
		}

		$db->sqlquery("UPDATE `users` SET `password` = ? WHERE `user_id` = ?", array($new_password_safe, $_SESSION['user_id']));
		
		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'password';
		header("Location: /usercp.php?module=password");
	}
}
?>

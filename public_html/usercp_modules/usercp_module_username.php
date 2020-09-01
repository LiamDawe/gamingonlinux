<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Change Username' . $templating->get('title', 1)  , 1);

$templating->load('usercp_modules/usercp_module_username');
$templating->block('main');
$templating->set('current_username', $_SESSION['username']);

if (isset($_POST['Update']))
{
	if (empty($_POST['new_username']))
	{
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = 'username';
		header("Location: /usercp.php?module=username");
		die();
	}

	if (empty($_POST['password']))
	{
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = 'password';
		header("Location: /usercp.php?module=username");
		die();
    }
    
    if (strlen($_POST['new_username']) < 4)
    {
        $_SESSION['message'] = 'username-short';
		header("Location: /usercp.php?module=username");
		die();
    }

	// find current password
	$grab_current_password = $dbl->run("SELECT `password`, `steam_id`, `oauth_uid` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

	if (!empty($grab_current_password['password']))
	{
		// check the originals match
		if (!password_verify($_POST['password'], $grab_current_password['password']))
		{
			$_SESSION['message'] = 'password-match';
			header("Location: /usercp.php?module=username");
			die();
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

	$new_username = trim($_POST['new_username']);

	// check to see if this username exists
	$check_name = $dbl->run("SELECT `username` FROM `users` WHERE `username` = ?", array($new_username))->fetch();
	if ($check_name)
	{
		$_SESSION['message'] = 'username_used';
		header("Location: /usercp.php?module=username");
		die();
	}

	// disallow certain username characters
	$aValid = array('-', '_');

	if(!ctype_alnum(str_replace($aValid, '', $new_username)))
	{
		$_SESSION['message'] = 'username_characters';
		header("Location: /usercp.php?module=username");
		die();
	}

	$dbl->run("UPDATE `users` SET `username` = ? WHERE `user_id` = ?", array($new_username, $_SESSION['user_id']));

	// send an email to their old address to let them know
	$subject = "Username changed on GamingOnLinux";

	$html_message = "<p>Hello,</p>
	<p>Someone, hopefully you, has changed your username on <a href=\"".$core->config('website_url')."\">GamingOnLinux</a> to: <strong>{$new_username}</strong>. If this was you, please ignore this email as it's just a security measure.</p>";

	$plain_message = PHP_EOL."Hello! Someone, hopefully you, has changed your username on ".$core->config('website_url')." to: {$new_username}. If this was you, please ignore this email as it's just a security measure.";

	$grab_email = $dbl->run("SELECT `email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetchOne();

	// Mail it
	if ($core->config('send_emails') == 1)
	{
		$mail = new mailer($core);
		$mail->sendMail($grab_email, $subject, $html_message, $plain_message);
	}

	$_SESSION['message'] = 'saved';
	$_SESSION['message_extra'] = 'username';
	$_SESSION['username'] = $new_username;

	// redirect and tell them it's done
	header("Location: /usercp.php?module=username");
}
?>

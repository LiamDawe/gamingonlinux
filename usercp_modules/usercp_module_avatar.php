<?php
if (isset($_GET['error']))
{
	$core->message($_SESSION['message'], NULL, 1);
}
if (isset($_GET['message']))
{
	if ($_GET['message'] == 'deleted')
	{
		$core->message("Your avatar has been deleted and reset to the default!");
	}
	if ($_GET['message'] == 'uploaded')
	{
		$core->message("Your avatar has been uploaded!");
	}
	if ($_GET['message'] == 'gravatar')
	{
		$core->message("Your avatar has changed to use a gravatar!");
	}
	if ($_GET['message'] == 'urldone')
	{
		$core->message("Your avatar has changed to use a the url you provided!");
	}
}
$templating->merge('usercp_modules/usercp_module_avatar');
$templating->block('main');
	
// get current avatar
$db->sqlquery("SELECT `avatar`, `avatar_uploaded`,`avatar_gravatar`, `gravatar_email` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
$user_avatar = $db->fetch();

// sort out the avatar
// either no avatar (gets no avatar from gravatars redirect) or gravatar set
if ($user_avatar['avatar_gravatar'] == 1)
{
	$avatar = "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $user_avatar['gravatar_email'] ) ) ) . "?d={$config['website_url']}{$config['path']}/uploads/avatars/no_avatar.png";
}
		
// either uploaded or linked an avatar
else  if (!empty($user_avatar['avatar']) && $user_avatar['avatar_gravatar'] == 0)
{
	$avatar = $user_avatar['avatar'];
	if ($user_avatar['avatar_uploaded'] == 1)
	{
		$avatar = "/uploads/avatars/{$user_avatar['avatar']}";
	}
}

// else no avatar, then as a fallback use gravatar if they have an email left-over
else if (empty($user_avatar['avatar']) && $user_avatar['avatar_gravatar'] == 0)
{
	$avatar = "/uploads/avatars/no_avatar.png";
}

$templating->set('current_avatar', $avatar);

$templating->set('width', $config['avatar_width']);
$templating->set('height', $config['avatar_height']);

$templating->set('gravatar_email', $user_avatar['gravatar_email']);

if (isset($_POST['action']))
{
	if ($_POST['action'] == 'url')
	{
		if (empty($_POST['avatar_url']))
		{
			$core->message('You didn\'t enter anything into the url box!', NULL, 1);
		}
		
		// check url is valid
		else if (getimagesize($_POST['avatar_url']) == false)
		{
			$core->message('Could not access the image!', NULL, 1);
		}
		
		else
		{
			// check dimensions
			list($width, $height, $type, $attr) = getimagesize($_POST['avatar_url']);

			if ($width > $config['avatar_width'] || $height > $config['avatar_height'])
			{
				$core->message('The dimensions are too big!');
			}	
			
			else
			{
				// remove any old avatar if one was uploaded
				$db->sqlquery("SELECT `avatar`, `avatar_uploaded` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
				$avatar = $db->fetch();
				
				if ($avatar['avatar_uploaded'] == 1)
				{
					unlink('uploads/avatars/' . $avatar['avatar']);
				}
				
				// change to new avatar
				$db->sqlquery("UPDATE `users` SET `avatar` = ?, `avatar_uploaded` = 0, `avatar_gravatar` = 0, `gravatar_email` = '' WHERE `user_id` = ?", array($_POST['avatar_url'], $_SESSION['user_id']));
				
				header("Location: /usercp.php?module=avatar&message=urldone");
			}
		}
	}
	
	else if ($_POST['action'] == 'Upload')
	{
		if ($user->avatar() == true)
		{
			header("Location: /usercp.php?module=avatar&message=uploaded");
		}
		
		else
		{
			$_SESSION['message'] = $user->message;
			header("Location: /usercp.php?module=avatar&error");
		}
	}

	else if ($_POST['action'] == 'Gravatar')
	{
		if (empty($_POST['gravatar_email']))
		{
			$core->message('To use a Gravatar be sure to enter your email for it! Options: <a href="usercp.php?module=avatar">Return to Avatars</a> | <a href="index.php">Homepage</a>');
		}

		else
		{
			// remove any old avatar if one was uploaded
			$db->sqlquery("SELECT `avatar`, `avatar_uploaded` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
			$avatar = $db->fetch();
				
			if ($avatar['avatar_uploaded'] == 1)
			{
				unlink('uploads/avatars/' . $avatar['avatar']);
			}

			$db->sqlquery("UPDATE `users` SET `avatar` = '', `avatar_uploaded` = 0, `avatar_gravatar` = 1, `gravatar_email` = ? WHERE `user_id` = ?", array($_POST['gravatar_email'], $_SESSION['user_id']));
		
			header("Location: /usercp.php?module=avatar&message=gravatar");
		}
	}
	
	else if ($_POST['action'] == 'Delete')
	{
		// remove any old avatar if one was uploaded
		$db->sqlquery("SELECT `avatar`, `avatar_uploaded`, `avatar_gravatar` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
		$avatar = $db->fetch();
				
		if ($avatar['avatar_uploaded'] == 1)
		{
			unlink('uploads/avatars/' . $avatar['avatar']);
		}
		
		$db->sqlquery("UPDATE `users` SET `avatar` = '', `avatar_uploaded` = 0, `avatar_gravatar` = 0, `gravatar_email` = '' WHERE `user_id` = ?", array($_SESSION['user_id']));
		
		header("Location: /usercp.php?module=avatar&message=deleted");
	}
}
?>

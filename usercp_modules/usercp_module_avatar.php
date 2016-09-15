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
	if ($_GET['message'] == 'gallery')
	{
		$core->message("You are now using an avatar picked from the gallery!");
	}
}
$templating->merge('usercp_modules/usercp_module_avatar');
$templating->block('main');

// get current avatar
$db->sqlquery("SELECT `avatar`, `avatar_uploaded`,`avatar_gravatar`, `gravatar_email`, `avatar_gallery` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
$user_avatar = $db->fetch();

// sort out the avatar
$avatar = $user->sort_avatar($user_avatar);
$templating->set('current_avatar', $avatar);

$templating->set('width', core::config('avatar_width'));
$templating->set('height', core::config('avatar_height'));

// AVATAR GALLERY
$db->sqlquery("SELECT `id`, `filename` FROM `avatars_gallery` ORDER BY `id` ASC");
$avatar_gallery = '';
while ($gallery = $db->fetch())
{
	$avatar_gallery .= '<li style="display: inline; float: left; padding: 5px;"><label class="inline"><img src="/uploads/avatars/gallery/'.$gallery['filename'].'" alt="avatar" /><br /><input name="gallery" type="radio" value="'.$gallery['id'].'" /></label></li>';
}
$templating->set('avatar_gallery', $avatar_gallery);

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

			if ($width > core::config('avatar_width') || $height > core::config('avatar_height'))
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
				$db->sqlquery("UPDATE `users` SET `avatar` = ?, `avatar_uploaded` = 0, `avatar_gravatar` = 0, `gravatar_email` = '', `avatar_gallery` = NULL WHERE `user_id` = ?", array($_POST['avatar_url'], $_SESSION['user_id']));

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

			$db->sqlquery("UPDATE `users` SET `avatar` = '', `avatar_uploaded` = 0, `avatar_gravatar` = 1, `gravatar_email` = ?, `avatar_gallery` = NULL WHERE `user_id` = ?", array($_POST['gravatar_email'], $_SESSION['user_id']));

			header("Location: /usercp.php?module=avatar&message=gravatar");
		}
	}

	else if ($_POST['action'] == 'gallery')
	{
		// remove any old avatar if one was uploaded
		$db->sqlquery("SELECT `avatar`, `avatar_uploaded`, `avatar_gravatar` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
		$avatar = $db->fetch();

		if ($avatar['avatar_uploaded'] == 1)
		{
			unlink('uploads/avatars/' . $avatar['avatar']);
		}

		$db->sqlquery("UPDATE `users` SET `avatar` = '', `avatar_uploaded` = 0, `avatar_gravatar` = 0, `gravatar_email` = '', `avatar_gallery` = ? WHERE `user_id` = ?", array($_POST['gallery'], $_SESSION['user_id']));

		header("Location: /usercp.php?module=avatar&message=gallery");
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

		$db->sqlquery("UPDATE `users` SET `avatar` = '', `avatar_uploaded` = 0, `avatar_gravatar` = 0, `gravatar_email` = '', `avatar_gallery` = NULL WHERE `user_id` = ?", array($_SESSION['user_id']));

		header("Location: /usercp.php?module=avatar&message=deleted");
	}
}
?>

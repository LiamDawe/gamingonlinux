<?php
$image_upload = new image_upload($dbl, $core);

$templating->set_previous('title', 'Change your avatar', 1);
$templating->set_previous('meta_description', 'Here you can change your avatar!', 1);

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
	if ($_GET['message'] == 'gallery')
	{
		$core->message("You are now using an avatar picked from the gallery!");
	}
}
$templating->load('usercp_modules/usercp_module_avatar');
$templating->block('main');

// sort out the avatar
$avatar = $user->sort_avatar($user->user_details);
$templating->set('current_avatar', $avatar);

$templating->set('width', $core->config('avatar_width'));
$templating->set('height', $core->config('avatar_height'));

// AVATAR GALLERY
$res_gal = $dbl->run("SELECT `id`, `filename` FROM `avatars_gallery` ORDER BY `id` ASC")->fetch_all();
$avatar_gallery = '';
foreach ($res_gal as $gallery)
{
	$avatar_gallery .= '<li style="display: inline; float: left; padding: 5px;"><label class="inline"><img src="/uploads/avatars/gallery/'.$gallery['filename'].'" alt="avatar" /><br /><input name="gallery" type="radio" value="'.$gallery['id'].'" /></label></li>';
}
$templating->set('avatar_gallery', $avatar_gallery);

$templating->set('gravatar_email', $user->user_details['gravatar_email']);

if (isset($_POST['action']))
{
	if ($_POST['action'] == 'Upload')
	{
		if ($image_upload->avatar() == true)
		{
			header("Location: /usercp.php?module=avatar&message=uploaded");
		}

		else
		{
			$_SESSION['message'] = image_upload::$return_message;
			header("Location: /usercp.php?module=avatar");
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
			$avatar = $dbl->run("SELECT `avatar`, `avatar_uploaded` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

			if ($avatar['avatar_uploaded'] == 1)
			{
				unlink('uploads/avatars/' . $avatar['avatar']);
			}

			$dbl->run("UPDATE `users` SET `avatar` = '', `avatar_uploaded` = 0, `avatar_gravatar` = 1, `gravatar_email` = ?, `avatar_gallery` = NULL WHERE `user_id` = ?", array($_POST['gravatar_email'], $_SESSION['user_id']));

			header("Location: /usercp.php?module=avatar&message=gravatar");
		}
	}

	else if ($_POST['action'] == 'gallery')
	{
		if (isset($_POST['gallery']))
		{
			// remove any old avatar if one was uploaded
			$avatar = $dbl->run("SELECT `avatar`, `avatar_uploaded`, `avatar_gravatar` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

			if ($avatar['avatar_uploaded'] == 1)
			{
				unlink('uploads/avatars/' . $avatar['avatar']);
			}

			$dbl->run("UPDATE `users` SET `avatar` = '', `avatar_uploaded` = 0, `avatar_gravatar` = 0, `gravatar_email` = '', `avatar_gallery` = ? WHERE `user_id` = ?", array($_POST['gallery'], $_SESSION['user_id']));
			
			header("Location: /usercp.php?module=avatar&message=gallery");
		}
		else
		{
			header("Location: /usercp.php?module=avatar");
		}
	}

	else if ($_POST['action'] == 'Delete')
	{
		// remove any old avatar if one was uploaded
		$avatar = $dbl->run("SELECT `avatar`, `avatar_uploaded`, `avatar_gravatar` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

		if ($avatar['avatar_uploaded'] == 1)
		{
			unlink('uploads/avatars/' . $avatar['avatar']);
		}

		$dbl->run("UPDATE `users` SET `avatar` = '', `avatar_uploaded` = 0, `avatar_gravatar` = 0, `gravatar_email` = '', `avatar_gallery` = NULL WHERE `user_id` = ?", array($_SESSION['user_id']));

		header("Location: /usercp.php?module=avatar&message=deleted");
	}
}
?>

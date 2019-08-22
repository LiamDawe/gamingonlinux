<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$image_upload = new image_upload($dbl, $core);

$templating->set_previous('title', 'Change your avatar', 1);
$templating->set_previous('meta_description', 'Here you can change your avatar!', 1);

$templating->load('usercp_modules/usercp_module_avatar');
$templating->block('main');

// sort out the avatar
$avatar = $user->sort_avatar($user->user_details);
$templating->set('current_avatar', $avatar);
$current_author_pic = '/uploads/avatars/no_avatar.png';
if (isset($user->user_details['author_picture']) && !empty($user->user_details['author_picture']))
{
	$current_author_pic = '/uploads/avatars/author_pictures/'.$user->user_details['author_picture'];
}
$templating->set('current_author_pic', $current_author_pic);

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

if (isset($_POST['action']))
{
	if ($_POST['action'] == 'Upload')
	{
		if ($image_upload->avatar() == true)
		{
			$_SESSION['message'] = 'uploaded';
			header("Location: /usercp.php?module=avatar");
			die();
		}

		else
		{
			$_SESSION['message'] = image_upload::$return_message;
			header("Location: /usercp.php?module=avatar");
			die();
		}
	}

	else if ($_POST['action'] == 'gallery')
	{
		if (isset($_POST['gallery']))
		{
			// first, check that gallery avatar actually exists
			$check = $dbl->run("SELECT `id` FROM `avatars_gallery` WHERE `id` = ?", array($_POST['gallery']))->fetch();
			if ($check)
			{
				// remove any old avatar if one was uploaded
				$avatar = $dbl->run("SELECT `avatar`, `avatar_uploaded` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

				if ($avatar['avatar_uploaded'] == 1)
				{
					unlink('uploads/avatars/' . $avatar['avatar']);
				}

				$dbl->run("UPDATE `users` SET `avatar` = '', `avatar_uploaded` = 0, `avatar_gallery` = ? WHERE `user_id` = ?", array($_POST['gallery'], $_SESSION['user_id']));
				
				$_SESSION['message'] = 'gallery_picked';
				header("Location: /usercp.php?module=avatar");
				die();
			}
			else
			{
				$_SESSION['message'] = 'no_gallery_picked';
				header("Location: /usercp.php?module=avatar");
				die();				
			}
		}
		else
		{
			$_SESSION['message'] = 'no_gallery_picked';
			header("Location: /usercp.php?module=avatar");
			die();
		}
	}

	else if ($_POST['action'] == 'Delete')
	{
		// remove any old avatar if one was uploaded
		$avatar = $dbl->run("SELECT `avatar`, `avatar_uploaded` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

		if ($avatar['avatar_uploaded'] == 1)
		{
			unlink('uploads/avatars/' . $avatar['avatar']);
		}

		$dbl->run("UPDATE `users` SET `avatar` = '', `avatar_uploaded` = 0, `avatar_gallery` = NULL WHERE `user_id` = ?", array($_SESSION['user_id']));

		$_SESSION['message'] = 'deleted';
		$_SESSION['message_extra'] = 'avatar';
		header("Location: /usercp.php?module=avatar");
		die();
	}

	else if ($_POST['action'] == 'upload_author_pic')
	{
		if ($image_upload->avatar(1) == true)
		{
			$_SESSION['message'] = 'uploaded';
			header("Location: /usercp.php?module=avatar");
			die();
		}

		else
		{
			$_SESSION['message'] = image_upload::$return_message;
			header("Location: /usercp.php?module=avatar");
			die();
		}
	}

	else if ($_POST['action'] == 'delete_author_pic')
	{
		// remove any old avatar if one was uploaded
		$avatar = $dbl->run("SELECT `author_picture` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();

		if (isset($avatar['author_picture']) && !empty($avatar['author_picture']))
		{
			unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/author_pictures/' . $avatar['author_picture']);
		}

		$dbl->run("UPDATE `users` SET `author_picture` = NULL WHERE `user_id` = ?", array($_SESSION['user_id']));

		$_SESSION['message'] = 'deleted';
		$_SESSION['message_extra'] = 'avatar';
		header("Location: /usercp.php?module=avatar");
		die();
	}
}
?>

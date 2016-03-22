<?php
class user
{
	public $message;

	function salt($max = 15)
	{
		$characterList = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*?";
		$i = 0;
		$salt = "";
		while ($i < $max)
		{
			$salt .= $characterList{mt_rand(0, (strlen($characterList) - 1))};
			$i++;
		}
		return $salt;
	}

	// normal login form
	function login($username, $password, $remember_username, $stay)
	{
		global $db;

		$db->sqlquery("SELECT `password_salt` FROM `users` WHERE (`username` = ? OR `email` = ?)", array($username, $username));
		if ($db->num_rows() > 0)
		{
			$info = $db->fetch();
			$safe_password = hash('sha256', $info['password_salt'] . $password);

			$db->sqlquery("SELECT `user_id`, `username`, `user_group`, `secondary_user_group`, `banned`, `theme`, `activated`, `in_mod_queue`, `email`, `login_emails` FROM `users` WHERE (`username` = ? OR `email` = ?) AND `password` = ?", array($username, $username, $safe_password));
			if ($db->num_rows() == 1)
			{
				$user = $db->fetch();

				$this->check_banned($user);

				$generated_session = md5(mt_rand() . $user['user_id'] . $_SERVER['HTTP_USER_AGENT']);

				// update IP address and last login
				$db->sqlquery("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $user['user_id']));

				$this->register_session($user, $generated_session);

				if ($remember_username == 1)
				{
					setcookie('remember_username', $username,  time()+60*60*24*30, '/', 'gamingonlinux.com');
				}

				if ($stay == 1)
				{
					setcookie('gol_stay', $user['user_id'], time()+31556926, '/', 'gamingonlinux.com');
					setcookie('gol_session', $generated_session, time()+31556926, '/', 'gamingonlinux.com');
				}

				return true;
			}

			else
			{
				$this->message = "Password probably didn't match!";
				return false;
			}
		}
		else
		{
			$this->message = "Couldn't find username!";
			return false;
		}
	}

	public static function check_banned($user_data)
	{
		global $db;

		$banned = 0;

		// now check IP ban
		$db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip));
		if ($db->num_rows() == 1)
		{
			$banned = 1;
		}

		if ($user_data['banned'] == 1)
		{
			$banned = 1;
		}

		if ($banned == 1)
		{
			setcookie('gol_stay', "",  time()-60, '/');
			setcookie('gol_session', "",  time()-60, '/');
			setcookie('gol-device', "",  time()-60, '/');

			// update their ip in the user table
			$db->sqlquery("UPDATE `users` SET `ip` = ? WHERE `user_id` = ?", array(core::$ip, $user_data['user_id']));

			// search the ip list, if it's not on it then add it in
			$db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip));
			if ($db->num_rows() == 0)
			{
				$db->sqlquery("INSERT INTO `ipbans` SET `ip` = ?", array(core::$ip));
			}

			if (core::config('pretty_urls') == 1)
			{
				header("Location: /home/banned");
			}
			else
			{
				header("Location: index.php?module=home&message=banned");
			}
			exit;
		}
	}

	// check if it's a new device, then set the session up
	public static function register_session($user_data, $generated_session)
	{
		global $db;

		// check if it's a new device straight away
		$new_device = 0;
		if (!isset($_COOKIE['gol-device']))
		{
			$new_device = 1;
		}

		// they have a device cookie, let's check it bitches
		if (isset($_COOKIE['gol-device']))
		{
			$db->sqlquery("SELECT `device-id` FROM `saved_sessions` WHERE `user_id` = ?", array($user_data['user_id']));
			$get_device = $db->fetch();

			// cookie didn't match, don't let them in, hacking attempt probable
			if ($get_device['device-id'] != $_COOKIE['gol-device'])
			{
				$new_device = 1;
				setcookie('gol-device', "",  time()-60, '/');
			}
		}

		$device_id = '';
		// register the new device to their account, could probably add a small hook here to allow people to turn this email off at their own peril
		if ($new_device == 1)
		{
			$device_id = md5(mt_rand() . $user_data['user_id'] . $_SERVER['HTTP_USER_AGENT']);

			setcookie('gol-device', $device_id, time()+31556926, '/', 'gamingonlinux.com');

			if ($user_data['login_emails'] == 1)
			{
				// send email about new device
				$message = "<p>Hello <strong>{$user_data['username']}</strong>,</p>
				<p>We have detected a login from a new device, if you have just logged in yourself don't be alarmed (your cookies may have just been wiped at somepoint)! However, if you haven't just logged into the GamingOnLinux website you may want to let <a href=\"https://www.gamingonlinux.com/profiles/1\">TheBoss</a> and <a href=\"https://www.gamingonlinux.com/profiles/432\">Levi</a> know and change your password immediately.</p>
				<div>
				<hr>
				Login detected from: {$_SERVER['HTTP_USER_AGENT']} on " . date("Y-m-d H:i:s") . "
				<hr>
				<p>If you haven&#39;t registered at <a href=\"" . core::config('website_url') . "\" target=\"_blank\">" . core::config('website_url') . "</a>, Forward this mail to <a href=\"mailto:contact@gamingonlinux.com\" target=\"_blank\">contact@gamingonlinux.com</a> with some info about what you want us to do about it.</p>
				<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
				<p>-----------------------------------------------------------------------------------------------------------</p>
				</div>";

				$plain_message = "Hello {$user_data['username']},\r\nWe have detected a login from a new device, if you have just logged in yourself don't be alarmed! However, if you haven't just logged into the GamingOnLinux (https://www.gamingonlinux.com) website you may want to let TheBoss and Levi know and change your password immediately.\r\n\r\nLogin detected from: {$_SERVER['HTTP_USER_AGENT']} on " . date("Y-m-d H:i:s");

				$mail = new mail($user_data['email'], "GamingOnLinux: New Login Notification", $message, $plain_message);
				$mail->send();
			}
		}
		else
		{
			$device_id = $_COOKIE['gol-device'];
		}

		// keeping a log of logins, to review at anytime
		// TODO: need to implement user reviewing login history, would need to add login time for that, but easy as fook
		$db->sqlquery("INSERT INTO `saved_sessions` SET `user_id` = ?, `session_id` = ?, `browser_agent` = ?, `device-id` = ?, `date` = ?", array($user_data['user_id'], $generated_session, $_SERVER['HTTP_USER_AGENT'], $device_id, date("Y-m-d")));

		$_SESSION['user_id'] = $user_data['user_id'];
		$_SESSION['username'] = $user_data['username'];
		$_SESSION['user_group'] = $user_data['user_group'];
		$_SESSION['secondary_user_group'] = $user_data['secondary_user_group'];
		$_SESSION['theme'] = $user_data['theme'];
		$_SESSION['new_login'] = 1;
		$_SESSION['activated'] = $user_data['activated'];
		$_SESSION['in_mod_queue'] = $user_data['in_mod_queue'];
		$_SESSION['logged_in'] = 1;
	}

	// if they have a stay logged in cookie log them in!
	function stay_logged_in()
	{
		global $db, $core;

		$db->sqlquery("SELECT `session_id` FROM `saved_sessions` WHERE `user_id` = ? AND `session_id` = ? AND `device-id` = ?", array($_COOKIE['gol_stay'], $_COOKIE['gol_session'], $_COOKIE['gol-device']));
		$session = $db->fetch();

		if ($db->num_rows() == 1)
		{
			// login then
			$db->sqlquery("SELECT `user_id`, `username`, `user_group`, `secondary_user_group`, `banned`, `theme`, `activated`, `in_mod_queue`, `email` FROM `users` WHERE `user_id` = ?", array($_COOKIE['gol_stay']));
			$user = $db->fetch();

			if ($user['banned'] == 0)
			{
				// now check IP ban
				$db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip));
				if ($db->num_rows() == 1)
				{
					setcookie('gol_stay', "",  time()-60, '/');
					$this->message = "You are banned!";
					return false;
				}

				else
				{
					// update IP address and last login
					$db->sqlquery("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $user['user_id']));

					$_SESSION['user_id'] = $user['user_id'];
					$_SESSION['username'] = $user['username'];
					$_SESSION['user_group'] = $user['user_group'];
					$_SESSION['secondary_user_group'] = $user['secondary_user_group'];
					$_SESSION['theme'] = $user['theme'];
					$_SESSION['new_login'] = 1;
					$_SESSION['activated'] = $user['activated'];
					$_SESSION['in_mod_queue'] = $user['in_mod_queue'];
					$_SESSION['logged_in'] = 1;

					return true;
				}
			}

			else
			{
				setcookie('gol_stay', "",  time()-60, '/');

				// update their ip in the user table
				$db->sqlquery("UPDATE `users` SET `ip` = ? WHERE `user_id` = ?", array(core::$ip, $user['user_id']));

				// search the ip list, if it's not on it then add it in
				$db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip));
				if ($db->num_rows() == 0)
				{
					$db->sqlquery("INSERT INTO `ipbans` SET `ip` = ?", array(core::$ip));
				}
				$this->message = "You are banned!";
				return false;
			}
		}

		else
		{
			return false;
		}
	}

	function logout()
	{
		global $db;

		if (isset($_COOKIE['gol-device']))
		{
			$db->sqlquery("DELETE FROM `saved_sessions` WHERE `user_id` = ? AND `device-id` = ?", array($_SESSION['user_id'], $_COOKIE['gol-device']));
		}

		unset($_SESSION['user_id']);
		unset($_SESSION['username']);
		unset($_SESSION['user_group']);
		unset($_SESSION['secondary_user_group']);
		unset($_SESSION['theme']);
		unset($_SESSION['uploads']);
		unset($_SESSION['new_login']);
		unset($_SESSION['activated']);
		unset($_SESSION['in_mod_queue']);
		unset($_SESSION['logged_in']);
		setcookie('gol_stay', "",  time()-60, '/');
		setcookie('gol_session', "",  time()-60, '/');
		setcookie('gol-device', "",  time()-60, '/');
		setcookie('steamID', '', -1, '/');
		header("Location: index.php");
	}

	function avatar()
	{
		global $db;

		if (is_uploaded_file($_FILES['new_image']['tmp_name']))
		{
			// check the dimensions
			list($width, $height, $type, $attr) = getimagesize($_FILES['new_image']['tmp_name']);

			// check if its too big
			if ($_FILES['new_image']['size'] > 42000)
			{
				$this->message = 'File size too big!';
				return false;
			}

			if ($width > core::config('avatar_width') || $height > core::config('avatar_height'))
			{
				// include the image class to resize it as its too big
				include('includes/class_image.php');
				$image = new SimpleImage();
				$image->load($_FILES['new_image']['tmp_name']);
				$image->resize(core::config('avatar_width'),core::config('avatar_height'));
				$image->save($_FILES['new_image']['tmp_name']);

				clearstatcache();

				// just double check it's now the right size (just a failsafe)
				list($width, $height, $type, $attr) = getimagesize($_FILES['new_image']['tmp_name']);
				if ($width > core::config('avatar_width') || $height > core::config('avatar_height'))
				{
					$this->message = 'Too big!';
					return false;
				}
			}

			// this will make sure it is an image file, if it cant get an image size then its not an image
			if (!getimagesize($_FILES['new_image']['tmp_name']))
			{
				$this->message = 'Not an image!';
				return false;
			}

			// see if they currently have an avatar set
			$db->sqlquery("SELECT `avatar`, `avatar_uploaded` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
			$avatar = $db->fetch();

			$image_info = getimagesize($_FILES['new_image']['tmp_name']);
			$image_type = $image_info[2];
			$file_ext = '';
			if( $image_type == IMAGETYPE_JPEG )
			{
				$file_ext = 'jpg';
			}

			else if( $image_type == IMAGETYPE_GIF )
			{
				$file_ext = 'gif';
			}

			else if( $image_type == IMAGETYPE_PNG )
			{
				$file_ext = 'png';
			}

			$rand_name = rand(1,999);

			$imagename = $_SESSION['username'] . $rand_name . '_avatar.' . $file_ext;

			// the actual image
			$source = $_FILES['new_image']['tmp_name'];

			// where to upload to
			$target = $_SERVER['DOCUMENT_ROOT'] . "/uploads/avatars/" . $imagename;

			if (move_uploaded_file($source, $target))
			{
				// remove old avatar
				if ($avatar['avatar_uploaded'] == 1)
				{
					unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/' . $avatar['avatar']);
				}

				$db->sqlquery("UPDATE `users` SET `avatar` = ?, `avatar_uploaded` = 1, `avatar_gravatar` = 0, `gravatar_email` = '' WHERE `user_id` = ?", array($imagename, $_SESSION['user_id']));
				return true;
			}

			else
			{
				$this->message = 'Could not upload file!';
				return false;
			}
		}

		else
		{
			$this->message = 'No file selected to upload, dummy!';
			return false;
		}
	}

	// check a users group to perform a certain task, can check two groups
	// useful for seeing if they are an admin or editor to perform editing, deleting, publishing etc
	function check_group($group, $group2 = NULL)
	{
		if (isset($_SESSION['user_group']))
		{
			if ($_SESSION['user_group'] == $group || $_SESSION['secondary_user_group'] == $group)
			{
				return true;
			}

			else if ($group2 != NULL)
			{
				if ($_SESSION['user_group'] == $group2 || $_SESSION['secondary_user_group'] == $group2)
				{
					return true;
				}

				else
				{
					return false;
				}
			}

			else
			{
				return false;
			}
		}

		else
		{
			return false;
		}
	}
}

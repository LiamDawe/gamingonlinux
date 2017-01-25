<?php
class user
{
	public $message;

	public static $user_sql_fields = "`user_id`, `single_article_page`, `per-page`,
	`articles-per-page`, `username`, `user_group`, `secondary_user_group`,
	`banned`, `theme`, `activated`, `in_mod_queue`, `email`, `login_emails`,
	`forum_type`, `avatar_gravatar`, `gravatar_email`, `avatar_gallery`, `avatar`, `avatar_uploaded`,
	`display_comment_alerts`, `email_options`, `auto_subscribe`, `auto_subscribe_email`";

	// normal login form
	function login($username, $password, $remember_username, $stay)
	{
		global $db;

		$db->sqlquery("SELECT `password_salt`, `password` FROM `users` WHERE (`username` = ? OR `email` = ?)", array($username, $username));
		if ($db->num_rows() > 0)
		{
			$info = $db->fetch();

			if (password_verify($password, $info['password']))
			{
				$db->sqlquery("SELECT ".$this::$user_sql_fields." FROM `users` WHERE (`username` = ? OR `email` = ?)", array($username, $username));

				if ($db->num_rows() == 1)
				{
					$user_info = $db->fetch();

					// sort old passwords to new
					$old_password = hash('sha256', $info['password_salt'] . $password);
					if ($old_password[0] != '$')
					{
							$new_password_hash = password_hash($password, PASSWORD_BCRYPT);

							$db->sqlquery("UPDATE `users` SET `password` = ? WHERE `user_id` = ?", array($new_password_hash, $user_info['user_id']));
					}

					$this->check_banned($user_info);

					$generated_session = md5(mt_rand() . $user_info['user_id'] . $_SERVER['HTTP_USER_AGENT']);

					// update IP address and last login
					$db->sqlquery("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $user_info['user_id']));

					$this->register_session($user_info, $generated_session);

					if ($remember_username == 1)
					{
						setcookie('remember_username', $username,  time()+60*60*24*30, '/', core::config('cookie_domain'));
					}

					if ($stay == 1)
					{
						setcookie('gol_stay', $user_info['user_id'], time()+31556926, '/', core::config('cookie_domain'));
						setcookie('gol_session', $generated_session, time()+31556926, '/', core::config('cookie_domain'));
					}

					return true;
				}
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
			$db->sqlquery("SELECT `device-id` FROM `saved_sessions` WHERE `user_id` = ? AND `device-id` = ?", array($user_data['user_id'], $_COOKIE['gol-device']));
			// cookie didn't match, don't let them in, hacking attempt probable
			if ($db->num_rows() == 0)
			{
				setcookie('gol-device', "",  time()-60, '/');
				$new_device = 1;
			}
		}

		$device_id = '';
		// register the new device to their account, could probably add a small hook here to allow people to turn this email off at their own peril
		if ($new_device == 1)
		{
			$device_id = md5(mt_rand() . $user_data['user_id'] . $_SERVER['HTTP_USER_AGENT']);

			setcookie('gol-device', $device_id, time()+31556926, '/', core::config('cookie_domain'));

			if ($user_data['login_emails'] == 1)
			{
				// send email about new device
				$message = "<p>Hello <strong>{$user_data['username']}</strong>,</p>
				<p>We have detected a login from a new device, if you have just logged in yourself don't be alarmed (your cookies may have just been wiped at somepoint)! However, if you haven't just logged into the GamingOnLinux website you may want to let <a href=\"https://www.gamingonlinux.com/profiles/1\">liamdawe</a> know and change your password immediately.</p>
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

		if (isset($_SERVER['HTTP_USER_AGENT']))
		{
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
		}
		else
		{
			$user_agent = 'empty';
		}

		// keeping a log of logins, to review at anytime
		// TODO: need to implement user reviewing login history, would need to add login time for that, but easy as fook
		$db->sqlquery("INSERT INTO `saved_sessions` SET `user_id` = ?, `session_id` = ?, `browser_agent` = ?, `device-id` = ?, `date` = ?", array($user_data['user_id'], $generated_session, $user_agent, $device_id, date("Y-m-d")));

		$_SESSION['user_id'] = $user_data['user_id'];
		$_SESSION['username'] = $user_data['username'];
		$_SESSION['user_group'] = $user_data['user_group'];
		$_SESSION['secondary_user_group'] = $user_data['secondary_user_group'];
		$_SESSION['theme'] = $user_data['theme'];
		$_SESSION['new_login'] = 1;
		$_SESSION['activated'] = $user_data['activated'];
		$_SESSION['in_mod_queue'] = $user_data['in_mod_queue'];
		$_SESSION['logged_in'] = 1;
		$_SESSION['per-page'] = $user_data['per-page'];
		$_SESSION['articles-per-page'] = $user_data['articles-per-page'];
		$_SESSION['forum_type'] = $user_data['forum_type'];
		$_SESSION['single_article_page'] = $user_data['single_article_page'];
		$_SESSION['avatar'] = user::sort_avatar($user_data);
		$_SESSION['display_comment_alerts'] = $user_data['display_comment_alerts'];
		$_SESSION['email_options'] = $user_data['email_options'];
		$_SESSION['auto_subscribe'] = $user_data['auto_subscribe'];
		$_SESSION['auto_subscribe_email'] = $user_data['auto_subscribe_email'];
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
			$db->sqlquery("SELECT ".$this::$user_sql_fields." FROM `users` WHERE `user_id` = ?", array($_COOKIE['gol_stay']));
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
					$_SESSION['per-page'] = $user['per-page'];
					$_SESSION['articles-per-page'] = $user['articles-per-page'];
					$_SESSION['forum_type'] = $user['forum_type'];
					$_SESSION['single_article_page'] = $user['single_article_page'];
					$_SESSION['avatar'] = user::sort_avatar($user);
					$_SESSION['display_comment_alerts'] = $user['display_comment_alerts'];
					$_SESSION['email_options'] = $user['email_options'];
					$_SESSION['auto_subscribe'] = $user['auto_subscribe'];
					$_SESSION['auto_subscribe_email'] = $user['auto_subscribe_email'];

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
		unset($_SESSION['email_options']);
		unset($_SESSION['auto_subscribe']);
		unset($_SESSION['auto_subscribe_email']);

		$_SESSION['per-page'] = core::config('default-comments-per-page');
		$_SESSION['articles-per-page'] = 15;
		$_SESSION['forum_type'] = 'normal_forum';
		$_SESSION['single_article_page'] = 0;
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
			// this will make sure it is an image file, if it cant get an image size then its not an image
			if (!getimagesize($_FILES['new_image']['tmp_name']))
			{
				$this->message = 'Not an image!';
				return false;
			}

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

				$_SESSION['avatar'] = "/uploads/avatars/" . $imagename;

				$db->sqlquery("UPDATE `users` SET `avatar` = ?, `avatar_uploaded` = 1, `avatar_gravatar` = 0, `gravatar_email` = '', `avatar_gallery` = NULL WHERE `user_id` = ?", array($imagename, $_SESSION['user_id']));
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

	public static function sort_avatar($user_data)
	{
		$avatar = '';
		if ($user_data['avatar_gravatar'] == 1)
		{
			$avatar = 'https://www.gravatar.com/avatar/' . md5( strtolower( trim( $user_data['gravatar_email'] ) ) ) . '?d='. core::config('website_url') . '/uploads/avatars/no_avatar.png';
		}

		else if ($user_data['avatar_gallery'] != NULL)
		{
			$avatar = "/uploads/avatars/gallery/{$user_data['avatar_gallery']}.png";
		}

		// either uploaded or linked an avatar
		else if (!empty($user_data['avatar']) && $user_data['avatar_gravatar'] == 0)
		{
			$avatar = $user_data['avatar'];
			if ($user_data['avatar_uploaded'] == 1)
			{
				$avatar = "/uploads/avatars/{$user_data['avatar']}";
			}
		}

		// else no avatar, then as a fallback use gravatar if they have an email left-over
		else if (empty($user_data['avatar']) && $user_data['avatar_gravatar'] == 0 && $user_data['avatar_gallery'] == NULL)
		{
			if ($_SESSION['theme'] == 'dark')
			{
				$avatar = "/uploads/avatars/no_avatar_dark.png";
			}
			else if ($_SESSION['theme'] == 'light')
			{
				$avatar = "/uploads/avatars/no_avatar.png";
			}
		}

		return $avatar;
	}

	// give them a cake icon if they have been here for x years
	public function cake_day($reg_date, $username)
	{
		global $core;

		$this_year = date('Y');

		// sort date to correct format
		$reg_year = date('Y', $reg_date);
		$reg_month = date('m', $reg_date);
		$reg_day = date('d', $reg_date);

		$cake_icon = '';
		if ($reg_month == date('m') && $reg_day == date('d') && $reg_year != date('Y'))
		{
			// calculate how many years
			$total_years = date('Y') - $reg_year;

			$cake_icon = '<img src="/templates/default/images/cake.png" alt="'.$total_years.' years" class="tooltip-top" title="'.$username.' has been here for '.$total_years.' years" />';
		}
		return $cake_icon;
	}

	public function new_user_badge($reg_date)
	{
		$new_user_badge = '';
		if ($reg_date > strtotime("-7 days"))
		{
			$new_user_badge = '<span class="badge blue">New User</span>';
		}
		return $new_user_badge;
	}

	public function delete_user_notification($note_id)
	{
		global $db;

		$db->sqlquery("SELECT `owner_id` FROM `user_notifications` WHERE `id` = ?", array($note_id));
		$checker = $db->fetch();
		if ($checker['owner_id'] != $_SESSION['user_id'])
		{
			return false;
		}

		$db->sqlquery("DELETE FROM `user_notifications` WHERE `id` = ?", array($note_id));

		return true;
	}

	public function display_pc_info($user_id, $distribution)
	{
		global $db;

		$pc_info = [];

		$get_info = $db->sqlquery("SELECT
			`desktop_environment`,
			`what_bits`,
			`cpu_vendor`,
			`cpu_model`,
			`gpu_vendor`,
			`gpu_model`,
			`gpu_driver`,
			`ram_count`,
			`monitor_count`,
			`gaming_machine_type`,
			`resolution`,
			`dual_boot`,
			`gamepad`
			FROM
			`user_profile_info`
			WHERE
			`user_id` = ?", array($user_id));

		if (!empty($distribution) && $distribution != 'Not Listed')
		{
			$pc_info['distro'] = "<strong>Distribution:</strong> <img class=\"distro\" height=\"20px\" width=\"20px\" src=\"/templates/default/images/distros/{$distribution}.svg\" alt=\"{$distribution}\" /> {$distribution}";
		}

		while ($additionaldb = $get_info->fetch())
		{
			if (!empty($additionaldb['desktop_environment']))
			{
				$pc_info['desktop'] = '<strong>Desktop Environment:</strong> ' . $additionaldb['desktop_environment'];
			}

			if ($additionaldb['what_bits'] != NULL && !empty($additionaldb['what_bits']))
			{
				$pc_info['what_bits'] = '<strong>Distribution Architecture:</strong> '.$additionaldb['what_bits'];
			}

			if ($additionaldb['dual_boot'] != NULL && !empty($additionaldb['dual_boot']))
			{
				$pc_info['dual_boot'] = '<strong>Do you dual-boot with a different operating system?</strong> '.$additionaldb['dual_boot'];
			}

			if ($additionaldb['cpu_vendor'] != NULL && !empty($additionaldb['cpu_vendor']))
			{
				$pc_info['cpu_vendor'] = '<strong>CPU Vendor:</strong> '.$additionaldb['cpu_vendor'];
			}

			if ($additionaldb['cpu_model'] != NULL && !empty($additionaldb['cpu_model']))
			{
				$pc_info['cpu_model'] = '<strong>CPU Model:</strong> ' . $additionaldb['cpu_model'];
			}

			if ($additionaldb['gpu_vendor'] != NULL && !empty($additionaldb['gpu_vendor']))
			{
				$pc_info['gpu_vendor'] = '<strong>GPU Vendor:</strong> ' . $additionaldb['gpu_vendor'];
			}

			if ($additionaldb['gpu_model'] != NULL && !empty($additionaldb['gpu_model']))
			{
				$pc_info['gpu_model'] = '<strong>GPU Model:</strong> ' . $additionaldb['gpu_model'];
			}

			if ($additionaldb['gpu_driver'] != NULL && !empty($additionaldb['gpu_driver']))
			{
				$pc_info['gpu_driver'] = '<strong>GPU Driver:</strong> ' . $additionaldb['gpu_driver'];
			}

			if ($additionaldb['ram_count'] != NULL && !empty($additionaldb['ram_count']))
			{
				$pc_info['ram_count'] = '<strong>RAM:</strong> '.$additionaldb['ram_count'].'GB';
			}

			if ($additionaldb['monitor_count'] != NULL && !empty($additionaldb['monitor_count']))
			{
				$pc_info['monitor_count'] = '<strong>Monitors:</strong> '.$additionaldb['monitor_count'];
			}

			if ($additionaldb['resolution'] != NULL && !empty($additionaldb['resolution']))
			{
				$pc_info['resolution'] = '<strong>Resolution:</strong> '.$additionaldb['resolution'];
			}

			if ($additionaldb['gaming_machine_type'] != NULL && !empty($additionaldb['gaming_machine_type']))
			{
				$pc_info['gaming_machine_type'] = '<strong>Main gaming machine:</strong> '.$additionaldb['gaming_machine_type'];
			}

			if ($additionaldb['gamepad'] != NULL && !empty($additionaldb['gamepad']))
			{
				$pc_info['gamepad'] = '<strong>Gamepad:</strong> '.$additionaldb['gamepad'];
			}
		}
		return $pc_info;
	}
}

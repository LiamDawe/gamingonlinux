<?php
class user
{
	public static $user_group_list;

	public static $user_sql_fields = "`user_id`, `single_article_page`, `per-page`,
	`articles-per-page`, `username`, `user_group`, `secondary_user_group`,
	`banned`, `theme`, `activated`, `in_mod_queue`, `email`, `login_emails`,
	`forum_type`, `avatar`, `avatar_uploaded`, `avatar_gravatar`, `gravatar_email`, `avatar_gallery`,
	`display_comment_alerts`, `email_options`, `auto_subscribe`, `auto_subscribe_email`, `distro`, `timezone`";

	function check_session()
	{
		global $db;

		$logout = 0;

		if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			// we know it's numeric, but doubly be sure and don't allow any html
			$safe_id = (int) $_SESSION['user_id'];

			// check if they actually have any saved sessions, if they don't then logout to cancel everything
			// this is also if we need to remove everyone being logged in due to any security issues
			$db->sqlquery("SELECT `user_id` FROM `saved_sessions` WHERE `user_id` = ?", array($safe_id));
			if ($db->num_rows() == 0)
			{
				$logout = 1;
			}
		}

		if ($logout == 1)
		{
			self::logout();
		}

		// help prevent Session Fixation attacks
		// Make sure we have a canary set
		if (!isset($_SESSION['canary']))
		{
		    session_regenerate_id(true);
		    $_SESSION['canary'] = time();
		}
		// Regenerate session ID every five minutes:
		if ($_SESSION['canary'] < time() - 300)
		{
		    session_regenerate_id(true);
		    $_SESSION['canary'] = time();
		}
	}

	// normal login form
	function login($username, $password, $remember_username, $stay)
	{
		global $db;
		
		if (!empty($password))
		{
			$db->sqlquery("SELECT `password` FROM `users` WHERE (`username` = ? OR `email` = ?)", array($username, $username));
			if ($db->num_rows() > 0)
			{
				$info = $db->fetch();

				if (password_verify($password, $info['password']))
				{
					$db->sqlquery("SELECT ".$this::$user_sql_fields." FROM `users` WHERE (`username` = ? OR `email` = ?)", array($username, $username));

					if ($db->num_rows() == 1)
					{
						$user_info = $db->fetch();

						$this->check_banned($user_info);

						$generated_session = md5(mt_rand() . $user_info['user_id'] . $_SERVER['HTTP_USER_AGENT']);

						// update IP address and last login
						$db->sqlquery("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $user_info['user_id']));

						$this->new_login($user_info, $generated_session);

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
					$_SESSION['message'] = "password_match";
					return false;
				}
			}
			else
			{
				$_SESSION['message'] = "bad_username";
				return false;
			}
		}
		else
		{
			$_SESSION['message'] = "no_password";
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

			$_SESSION['message'] = 'banned';
			if (core::config('pretty_urls') == 1)
			{
				header("Location: /home/");
			}
			else
			{
				header("Location: ".core::config('website_url')."index.php?module=home");
			}
			die();
		}
	}

	public static function register_session($user_data)
	{
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
		$_SESSION['distro'] = $user_data['distro'];
		
		// force a default and only set it if it's properly set by the user
		$_SESSION['timezone'] = 'UTC';
		if (isset($user_data['timezone']) && !empty($user_data['timezone']))
		{
			$_SESSION['timezone'] = $user_data['timezone'];
		}

		session_regenerate_id(true);
		$_SESSION['canary'] = time();
	}

	// check if it's a new device, then set the session up
	public static function new_login($user_data, $generated_session)
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

			if ($user_data['login_emails'] == 1 && core::config('send_emails'))
			{
				// send email about new device
				$message = "<p>Hello <strong>{$user_data['username']}</strong>,</p>
				<p>We have detected a login from a new device, if you have just logged in yourself don't be alarmed (your cookies may have just been wiped at somepoint)! However, if you haven't just logged into the ".core::config('site_title')." ".core::config('website_url')." website you may want to let the admin know and change your password immediately.</p>
				<div>
				<hr>
				<p>Login detected from: {$_SERVER['HTTP_USER_AGENT']} on " . date("Y-m-d H:i:s") . "</p>";

				$plain_message = "Hello {$user_data['username']},\r\nWe have detected a login from a new device, if you have just logged in yourself don't be alarmed! However, if you haven't just logged into the ".core::config('site_title')." ".core::config('website_url')." website you may want to let the admin know and change your password immediately.\r\n\r\nLogin detected from: {$_SERVER['HTTP_USER_AGENT']} on " . date("Y-m-d H:i:s");

				$mail = new mail($user_data['email'], core::config('site_title') . ": New Login Notification", $message, $plain_message);
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

		self::register_session($user_data);
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
			$user_data = $db->fetch();

			if ($user_data['banned'] == 0)
			{
				// now check IP ban
				$db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip));
				if ($db->num_rows() == 1)
				{
					setcookie('gol_stay', "",  time()-60, '/');
					$this->message = "banned";
					return false;
				}

				else
				{
					// update IP address and last login
					$db->sqlquery("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $user_data['user_id']));

					self::register_session($user_data);

					return true;
				}
			}

			else
			{
				setcookie('gol_stay', "",  time()-60, '/');

				// update their ip in the user table
				$db->sqlquery("UPDATE `users` SET `ip` = ? WHERE `user_id` = ?", array(core::$ip, $user_data['user_id']));

				// search the ip list, if it's not on it then add it in
				$db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip));
				if ($db->num_rows() == 0)
				{
					$db->sqlquery("INSERT INTO `ipbans` SET `ip` = ?", array(core::$ip));
				}
				$this->message = "banned";
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

		// remove all session information
		$_SESSION = array();
		
		// delete the session cookie
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
		);
		
		session_destroy();
		
		$_SESSION['timezone'] = 'UTC';
		$_SESSION['per-page'] = core::config('default-comments-per-page');
		$_SESSION['articles-per-page'] = 15;
		$_SESSION['forum_type'] = 'normal_forum';
		$_SESSION['single_article_page'] = 0;
		setcookie('gol_stay', "",  time()-60, '/');
		setcookie('gol_session', "",  time()-60, '/');
		setcookie('gol-device', "",  time()-60, '/');
		setcookie('steamID', '', -1, '/');

		session_regenerate_id(true);
		$_SESSION['canary'] = time();

		header("Location: ".core::config('website_url')."index.php");
	}

	// check a users group to perform a certain task, can check two groups
	// useful for seeing if they are an admin or editor to perform editing, deleting, publishing etc
	function check_group($check_groups = NULL)
	{
		if ( isset($_SESSION['user_group']) || isset($_SESSION['secondary_user_group']) )
		{
			$user_group = (int) $_SESSION['user_group'];
			$second_group = (int) $_SESSION['secondary_user_group'];
			
			if ( is_array($check_groups) )
			{
				if ( in_array($user_group, $check_groups) || in_array($second_group, $check_groups) )
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
				if ($user_group == $check_groups || $second_group == $check_groups)
				{
					return true;
				}
				else
				{
					return false;
				}
			}
		}
		else
		{
			return false;
		}
	}

	public static function sort_avatar($user_data)
	{
		if ($_SESSION['theme'] == 'dark')
		{
			$default_avatar = core::config('website_url') . "uploads/avatars/no_avatar_dark.png";
		}
		else if ($_SESSION['theme'] == 'default')
		{
			$default_avatar = core::config('website_url') . "uploads/avatars/no_avatar.png";
		}
			
		$avatar = '';
		if ($user_data['avatar_gravatar'] == 1)
		{
			$avatar = 'https://www.gravatar.com/avatar/' . md5( strtolower( trim( $user_data['gravatar_email'] ) ) ) . '?d='. $default_avatar;
		}

		else if ($user_data['avatar_gallery'] != NULL)
		{
			$avatar = core::config('website_url') . "uploads/avatars/gallery/{$user_data['avatar_gallery']}.png";
		}

		// either uploaded or linked an avatar
		else if (!empty($user_data['avatar']) && $user_data['avatar_gravatar'] == 0)
		{
			$avatar = $user_data['avatar'];
			if ($user_data['avatar_uploaded'] == 1)
			{
				$avatar = core::config('website_url') . "uploads/avatars/{$user_data['avatar']}";
			}
		}

		// else no avatar, then as a fallback use gravatar if they have an email left-over
		else if (empty($user_data['avatar']) && $user_data['avatar_gravatar'] == 0 && $user_data['avatar_gallery'] == NULL)
		{
			$avatar = $default_avatar;
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

	public static function display_pc_info($user_id, $distribution)
	{
		global $db;

		$pc_info = [];

		$counter = 0;

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
			`gamepad`,
			`date_updated`
			FROM
			`user_profile_info`
			WHERE
			`user_id` = ?", array($user_id));

		if (!empty($distribution) && $distribution != 'Not Listed')
		{
			$counter++;
			$pc_info['distro'] = "<strong>Distribution:</strong> <img class=\"distro\" height=\"20px\" width=\"20px\" src=\"/templates/default/images/distros/{$distribution}.svg\" alt=\"{$distribution}\" /> {$distribution}";
		}

		while ($additionaldb = $get_info->fetch())
		{
			if (!empty($additionaldb['desktop_environment']))
			{
				$counter++;
				$pc_info['desktop'] = '<strong>Desktop Environment:</strong> ' . $additionaldb['desktop_environment'];
			}

			if ($additionaldb['what_bits'] != NULL && !empty($additionaldb['what_bits']))
			{
				$counter++;
				$pc_info['what_bits'] = '<strong>Distribution Architecture:</strong> '.$additionaldb['what_bits'];
			}

			if ($additionaldb['dual_boot'] != NULL && !empty($additionaldb['dual_boot']))
			{
				$counter++;
				$pc_info['dual_boot'] = '<strong>Do you dual-boot with a different operating system?</strong> '.$additionaldb['dual_boot'];
			}

			if ($additionaldb['cpu_vendor'] != NULL && !empty($additionaldb['cpu_vendor']))
			{
				$counter++;
				$pc_info['cpu_vendor'] = '<strong>CPU Vendor:</strong> '.$additionaldb['cpu_vendor'];
			}

			if ($additionaldb['cpu_model'] != NULL && !empty($additionaldb['cpu_model']))
			{
				$counter++;
				$pc_info['cpu_model'] = '<strong>CPU Model:</strong> ' . $additionaldb['cpu_model'];
			}

			if ($additionaldb['gpu_vendor'] != NULL && !empty($additionaldb['gpu_vendor']))
			{
				$counter++;
				$pc_info['gpu_vendor'] = '<strong>GPU Vendor:</strong> ' . $additionaldb['gpu_vendor'];
			}

			if ($additionaldb['gpu_model'] != NULL && !empty($additionaldb['gpu_model']))
			{
				$counter++;
				$pc_info['gpu_model'] = '<strong>GPU Model:</strong> ' . $additionaldb['gpu_model'];
			}

			if ($additionaldb['gpu_driver'] != NULL && !empty($additionaldb['gpu_driver']))
			{
				$counter++;
				$pc_info['gpu_driver'] = '<strong>GPU Driver:</strong> ' . $additionaldb['gpu_driver'];
			}

			if ($additionaldb['ram_count'] != NULL && !empty($additionaldb['ram_count']))
			{
				$counter++;
				$pc_info['ram_count'] = '<strong>RAM:</strong> '.$additionaldb['ram_count'].'GB';
			}

			if ($additionaldb['monitor_count'] != NULL && !empty($additionaldb['monitor_count']))
			{
				$counter++;
				$pc_info['monitor_count'] = '<strong>Monitors:</strong> '.$additionaldb['monitor_count'];
			}

			if ($additionaldb['resolution'] != NULL && !empty($additionaldb['resolution']))
			{
				$counter++;
				$pc_info['resolution'] = '<strong>Resolution:</strong> '.$additionaldb['resolution'];
			}

			if ($additionaldb['gaming_machine_type'] != NULL && !empty($additionaldb['gaming_machine_type']))
			{
				$counter++;
				$pc_info['gaming_machine_type'] = '<strong>Main gaming machine:</strong> '.$additionaldb['gaming_machine_type'];
			}

			if ($additionaldb['gamepad'] != NULL && !empty($additionaldb['gamepad']))
			{
				$counter++;
				$pc_info['gamepad'] = '<strong>Gamepad:</strong> '.$additionaldb['gamepad'];
			}
		}
		$pc_info['counter'] = $counter;
		return $pc_info;
	}
	
	// check their subscription details to an item (article, forum topic etc)
	function check_subscription($data_id, $type)
	{
		global $db;
		
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0 && core::is_number($data_id))
		{
			$sql_table = '';
			if ($type == 'article')
			{
				$sql_table = 'articles_subscriptions';
				$sql_id_field = 'article_id';
			}
			if ($type == 'forum')
			{
				$sql_table = 'forum_topics_subscriptions';
				$sql_id_field = 'topic_id';
			}
			
			// see if they are subscribed right now, if they are and they untick the subscribe box, remove their subscription as they are unsubscribing
			$subscribe_check = [];
			$db->sqlquery("SELECT `$sql_id_field`, `emails`, `send_email` FROM `$sql_table` WHERE `user_id` = ? AND `$sql_id_field` = ?", array($_SESSION['user_id'], $data_id));
			$sub_exists = $db->num_rows();

			if ($sub_exists == 1)
			{
				$check_current_sub = $db->fetch();
			}

			$subscribe_check['auto_subscribe'] = '';
			if ($_SESSION['auto_subscribe'] == 1 || $sub_exists == 1)
			{
				$subscribe_check['auto_subscribe'] = 'checked';
			}

			$subscribe_check['emails'] = '';
			if ((isset($check_current_sub) && $check_current_sub['emails'] == 1) || !isset($check_current_sub) && $_SESSION['auto_subscribe_email'] == 1)
			{
				$subscribe_check['emails'] = 'selected';
			}
			
			return $subscribe_check;
		}
	}
	
	public static function user_profile_icons($profile_fields, $data)
	{
		$profile_fields_output = '';

		foreach ($profile_fields as $field)
		{
			if (!empty($data[$field['db_field']]))
			{
				if ( $data[$field['db_field']] == $field['base_link'] )
				{
					//Skip if it's only the first part of the url
					continue;
				}
								
				if ($field['db_field'] == 'website')
				{
					$url = parse_url($data[$field['db_field']]);
					if((!isset($url['scheme'])) || (isset($url['scheme']) && $url['scheme'] != 'https' && $url['scheme'] != 'http'))
					{
						$data[$field['db_field']] = 'http://' . $data[$field['db_field']];
					}
				}

				$url = '';
				if ($field['base_link_required'] == 1 && strpos($data[$field['db_field']], $field['base_link']) === false ) //base_link_required and not already in the database
				{
					$url = $field['base_link'];
				}

				$image = '';
				if (isset($field['image']) && $field['image'] != NULL)
				{
					$image = "<img src=\"{$field['image']}\" alt=\"{$field['name']}\" />";
				}

				$span = '';
				if (isset($field['span']))
				{
					$span = $field['span'];
				}
				$into_output = '';
				if ($field['name'] != 'Distro')
				{
					$into_output .= "<li><a href=\"$url{$data[$field['db_field']]}\">$image$span</a></li>";
				}

				$profile_fields_output .= $into_output;
			}
		}
		
		return $profile_fields_output;
	}
	
	public static function grab_user_groups()
	{
		global $db;
		
		$db->sqlquery("SELECT `group_id`, `group_name`, `show_badge`, `badge_text`, `badge_colour` FROM `user_groups` ORDER BY `group_name` ASC");
		self::$user_group_list = $db->fetch_all_rows(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
	}
	
	public static function user_badges($data, $list = 0)
	{
		$badges = [];
		if (isset($data['game_developer']) && $data['game_developer'] == 1)
		{
			$text = '<span class="badge yellow">Game Dev</span>';
			
			if ($list == 1)
			{
				$text = '<li>'.$text.'</li>';
			}
			
			$badges[] = $text;
		}
		if (isset($data['register_date']) && $data['register_date'] > strtotime("-7 days"))
		{
			$text = '<span class="badge blue">New User</span>';
			
			if ($list == 1)
			{
				$text = '<li>'.$text.'</li>';
			}
			
			$badges[] = $text;
		}
		if (array_key_exists($data['user_group'], self::$user_group_list) && self::$user_group_list[$data['user_group']]['show_badge'] == 1)
		{
			$text = '<span class="badge '.self::$user_group_list[$data['user_group']]['badge_colour'].'">'.self::$user_group_list[$data['user_group']]['badge_text'].'</span>';
			if ($list == 1)
			{
				$text = '<li>'.$text.'</li>';
			}
			$badges[] = $text;
		}
		if (array_key_exists($data['secondary_user_group'], self::$user_group_list) && self::$user_group_list[$data['secondary_user_group']]['show_badge'] == 1)
		{
			// admins and main editors should not get the supporter badge
			if ($data['secondary_user_group'] == 6 && $data['user_group'] != 1 && $data['user_group'] != 2)
			{
				$text = '<span class="badge '.self::$user_group_list[$data['secondary_user_group']]['badge_colour'].'">'.self::$user_group_list[$data['secondary_user_group']]['badge_text'].'</span>';
				if ($list == 1)
				{
					$text = '<li>'.$text.'</li>';
				}
				$badges[] = $text;
			}
		}
		return $badges;
	}
}

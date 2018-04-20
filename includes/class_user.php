<?php
class user
{
	protected $db;
	private $core;
	// cache stored user information grabbed from the database, built up as the script runs
	public $user_details = [];
	public static $user_group_list;
	public static $user_sql_fields = "`user_id`, `single_article_page`, `per-page`,
	`articles-per-page`, `username`, `user_group`, `secondary_user_group`,
	`banned`, `theme`, `activated`, `in_mod_queue`, `email`, `login_emails`,
	`forum_type`, `avatar`, `avatar_uploaded`, `avatar_gravatar`, `gravatar_email`, `avatar_gallery`,
	`display_comment_alerts`, `admin_comment_alerts`, `email_options`, `auto_subscribe`, `auto_subscribe_email`, `distro`, `timezone`";
	
	public $user_groups;
	public $blocked_users = [];

	public $cookie_length = 60*60*24*30; // 30 days
	
	function __construct($dbl, $core)
	{
		$this->db = $dbl;
		$this->core = $core;
		$this->grab_user_groups();
		$this->block_list();
		$this->check_session();
	}

	// check their session is valid and register guest session if needed
	function check_session()
	{
		$logout = 0;

		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			// we know it's numeric, but doubly be sure and don't allow any html
			$safe_id = (int) $_SESSION['user_id'];

			// check if they actually have any saved sessions, if they don't then logout to cancel everything
			// this is also if we need to remove everyone being logged in due to any security issues
			$session_exists = $this->db->run("SELECT `user_id` FROM `saved_sessions` WHERE `user_id` = ?", [$safe_id])->fetch();
			if (!$session_exists)
			{
				$logout = 1;
			}
			$this->user_details = $this->db->run("SELECT ".$this::$user_sql_fields." FROM `users` WHERE `user_id` = ?", [$_SESSION['user_id']])->fetch();
			
			$this->check_banned();
		}
		else
		{
			if ($this->stay_logged_in() == false) // make a guest session if they aren't saved
			{
				$_SESSION['user_id'] = 0;
				$_SESSION['per-page'] = $this->core->config('default-comments-per-page');
				$_SESSION['articles-per-page'] = 15;
				$this->user_details = ['theme' => 'default', 'timezone' => 'UTC', 'single_article_page' => 0, 'user_id' => 0, 'forum_type' => 'normal', 'avatar_gravatar' => 0, 'avatar_gallery' => NULL];
			}
		}
		
		$this->user_groups = $this->get_user_groups();

		if ($logout == 1)
		{
			$this->logout();
			return;
		}
	}

	// normal login form
	function login($username, $password, $remember_username, $stay)
	{
		global $db;
		
		if (!empty($password))
		{
			// check username/email exists first
			$info = $this->db->run("SELECT `password` FROM `users` WHERE (`username` = ? OR `email` = ?)", [$username, $username])->fetch();
			if ($info)
			{
				// now check password matches
				if (password_verify($password, $info['password']))
				{
					$this->user_details = $this->db->run("SELECT ".$this::$user_sql_fields." FROM `users` WHERE (`username` = ? OR `email` = ?)", [$username, $username])->fetch();

					$this->check_banned($this->user_details['user_id']);

					$generated_session = md5(mt_rand() . $this->user_details['user_id'] . $_SERVER['HTTP_USER_AGENT']);

					// update IP address and last login
					$this->db->run("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $this->user_details['user_id']));

					$this->new_login($generated_session);

					if ($remember_username == 1)
					{
						setcookie('remember_username', $username,  time()+$this->cookie_length, '/', $this->core->config('cookie_domain'));
					}

					if ($stay == 1)
					{
						setcookie('gol_stay', $this->user_details['user_id'], time()+$this->cookie_length, '/', $this->core->config('cookie_domain'));
						setcookie('gol_session', $generated_session, time()+$this->cookie_length, '/', $this->core->config('cookie_domain'));
					}

					return true;
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

	public function check_banned()
	{
		$ip_banned = 0;

		// now check IP ban
		$check_ip = $this->db->run("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", [core::$ip])->fetch();
		if ($check_ip)
		{
			$ip_banned = 1;
		}

		if ($this->user_details['banned'] == 1 || $ip_banned == 1)
		{
			// update their ip in the user table
			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
			{
				$this->db->run("UPDATE `users` SET `ip` = ? WHERE `user_id` = ?", [core::$ip, $_SESSION['user_id']]);
			}

			// search the ip list, if it's not on it then add it in
			$search_ips = $this->db->run("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", [core::$ip])->fetch();
			if (!$search_ips)
			{
				$this->db->run("INSERT INTO `ipbans` SET `ip` = ?", [core::$ip]);
			}
			
			$this->logout(1);
		}
	}
	
	public function register_session()
	{
		if (!isset($_SESSION))
		{
			error_log("Session not started in: " . $this->core->current_page());
		}
		session_regenerate_id(true);
		
		$_SESSION['user_id'] = $this->user_details['user_id'];
		$_SESSION['username'] = $this->user_details['username'];
		$_SESSION['new_login'] = 1;
		$_SESSION['activated'] = $this->user_details['activated'];
		$_SESSION['per-page'] = $this->user_details['per-page'];
		$_SESSION['articles-per-page'] = $this->user_details['articles-per-page'];
		$_SESSION['email_options'] = $this->user_details['email_options'];
		$_SESSION['auto_subscribe'] = $this->user_details['auto_subscribe'];
		$_SESSION['auto_subscribe_email'] = $this->user_details['auto_subscribe_email'];
	}

	function block_list()
	{
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			$this->blocked_users = $this->db->run("SELECT u.`username`, b.`blocked_id` FROM `user_block_list` b INNER JOIN `users` u ON u.user_id = b.blocked_id WHERE b.`user_id` = ? ORDER BY u.`username` ASC", array($_SESSION['user_id']))->fetch_all(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
		}
		else
		{
			$this->blocked_users = [];
		}
	}
	
	// return a list of group ids that have a particular permission
	function get_group_ids($permission)
	{
		$allowed_groups = $this->db->run("SELECT m.`group_id` FROM `user_group_permissions_membership` m INNER JOIN `user_groups` g ON m.`group_id` = g.`group_id` INNER JOIN `user_group_permissions` p ON p.id = m.permission_id WHERE p.`name` = ?", [$permission])->fetch_all(PDO::FETCH_COLUMN);
		
		return $allowed_groups;
	}
	
	// check if a user is able to do or not do something
	function can($do)
	{
		// simplistic true or false check for a single permission type
		if (!is_array($do))
		{			
			// find all groups that have that permission
			$allowed_groups = $this->db->run("SELECT p.name, m.`group_id` FROM `user_group_permissions_membership` m INNER JOIN `user_groups` g ON m.`group_id` = g.`group_id` INNER JOIN `user_group_permissions` p ON p.id = m.permission_id WHERE p.`name` = ?", [$do])->fetch_all(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);

			foreach ($this->user_groups as $group)
			{
				// at least one group they are has it checked, return true
				if (in_array($group, $allowed_groups[$do]))
				{
					return true;
				}
			}
			// if we didn't find any time the group value = 1, then none of their user groups is allowed
			return false;
		}

		// checking multiple permissions at the same time
		if (is_array($do))
		{
			$in  = str_repeat('?,', count($do) - 1) . '?';

			// find all groups that have that permission
			$allowed_groups = $this->db->run("SELECT p.name, m.`group_id` FROM `user_group_permissions_membership` m INNER JOIN `user_groups` g ON m.`group_id` = g.`group_id` INNER JOIN `user_group_permissions` p ON p.id = m.permission_id WHERE p.`name` IN ( $in )", $do)->fetch_all(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);

			$final = [];
			// check over their user groups and see if their group is allowed to use the permissions
			foreach ($this->user_groups as $group)
			{
				foreach ($allowed_groups as $key => $checker)
				{
					// at least one group they are has it checked, return true
					if (in_array($group, $checker))
					{
						$final[$key] = 1;
					}
					else if (!in_array($group, $checker))
					{
						if (!isset($final[$key]))
						{
							$final[$key] = 0;
						}
					}
				}
			}
			return $final;
		}		
	}
	
	// check if it's a new device, then set the session up
	public function new_login($generated_session)
	{
		// check if it's a new device straight away
		$new_device = 0;
		if (!isset($_COOKIE['gol-device']))
		{
			$new_device = 1;
		}

		// they have a device cookie, let's check it bitches
		if (isset($_COOKIE['gol-device']))
		{
			$device_test = $this->db->run("SELECT `device-id` FROM `saved_sessions` WHERE `user_id` = ? AND `device-id` = ?", array($this->user_details['user_id'], $_COOKIE['gol-device']))->fetch();
			// cookie didn't match, don't let them in, hacking attempt probable
			if (!$device_test)
			{
				setcookie('gol-device', "",  time()-60, '/');
				$new_device = 1;
			}
		}

		$device_id = '';
		// register the new device to their account
		if ($new_device == 1)
		{
			if ($this->user_details['login_emails'] == 1 && $this->core->config('send_emails'))
			{
				$device_id = md5(mt_rand() . $this->user_details['user_id'] . $_SERVER['HTTP_USER_AGENT']);

				setcookie('gol-device', $device_id, time()+$this->cookie_length, '/', $this->core->config('cookie_domain'));

				// send email about new device
				$html_message = "<p>Hello <strong>" . $this->user_details['username'] . "</strong>,</p>
				<p>We have detected a login from a new device, if you have just logged in yourself don't be alarmed (your cookies may have just been wiped at somepoint)! However, if you haven't just logged into the <a href=\"".$this->core->config('website_url')."\">".$this->core->config('site_title')."</a> website you may want to let the admin know and change your password immediately.</p>
				<div>
				<hr>
				<p>Login detected from: {$_SERVER['HTTP_USER_AGENT']} on " . date("Y-m-d H:i:s") . "</p>
				<hr>
				<p>You can turn this notice off any time from your User Control Panel, in the Notification Preferences page.</p>";

				$plain_message = "Hello " . $this->user_details['username'] . ",\r\nWe have detected a login from a new device, if you have just logged in yourself don't be alarmed! However, if you haven't just logged into the ".$this->core->config('site_title')." ".$this->core->config('website_url')." website you may want to let the admin know and change your password immediately.\r\n\r\nLogin detected from: {$_SERVER['HTTP_USER_AGENT']} on " . date("Y-m-d H:i:s") . "\r\nYou can turn this notice off any time from your User Control Panel, in the Notification Preferences page.";

				$mail = new mailer($this->core);
				$mail->sendMail($this->user_details['email'], $this->core->config('site_title') . ": New Login Notification", $html_message, $plain_message);
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
		$this->db->run("INSERT INTO `saved_sessions` SET `user_id` = ?, `session_id` = ?, `browser_agent` = ?, `device-id` = ?, `date` = ?", array($this->user_details['user_id'], $generated_session, $user_agent, $device_id, date("Y-m-d")));

		$this->register_session();
	}

	// if they have a stay logged in cookie log them in
	function stay_logged_in()
	{
 		global $core;
		
		if (isset($_COOKIE['gol_stay']) && isset($_COOKIE['gol_session']))
		{
			$session = $this->db->run("SELECT `session_id` FROM `saved_sessions` WHERE `user_id` = ? AND `session_id` = ?", array($_COOKIE['gol_stay'], $_COOKIE['gol_session']))->fetch();

			if ($session)
			{
				// login then
				$this->user_details = $this->db->run("SELECT ".$this::$user_sql_fields." FROM `users` WHERE `user_id` = ?", array($_COOKIE['gol_stay']))->fetch();
				
				$this->check_banned();

				// update IP address and last login
				$this->db->run("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $this->user_details['user_id']));

				$this->register_session();

				return true;
			}
			else
			{
				setcookie('gol_stay', "",  time()-60, '/');
				setcookie('gol_session', "",  time()-60, '/');
				setcookie('gol-device', "",  time()-60, '/');
				setcookie('steamID', '', -1, '/');
				
				return false;
			}
		}

		else
		{
			return false;
		}
	}

	function logout($banned = 0, $redirect = 1)
	{
		if (isset($_COOKIE['gol-device']))
		{
			$this->db->run("DELETE FROM `saved_sessions` WHERE `user_id` = ? AND `device-id` = ?", [$_SESSION['user_id'], $_COOKIE['gol-device']]);
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
		
		session_start();
		
		session_regenerate_id(true);

		setcookie('gol_stay', "",  time()-60, '/');
		setcookie('gol_session', "",  time()-60, '/');
		setcookie('gol-device', "",  time()-60, '/');
		setcookie('steamID', '', -1, '/');

		if ($banned == 1)
		{
			$_SESSION['message'] = 'banned';
		}
		
		if ($redirect == 1)
		{
			header("Location: ".$this->core->config('website_url'));
			die();
		}
	}
	
	// get a list of this users user groups, store it so we can access it as many times as required without hitting the DB constantly
	function get_user_groups()
	{
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			$their_groups = $this->db->run("SELECT `group_id` FROM `user_group_membership` WHERE `user_id` = ?", [$_SESSION['user_id']])->fetch_all(PDO::FETCH_COLUMN);
		}
		else
		{
			$their_groups = [0 => 4];
		}
		return $their_groups;
	}

	// check a users group to perform a certain task
	// useful for seeing if they are an admin or editor to perform editing, deleting, publishing etc
	function check_group($check_groups = NULL)
	{	
		if ( is_array($check_groups) )
		{
			foreach ($check_groups as $group)
			{
				if ( in_array($group, $this->user_groups) )
				{
					return true;
				}
			}
		}
		else
		{
			if (in_array($check_groups, $this->user_groups))
			{
				return true;
			}
		}

		return false;
	}

	// helper function to display the correct avatar, for the given user data
	public function sort_avatar($data)
	{
		$your_theme = $this->user_details['theme'];
		
		if ($your_theme == 'dark')
		{
			$default_avatar = $this->core->config('website_url') . "uploads/avatars/no_avatar_dark.png";
		}
		else if ($your_theme == 'default')
		{
			$default_avatar = $this->core->config('website_url') . "uploads/avatars/no_avatar.png";
		}
		
		if (!empty($data))
		{
			$avatar = '';
			if ($data['avatar_gravatar'] == 1)
			{
				$avatar = 'https://www.gravatar.com/avatar/' . md5( strtolower( trim( $data['gravatar_email'] ) ) ) . '?d='. $default_avatar;
			}

			else if ($data['avatar_gallery'] != NULL)
			{
				$avatar = $this->core->config('website_url') . "uploads/avatars/gallery/{$data['avatar_gallery']}.png";
			}

			// either uploaded or linked an avatar
			else if (!empty($data['avatar']) && $data['avatar_gravatar'] == 0)
			{
				$avatar = $data['avatar']; // for google login avatars
				if ($data['avatar_uploaded'] == 1)
				{
					$avatar = $this->core->config('website_url') . "uploads/avatars/{$data['avatar']}";
				}
			}

			// else no avatar, then as a fallback use gravatar if they have an email left-over
			else if (empty($data['avatar']) && $data['avatar_gravatar'] == 0 && $data['avatar_gallery'] == NULL)
			{
				$avatar = $default_avatar;
			}
		}
		else
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
		$checker = $this->db->run("SELECT `owner_id` FROM `user_notifications` WHERE `id` = ?", array($note_id))->fetch();
		if ($checker['owner_id'] != $_SESSION['user_id'])
		{
			return false;
		}

		$this->db->run("DELETE FROM `user_notifications` WHERE `id` = ?", array($note_id));

		return true;
	}

	public function display_pc_info($user_id)
	{
		$pc_info = [];

		$counter = 0;

		$additionaldb = $this->db->run("SELECT
			p.`desktop_environment`,
			p.`what_bits`,
			p.`cpu_vendor`,
			p.`cpu_model`,
			p.`gpu_vendor`,
			p.`gpu_driver`,
			p.`ram_count`,
			p.`monitor_count`,
			p.`gaming_machine_type`,
			p.`resolution`,
			p.`dual_boot`,
			p.`wine`,
			p.`gamepad`,
			p.`date_updated`,
			u.`distro`,
			g.`id` AS `gpu_id`, 
			g.`name` AS `gpu_model`
			FROM
			`user_profile_info` p
			INNER JOIN
			`users` u ON u.user_id = p.user_id
			LEFT JOIN
			`gpu_models` g ON g.id = p.gpu_model 
			WHERE
			p.`user_id` = ?", array($user_id))->fetch();


		if (!empty($additionaldb['distro']) && $additionaldb['distro'] != 'Not Listed')
		{
			$counter++;
			$pc_info['distro'] = "<strong>Distribution:</strong> <img class=\"distro\" height=\"20px\" width=\"20px\" src=\"/templates/default/images/distros/{$additionaldb['distro']}.svg\" alt=\"{$additionaldb['distro']}\" /> {$additionaldb['distro']}";
		}
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

		if ($additionaldb['wine'] != NULL && !empty($additionaldb['wine']))
		{
			$counter++;
			$pc_info['wine'] = '<strong>When was the last time you used Wine to play a Windows game?</strong> '.$additionaldb['wine'];
		}
		
		if ($additionaldb['ram_count'] != NULL && !empty($additionaldb['ram_count']))
		{
			$counter++;
			$pc_info['ram_count'] = '<strong>RAM:</strong> '.$additionaldb['ram_count'].'GB';
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
			$check_current_sub = $this->db->run("SELECT `$sql_id_field`, `emails`, `send_email` FROM `$sql_table` WHERE `user_id` = ? AND `$sql_id_field` = ?", array($_SESSION['user_id'], $data_id))->fetch();

			$subscribe_check['auto_subscribe'] = '';
			if ($check_current_sub || $_SESSION['auto_subscribe'] == 1)
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
	
	// this function gets a list of [user_id => [group id, group id], another_user_id => [group_id, group_id]]
	// helper function for grabbing user badges for comments, forum posts etc
	public function post_group_list($user_ids)
	{
		$in  = str_repeat('?,', count($user_ids) - 1) . '?';
		$group_list = $this->db->run("SELECT u.`user_id`, m.`group_id` FROM `users` u LEFT JOIN `user_group_membership` m ON u.user_id = m.user_id WHERE u.`user_id` IN ( $in ) ORDER BY u.`user_id` ASC", $user_ids)->fetch_all();
		
		$formatted_list = [0 => [0]]; // guest user/group
		
		foreach ($group_list as $group)
		{
			$formatted_list[$group['user_id']][] = $group['group_id'];
		}
		
		return $formatted_list;
	}
	
	// helper function to get the data needed for sorting user_badges in the function below this one 
	public function grab_user_groups()
	{
		$get_groups = unserialize(core::$redis->get('user_group_list'));
		if ($get_groups === false || $get_groups === null) // there's no cache
		{
			$groups_query = $this->db->run("SELECT `group_id`, `group_name`, `show_badge`, `badge_text`, `badge_colour` FROM `user_groups` ORDER BY `group_name` ASC")->fetch_all(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);		
			core::$redis->set('user_group_list', serialize($groups_query));
		}
		self::$user_group_list = $get_groups;
	}
	
	// the actual user badge sorting, which gives the expected output of user badges for comments, forum posts etc
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
		if (isset($data['user_groups']))
		{
			foreach ($data['user_groups'] as $group)
			{
				if (array_key_exists($group, self::$user_group_list) && self::$user_group_list[$group]['show_badge'] == 1)
				{
					$text = '<span class="badge '.self::$user_group_list[$group]['badge_colour'].'">'.self::$user_group_list[$group]['badge_text'].'</span>';
					if ($list == 1)
					{
						$text = '<li>'.$text.'</li>';
					}
					$badges[] = $text;
				}
			}
		}
		return $badges;
	}

	function block_user($user_id)
	{
		// get their username
		$username = $this->db->run("SELECT `username` FROM `users` WHERE `user_id` = ?", array($user_id))->fetchOne();
		
		// check they're not on the list already
		$check = $this->db->run("SELECT `blocked_id` FROM `user_block_list` WHERE `user_id` = ? AND `blocked_id` = ?", array($_SESSION['user_id'], $user_id))->fetchOne();
		if ($check)
		{
			$_SESSION['message'] = 'already_blocked';
			$_SESSION['message_extra'] = $username;
			
			header("Location: /usercp.php?module=block_list");
			die();
		}
		else
		{
			// add them to the block block list 
			$this->db->run("INSERT INTO `user_block_list` SET `user_id` = ?, `blocked_id` = ?", array($_SESSION['user_id'], $user_id));

			$_SESSION['message'] = 'blocked';
			$_SESSION['message_extra'] = $username;
			
			header("Location: /usercp.php?module=block_list");
			die();
		}		
	}

	function unblock_user($user_id)
	{
		// get their username
		$username = $this->db->run("SELECT `username` FROM `users` WHERE `user_id` = ?", array($user_id))->fetchOne();
		
		$this->db->run("DELETE FROM `user_block_list` WHERE `user_id` = ? AND `blocked_id` = ?", array($_SESSION['user_id'], $user_id));
		
		$_SESSION['message'] = 'unblocked';
		$_SESSION['message_extra'] = $username;
			
		header("Location: /usercp.php?module=block_list");
		die();
	}

	function delete_user($user_id)
	{
		// remove any old avatar if one was uploaded
		$deleted_info = $this->db->run("SELECT `avatar`, `avatar_uploaded`, `avatar_gravatar`, `username` FROM `users` WHERE `user_id` = ?", array($user_id))->fetch();

		if ($deleted_info['avatar_uploaded'] == 1)
		{
			unlink('uploads/avatars/' . $deleted_info['avatar']);
		}

		$this->db->run("DELETE FROM `users` WHERE `user_id` = ?", array($user_id));
		$this->db->run("DELETE FROM `user_profile_info` WHERE `user_id` = ?", array($user_id));
		$this->db->run("DELETE FROM `forum_topics_subscriptions` WHERE `user_id` = ?", array($user_id));
		$this->db->run("DELETE FROM `articles_subscriptions` WHERE `user_id` = ?", array($user_id));
		$this->db->run("DELETE FROM `user_conversations_info` WHERE `owner_id` = ?", array($user_id));
		$this->db->run("DELETE FROM `user_conversations_participants` WHERE `participant_id` = ?", array($user_id));
		$this->db->run("DELETE FROM `user_notifications` WHERE `owner_id` = ?", array($user_id));
		$this->db->run("UPDATE `articles_comments` SET `author_id` = 0 WHERE `author_id` = ?", array($user_id));
		$this->db->run("UPDATE `forum_topics` SET `author_id` = 0 WHERE `author_id` = ?", array($user_id));
		$this->db->run("UPDATE `forum_replies` SET `author_id` = 0 WHERE `author_id` = ?", array($user_id));
		$this->db->run("UPDATE `config` SET `data_value` = (data_value - 1) WHERE `data_key` = 'total_users'");
		$this->db->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = 'delete_user', `data` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], $deleted_info['username'], core::$date, core::$date));
	}
}

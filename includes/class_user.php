<?php
class user
{
	// the required database connection
	private $database;
	// the required core class
	private $core;
	// cache stored user information grabbed from the database, built up as the script runs
	public $user_details = [];
	
	public static $user_group_list;

	public static $user_sql_fields = "`user_id`, `single_article_page`, `per-page`,
	`articles-per-page`, `username`, `user_group`, `secondary_user_group`,
	`banned`, `theme`, `activated`, `in_mod_queue`, `email`, `login_emails`,
	`forum_type`, `avatar`, `avatar_uploaded`, `avatar_gravatar`, `gravatar_email`, `avatar_gallery`,
	`display_comment_alerts`, `email_options`, `auto_subscribe`, `auto_subscribe_email`, `distro`, `timezone`";
	
	function __construct($database, $core)
	{
		$this->database = $database;
		$this->core = $core;
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
			$session_exists = $this->database->run("SELECT `user_id` FROM ".$this->core->db_tables['session']." WHERE `user_id` = ?", [$safe_id])->fetch();
			if (!$session_exists)
			{
				$logout = 1;
			}
			
			$this->check_banned($_SESSION['user_id']);
		}
		else
		{
			if (isset($_COOKIE['gol_stay']) && isset($_COOKIE['gol_session']) && isset($_COOKIE['gol-device']) && $user->stay_logged_in() == true)
			{
				header("Location: " . $_SERVER['REQUEST_URI']);
			}
			$this->make_guest_session();
		}

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
			$info = $this->database->run("SELECT `password` FROM ".$this->core->db_tables['users']." WHERE (`username` = ? OR `email` = ?)", [$username, $username])->fetch();
			if ($info)
			{
				// now check password matches
				if (password_verify($password, $info['password']))
				{
					$user_info = $this->database->run("SELECT ".$this::$user_sql_fields." FROM ".$this->core->db_tables['users']." WHERE (`username` = ? OR `email` = ?)", [$username, $username])->fetch();

					$this->check_banned($user_info['user_id']);

					$generated_session = md5(mt_rand() . $user_info['user_id'] . $_SERVER['HTTP_USER_AGENT']);

					// update IP address and last login
					$db->sqlquery("UPDATE `".$this->database->table_prefix."users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $user_info['user_id']));

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

	public function check_banned($user_id)
	{
		$banned = 0;

		// now check IP ban
		$check_ip = $this->database->run("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", [core::$ip])->fetch();
		if ($check_ip)
		{
			$banned = 1;
		}

		$banning_check = $this->database->run("SELECT `banned` FROM ".$this->core->db_tables['users']." WHERE `user_id` = ?", [$user_id])->fetchOne();
			
		if ($banning_check == 1)
		{
			$banned = 1;
		}

		if ($banned == 1)
		{
			// update their ip in the user table
			$this->database->run("UPDATE ".$this->core->db_tables['users']." SET `ip` = ? WHERE `user_id` = ?", [core::$ip, $user_id]);

			// search the ip list, if it's not on it then add it in
			$search_ips = $this->database->run("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", [core::$ip])->fetch();
			if (!$search_ips)
			{
				$this->database->run("INSERT INTO `ipbans` SET `ip` = ?", [core::$ip]);
			}
			
			$this->logout(1);
		}
	}
	
	// need to implement this and do checks to only use it if not logging out or banned etc
	function make_guest_session()
	{
		$_SESSION['user_id'] = 0;
		$_SESSION['username'] = 'Guest'; // not even sure why I set this
		$_SESSION['user_group'] = 4;
		$_SESSION['secondary_user_group'] = 4;
		$_SESSION['per-page'] = $this->core->config('default-comments-per-page');
		$_SESSION['articles-per-page'] = 15;
		$_SESSION['single_article_page'] = 0;
	}
	
	// helper func to get a user field(s)
	function get($fields, $user_id)
	{
		if (is_array($fields))
		{
			$to_return = [];
			foreach ($fields as $field)
			{
				if (isset($this->user_details[$user_id]) && in_array($field, $this->user_details[$user_id]))
				{
					$to_return[$field] = $this->user_details[$user_id][$field];
				}
				else
				{
					$grab_fields[] = "`" . $field . "`";
				}
			}
			if (!empty($grab_fields))
			{
				$get_fields = implode(',', $grab_fields);

				$sql = "SELECT ".$get_fields." FROM ".$this->core->db_tables['users']." WHERE `user_id` = ?";
				$grabber = $this->database->run($sql, [$user_id])->fetch();
				foreach ($grabber as $field => $put)
				{
					$to_return[$field] = $put;
					$this->user_details[$user_id][$field] = $put;
				}
			}
			return $to_return;
		}
		else
		{
			// if we already have it in the cache
			if (isset($this->user_details[$user_id][$fields]))
			{
				return $this->user_details[$user_id][$fields];
			}
			else
			{
				$get_fields = "`" . $fields . "`";

				$sql = "SELECT ".$get_fields." FROM ".$this->core->db_tables['users']." WHERE `user_id` = ?";
				$picked_fields = $this->database->run($sql, [$user_id])->fetch();
				
				// set the cache
				$this->user_details[$user_id][$fields] = $picked_fields[$fields];
				
				// return the details
				return $picked_fields;
			}
		}
	}
	
	function user_timezone($user_id)
	{
		if (!isset($user_id) || $user_id == 0)
		{
			return 'UTC';
		}
		else
		{
			return $this->get('timezone', $user_id)['timezone'];
		}
	}

	public function register_session($user_data)
	{
		session_regenerate_id(true);
		
		$_SESSION['user_id'] = $user_data['user_id'];
		$_SESSION['username'] = $user_data['username'];
		$_SESSION['user_group'] = $user_data['user_group'];
		$_SESSION['secondary_user_group'] = $user_data['secondary_user_group'];
		$_SESSION['new_login'] = 1;
		$_SESSION['activated'] = $user_data['activated'];
		$_SESSION['per-page'] = $user_data['per-page'];
		$_SESSION['articles-per-page'] = $user_data['articles-per-page'];
		$_SESSION['single_article_page'] = $user_data['single_article_page'];
		$_SESSION['display_comment_alerts'] = $user_data['display_comment_alerts'];
		$_SESSION['email_options'] = $user_data['email_options'];
		$_SESSION['auto_subscribe'] = $user_data['auto_subscribe'];
		$_SESSION['auto_subscribe_email'] = $user_data['auto_subscribe_email'];
	}

	// check if it's a new device, then set the session up
	public function new_login($user_data, $generated_session)
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
			$db->sqlquery("SELECT `device-id` FROM ".$this->core->db_tables['session']." WHERE `user_id` = ? AND `device-id` = ?", array($user_data['user_id'], $_COOKIE['gol-device']));
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
		$db->sqlquery("INSERT INTO ".$this->core->db_tables['session']." SET `user_id` = ?, `session_id` = ?, `browser_agent` = ?, `device-id` = ?, `date` = ?", array($user_data['user_id'], $generated_session, $user_agent, $device_id, date("Y-m-d")));

		$this->register_session($user_data);
	}

	// if they have a stay logged in cookie log them in!
	function stay_logged_in()
	{
		global $db, $core;

		$db->sqlquery("SELECT `session_id` FROM ".$this->core->db_tables['session']." WHERE `user_id` = ? AND `session_id` = ? AND `device-id` = ?", array($_COOKIE['gol_stay'], $_COOKIE['gol_session'], $_COOKIE['gol-device']));
		$session = $db->fetch();

		if ($db->num_rows() == 1)
		{
			// login then
			$db->sqlquery("SELECT ".$this::$user_sql_fields." FROM ".$this->core->db_tables['users']." WHERE `user_id` = ?", array($_COOKIE['gol_stay']));
			$user_data = $db->fetch();
			
			$this->check_banned($user_data['user_id']);

			// update IP address and last login
			$db->sqlquery("UPDATE `".$this->database->table_prefix."users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $user_data['user_id']));

			$this->register_session($user_data);

			return true;
		}

		else
		{
			return false;
		}
	}

	function logout($banned = 0)
	{
		if (isset($_COOKIE['gol-device']))
		{
			$this->database->run("DELETE FROM ".$this->core->db_tables['session']." WHERE `user_id` = ? AND `device-id` = ?", [$_SESSION['user_id'], $_COOKIE['gol-device']]);
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
		
		header("Location: ".core::config('website_url'));
		die();
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

	public function sort_avatar($user_id)
	{
		$user_data = $this->get(['avatar', 'avatar_gravatar', 'gravatar_email', 'avatar_gallery', 'avatar_uploaded', 'theme'], $user_id);
		
		if ($user_data['theme'] == 'dark')
		{
			$default_avatar = $this->core->config('website_url') . "uploads/avatars/no_avatar_dark.png";
		}
		else if ($user_data['theme'] == 'default')
		{
			$default_avatar = $this->core->config('website_url') . "uploads/avatars/no_avatar.png";
		}
			
		$avatar = '';
		if ($user_data['avatar_gravatar'] == 1)
		{
			$avatar = 'https://www.gravatar.com/avatar/' . md5( strtolower( trim( $user_data['gravatar_email'] ) ) ) . '?d='. $default_avatar;
		}

		else if ($user_data['avatar_gallery'] != NULL)
		{
			$avatar = $this->core->config('website_url') . "uploads/avatars/gallery/{$user_data['avatar_gallery']}.png";
		}

		// either uploaded or linked an avatar
		else if (!empty($user_data['avatar']) && $user_data['avatar_gravatar'] == 0)
		{
			$avatar = $user_data['avatar'];
			if ($user_data['avatar_uploaded'] == 1)
			{
				$avatar = $this->core->config('website_url') . "uploads/avatars/{$user_data['avatar']}";
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

	public function display_pc_info($user_id)
	{
		global $db;

		$pc_info = [];

		$counter = 0;

		$additionaldb = $db->sqlquery("SELECT
			p.`desktop_environment`,
			p.`what_bits`,
			p.`cpu_vendor`,
			p.`cpu_model`,
			p.`gpu_vendor`,
			p.`gpu_model`,
			p.`gpu_driver`,
			p.`ram_count`,
			p.`monitor_count`,
			p.`gaming_machine_type`,
			p.`resolution`,
			p.`dual_boot`,
			p.`gamepad`,
			p.`date_updated`,
			u.`distro`
			FROM
			".$this->core->db_tables['profile_info']." p
			INNER JOIN
			".$this->core->db_tables['users']." u ON u.user_id = p.user_id
			WHERE
			p.`user_id` = ?", array($user_id))->fetch();


		if (!empty($get_info['distro']) && $get_info['distro'] != 'Not Listed')
		{
			$counter++;
			$pc_info['distro'] = "<strong>Distribution:</strong> <img class=\"distro\" height=\"20px\" width=\"20px\" src=\"/templates/default/images/distros/{$distribution}.svg\" alt=\"{$distribution}\" /> {$distribution}";
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
	
	public function grab_user_groups()
	{
		global $db;
		
		$db->sqlquery("SELECT `group_id`, `group_name`, `show_badge`, `badge_text`, `badge_colour` FROM ".$this->core->db_tables['user_groups']." ORDER BY `group_name` ASC");
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

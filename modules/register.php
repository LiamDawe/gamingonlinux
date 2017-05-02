<?php

$core->check_ip_from_stopforumspam(core::$ip);

$templating->set_previous('title', 'Register', 1);
$templating->set_previous('meta_description', 'GamingOnLinux.com register page', 1);

if (core::config('pretty_urls') == 1)
{
	$redirect = '/register/';
}
else
{
	$redirect = '/index.php?module=register&';
}

require_once("includes/curl_data.php");

$templating->merge('register');

if (core::config('allow_registrations') == 1)
{
	if (core::config('captcha_disabled') == 0 && core::config('register_captcha') == 1)
	{
		$captcha = '<strong>You must do a captcha to register</strong><br />If you don\'t see a captcha below, then <strong>please allow google reCAPTCHA in your privacy plugins</strong>. <div class="g-recaptcha" data-sitekey="'.core::config('recaptcha_public').'"></div>';
	}

	else
	{
		$captcha = '';
	}

	if (!isset($_POST['register']) && !isset($_GET['twitter_new']) && !isset($_GET['steam_new']) && !isset($_GET['google_new']))
	{
		$templating->block('main');

		$templating->set('rules', core::config('rules'));
		$templating->set('captcha', $captcha);
		$templating->set('timezone_list', core::timezone_list());

		// set time to check against registration time to prevent really fast bots
		$_SESSION['register_time'] = time();
	}

	else if (!isset($_POST['register']) && isset($_GET['twitter_new']))
	{
		$templating->block('twitter_new');

		$templating->set('rules', core::config('rules'));
		$templating->set('captcha', $captcha);
		$templating->set('timezone_list', core::timezone_list());

		// set time to check against registration time to prevent really fast bots
		$_SESSION['register_time'] = time();
	}

	else if (!isset($_POST['register']) && isset($_GET['steam_new']))
	{
		$templating->block('steam_new');

		$templating->set('username', $_SESSION['steam_username']);

		$templating->set('rules', core::config('rules'));
		$templating->set('captcha', $captcha);
		$templating->set('timezone_list', core::timezone_list());

		// set time to check against registration time to prevent really fast bots
		$_SESSION['register_time'] = time();
	}
	
	else if (!isset($_POST['register']) && isset($_GET['google_new']))
	{
		$templating->block('google_new');
		
		$templating->set('name', $_SESSION['google_name']);
		$templating->set('email', $_SESSION['google_data']['google_email']);

		$templating->set('rules', core::config('rules'));
		$templating->set('captcha', $captcha);
		$templating->set('timezone_list', core::timezone_list());

		// set time to check against registration time to prevent really fast bots
		$_SESSION['register_time'] = time();
	}

	else if (isset($_POST['register']))
	{
		// disallow certain username characters
		$aValid = array('-', '_');

		if(!ctype_alnum(str_replace($aValid, '', $_POST['username'])))
		{
			$_SESSION['message'] = 'username_characters';
			header("Location: ".$redirect);
			die();
		}

		// they must have a password for normal registrations
		if ($_POST['register'] == 'Register')
		{
			if (empty($_POST['password']))
			{
				$_SESSION['message'] = 'empty';
				$_SESSION['message_extra'] = 'password';
				header("Location: ".$redirect);
				die();
			}
			
			// check passwords match
			if ($_POST['password'] != $_POST['verify_password'])
			{
				$_SESSION['message'] = 'password_match';
				header("Location: ".$redirect);
				die();
			}
			$safe_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
		}
		
		$username = $_POST['username'];
		$email = $_POST['uemail'];
		
		$check_empty = core::mempty(compact('username', 'email'));
		
		// all registrations need a username and email
		if ($check_empty !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $check_empty;
			header("Location: ".$redirect);
			die();
		}

		// check ip bans
		$db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array(core::$ip));
		if ($db->num_rows() == 1)
		{
			$core->message("You are banned!", NULL, 1);
		}

		else
		{
			if (core::config('captcha_disabled') == 0 && core::config('register_captcha') == 1)
			{
				$recaptcha=$_POST['g-recaptcha-response'];
				$google_url="https://www.google.com/recaptcha/api/siteverify";
				$ip=core::$ip;
				$url=$google_url."?secret=".core::config('recaptcha_secret')."&response=".$recaptcha."&remoteip=".$ip;
				$res=getCurlData($url);
				$res= json_decode($res, true);
			}

			if (core::config('captcha_disabled') == 1 || (core::config('captcha_disabled') == 0 && (core::config('register_captcha') == 1 && $res['success']) || core::config('register_captcha') == 0))
			{
				// check username isnt taken
				$db->sqlquery("SELECT `username` FROM ".$core->db_tables['users']." WHERE `username` = ?", array($_POST['username']));
				if ($db->fetch())
				{
					$_SESSION['message'] = 'username_taken';
					header("Location: ".$redirect);
					die();
				}

				// dont allow dupe emails
				$db->sqlquery("SELECT `email` FROM ".$core->db_tables['users']." WHERE `email` = ?", array($_POST['uemail']));
				if ($db->fetch())
				{
					$_SESSION['message'] = 'email_taken';
					
					if (core::config('pretty_urls') == 1)
					{
						
						header("Location: ".$redirect);
					}
					else
					{
						header("Location: ".$redirect);
					}
					die();
				}
				
				// get the session register time plus 2 seconds, if it's under that it was too fast and done by a bot
				$register_time = $_SESSION['register_time'] + 2;

				// anti-spam, if a bot auto fills this hidden field don't register them, but say you did
				if (empty($_POST['email']) && time() > $register_time)
				{
					// make random registration code for activating the account
					$code = sha1(mt_rand(10000,99999).time().$_POST['uemail']);
					
					$email = trim($_POST['uemail']);

					// register away
					if ($_POST['register'] == 'Register')
					{
						$db->sqlquery("INSERT INTO ".$core->db_tables['users']." SET `username` = ?, `password` = ?, `email` = ?, `gravatar_email` = ?, `ip` = ?, `register_date` = ?, `last_login` = ?, `theme` = 'default', `activation_code` = ?, `timezone` = ?", array($_POST['username'], $safe_password, $email, $email, core::$ip, core::$date, core::$date, $code, $_POST['timezone']));
					}

					if ($_POST['register'] == 'twitter')
					{
						$db->sqlquery("INSERT INTO ".$core->db_tables['users']." SET `username` = ?, `email` = ?, `gravatar_email` = ?, `ip` = ?, `register_date` = ?, `last_login` = ?, `theme` = 'default', `oauth_provider` = ?, `oauth_uid` = ?, `twitter_username` = ?, `activation_code` = ?, `timezone` = ?", array($_POST['username'], $email, $email, core::$ip, core::$date, core::$date, $_SESSION['twitter_data']['oauth_provider'], $_SESSION['twitter_data']['uid'], $_SESSION['twitter_data']['twitter_username'], $code, $_POST['timezone']));
					}

					if ($_POST['register'] == 'steam')
					{
						$db->sqlquery("INSERT INTO ".$core->db_tables['users']." SET `username` = ?, `password` = ?, `email` = ?, `gravatar_email` = ?, `ip` = ?, `register_date` = ?, `last_login` = ?, `theme` = 'default', `steam_id` = ?, `steam_username` = ?, `activation_code` = ?, `timezone` = ?", array($_POST['username'], $safe_password, $email, $email, core::$ip, core::$date, core::$date, $_SESSION['steam_id'], $_SESSION['steam_username'], $code, $_POST['timezone']));
					}
					
					if ($_POST['register'] == 'google')
					{
						$db->sqlquery("INSERT INTO ".$core->db_tables['users']." SET `username` = ?, `email` = ?, `gravatar_email` = ?, `avatar` = ?, `ip` = ?, `register_date` = ?, `last_login` = ?, `theme` = 'default', `google_id` = ?, `google_email` = ?, `activation_code` = ?, `timezone` = ?", array($_POST['username'], $email, $email, $_SESSION['google_avatar'], core::$ip, core::$date, core::$date, $_SESSION['google_data']['google_id'], $_SESSION['google_data']['google_email'], $code, $_POST['timezone']));
					}

					$last_id = $db->grab_id();
					
					$dbl->run("INSERT INTO ".$core->db_tables['user_group_membership']." SET `user_id` = ?, `group_id` = ?", [$last_id, 3]);

					$db->sqlquery("INSERT INTO ".$core->db_tables['user_profile_info']." SET `user_id` = ?", array($last_id));

					// add one to members count, this is a special case
					// it's one of only a few times we would update a remote config table, rather than local if we are using their user database
					$config_table = '`'.$this->database->table_prefix.'config`';
					if ($this->config('local_users') == 0)
					{
						$config_table = $this->config('remote_users_database') . '.' . '`config`';
					}
					$db->sqlquery("UPDATE $config_table SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_users'");

					// get the users info to log them in right away!
					$db->sqlquery("SELECT ".$user::$user_sql_fields." FROM ".$core->db_tables['users']." WHERE `user_id` = ?", array($last_id));
					$new_user_info = $db->fetch();
					
					$generated_session = md5(mt_rand() . $last_id . $_SERVER['HTTP_USER_AGENT']);
					
					user::new_login($new_user_info, $generated_session);

					// subject
					$subject = 'Welcome to '.core::config('site_title').', activation needed!';

					// message
					$html_message = '<p>Hello '.$_POST['username'].',</p>
					<p>Thanks for registering on <a href="'.core::config('website_url').'" target="_blank">'.core::config('site_title').'</a>!</p>
					<p><strong><a href="'.core::config('website_url').'index.php?module=activate_user&user_id='.$last_id.'&code='.$code.'">You need to activate your account before you can post! Click here to activate!</a></strong></p>
					<p>If you&#39;re new, consider saying hello in the <a href="'.core::config('website_url').'forum/" target="_blank">forum</a>.</p>';

					$plain_message = 'Hello '.$_POST['username'].', Thanks for registering on '.core::config('site_title').'. You need to activate your account before you can post! Go here to activate: '.core::config('website_url').'index.php?module=activate_user&user_id='.$last_id.'&code='.$code;

					$mail = new mail($_POST['uemail'], $subject, $html_message, $plain_message);
					$mail->send();

					$_SESSION['message'] = 'new_account';
					header("Location: ". $core->config('website_url'));
				}
			}
			// Check the score to determine what to do.
			else if (core::config('captcha_disabled') == 0 && core::config('register_captcha') == 1 && !$res['success'])
			{
				// Add code to process the form.
				$core->message("You need to complete the captcha to prove you are human and not a bot! <a href=\"index.php?module=register\">Click here to try again</a>.", NULL, 1);
			}
		}
	}
}

else
{
	$core->message(core::config('register_off_message'));
}

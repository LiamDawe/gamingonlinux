<?php
$templating->set_previous('title', 'Register', 1);
$templating->set_previous('meta_description', 'GamingOnLinux.com register page', 1);

$redirect = '/register/';

require_once("includes/curl_data.php");

$templating->load('register');

if ($core->config('allow_registrations') == 1)
{
	if ($core->config('captcha_disabled') == 0 && $core->config('register_captcha') == 1)
	{
		$captcha = '<strong>You must do a captcha to register</strong><br />
		We use Google\'s reCAPTCHA, you must agree to their use of cookies to use it. This is to help us prevent spam!
		<button id="accept_captcha" type="button" data-pub-key="'.$core->config('recaptcha_public').'">Accept & Show reCAPTCHA</button>';
	}

	else
	{
		$captcha = '';
	}

	if (!isset($_POST['register']) && !isset($_GET['twitter_new']) && !isset($_GET['steam_new']) && !isset($_GET['google_new']))
	{
		$templating->block('main');

		$templating->set('rules', $core->config('rules'));
		$templating->set('captcha', $captcha);
		$templating->set('timezone_list', core::timezone_list());

		// set time to check against registration time to prevent really fast bots
		$_SESSION['register_time'] = time();
	}

	else if (!isset($_POST['register']) && isset($_GET['twitter_new']))
	{
		$templating->block('twitter_new');

		$templating->set('rules', $core->config('rules'));
		$templating->set('captcha', $captcha);
		$templating->set('timezone_list', core::timezone_list());

		// set time to check against registration time to prevent really fast bots
		$_SESSION['register_time'] = time();
	}

	else if (!isset($_POST['register']) && isset($_GET['steam_new']))
	{
		if (isset($_SESSION['steam_username']))
		{
			$templating->block('steam_new');

			$templating->set('username', $_SESSION['steam_username']);

			$templating->set('rules', $core->config('rules'));
			$templating->set('captcha', $captcha);
			$templating->set('timezone_list', core::timezone_list());

			// set time to check against registration time to prevent really fast bots
			$_SESSION['register_time'] = time();
		}
		else
		{
			header("Location: /index.php?module=login");
			die();
		}
	}
	
	else if (!isset($_POST['register']) && isset($_GET['google_new']))
	{
		$templating->block('google_new');
		
		$templating->set('name', $_SESSION['google_name']);
		$templating->set('email', $_SESSION['google_data']['google_email']);

		$templating->set('rules', $core->config('rules'));
		$templating->set('captcha', $captcha);
		$templating->set('timezone_list', core::timezone_list());

		// set time to check against registration time to prevent really fast bots
		$_SESSION['register_time'] = time();
	}

	else if (isset($_POST['register']))
	{
		// check they agreed to the privacy policy/terms
		if (!isset($_POST['policy_agree']))
		{
			$_SESSION['message'] = 'policy_agree';
			header("Location: ".$redirect);
			die();			
		}

		if (!isset($_POST['spam_list']))
		{
			$_SESSION['message'] = 'spam_check_agree';
			header("Location: ".$redirect);
			die();	
		}

		$core->check_ip_from_stopforumspam(core::$ip);

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

		if ($core->config('captcha_disabled') == 0 && $core->config('register_captcha') == 1)
		{
			if (!isset($_POST['g-recaptcha-response']))
			{
				$_SESSION['message'] = 'captcha_nope';
				header("Location: /index.php?module=register");
				die();
			}
			$recaptcha = $_POST['g-recaptcha-response'];
			$google_url = "https://www.google.com/recaptcha/api/siteverify";
			$ip = core::$ip;
			$url = $google_url."?secret=".$core->config('recaptcha_secret')."&response=".$recaptcha."&remoteip=".$ip;
			$res = getCurlData($url);
			$res = json_decode($res, true);
		}

		if ($core->config('captcha_disabled') == 1 || ($core->config('captcha_disabled') == 0 && ($core->config('register_captcha') == 1 && $res['success']) || $core->config('register_captcha') == 0))
		{
			// check username isnt taken
			$username_test = $dbl->run("SELECT `username` FROM `users` WHERE `username` = ?", array($_POST['username']))->fetchOne();
			if ($username_test)
			{
				$_SESSION['message'] = 'username_taken';
				header("Location: ".$redirect);
				die();
			}

			// dont allow dupe emails
			$email_test = $dbl->run("SELECT `email` FROM `users` WHERE `email` = ?", array($_POST['uemail']))->fetch();
			if ($email_test)
			{
				$_SESSION['message'] = 'email_taken';

				header("Location: ".$redirect);
				die();
			}
				
			// get the session register time plus 2 seconds, if it's under that it was too fast and done by a bot
			$register_time = $_SESSION['register_time'] + 2;

			// anti-spam
			if (time() > $register_time)
			{
				// make random registration code for activating the account
				$code = sha1(mt_rand(10000,99999).time().$_POST['uemail']);
					
				$email = trim($_POST['uemail']);

				// i don't know why (possibly plugins blocking it), but sometimes we just don't get a timezone
				$timezone = 'UTC';
				if (isset($_POST['timezone']) && !empty($_POST['timezone']))
				{
					$timezone = $_POST['timezone'];
				}

				// register away
				if ($_POST['register'] == 'Register')
				{
					$do_register = $dbl->run("INSERT INTO `users` SET `username` = ?, `password` = ?, `email` = ?, `ip` = ?, `register_date` = ?, `last_login` = ?, `theme` = 'default', `activation_code` = ?, `timezone` = ?", array($_POST['username'], $safe_password, $email, core::$ip, core::$date, core::$date, $code, $timezone));
				}

				if ($_POST['register'] == 'twitter')
				{
					$do_register = $dbl->run("INSERT INTO `users` SET `username` = ?, `email` = ?, `ip` = ?, `register_date` = ?, `last_login` = ?, `theme` = 'default', `oauth_provider` = ?, `oauth_uid` = ?, `twitter_username` = ?, `activation_code` = ?, `timezone` = ?", array($_POST['username'], $email, core::$ip, core::$date, core::$date, $_SESSION['twitter_data']['oauth_provider'], $_SESSION['twitter_data']['uid'], $_SESSION['twitter_data']['twitter_username'], $code, $timezone));
				}

				if ($_POST['register'] == 'steam')
				{
					$do_register = $dbl->run("INSERT INTO `users` SET `username` = ?, `email` = ?, `ip` = ?, `register_date` = ?, `last_login` = ?, `theme` = 'default', `steam_id` = ?, `steam_username` = ?, `activation_code` = ?, `timezone` = ?", array($_POST['username'], $email, core::$ip, core::$date, core::$date, $_SESSION['steam_id'], $_SESSION['steam_username'], $code, $timezone));
				}
					
				if ($_POST['register'] == 'google')
				{
					$do_register = $dbl->run("INSERT INTO `users` SET `username` = ?, `email` = ?, `ip` = ?, `register_date` = ?, `last_login` = ?, `theme` = 'default', `google_email` = ?, `activation_code` = ?, `timezone` = ?", array($_POST['username'], $email, core::$ip, core::$date, core::$date, $_SESSION['google_data']['google_email'], $code, $timezone));
				}

				$last_id = $dbl->new_id();

				$dbl->run("INSERT INTO `user_profile_info` SET `user_id` = ?", array($last_id));
					
				$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = ?", [$last_id, 3]);

				$dbl->run("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_users'");

				// get the users info to log them in right away!
				$user->user_details = $dbl->run("SELECT ".$user::$user_sql_fields." FROM `users` WHERE `user_id` = ?", array($last_id))->fetch();
					
				$generated_session = md5(mt_rand() . $last_id . $_SERVER['HTTP_USER_AGENT']);
					
				$user->new_login($generated_session);

				// subject
				$subject = 'Welcome to GamingOnLinux, activation needed!';

				// message
				$html_message = '<p>Hello '.$_POST['username'].',</p>
				<p>Thanks for registering on <a href="'.$core->config('website_url').'" target="_blank">GamingOnLinux</a>!</p>
				<p><strong><a href="'.$core->config('website_url').'index.php?module=activate_user&user_id='.$last_id.'&code='.$code.'">You need to activate your account before you can post! Click here to activate!</a></strong></p>
				<p>If you&#39;re new, consider saying hello in the <a href="'.$core->config('website_url').'forum/" target="_blank">forum</a>.</p>';

				$plain_message = 'Hello '.$_POST['username'].', Thanks for registering on GamingOnLinux. You need to activate your account before you can post! Go here to activate: '.$core->config('website_url').'index.php?module=activate_user&user_id='.$last_id.'&code='.$code;

				$mail = new mailer($core);
				$mail->sendMail($_POST['uemail'], $subject, $html_message, $plain_message);

				$_SESSION['message'] = 'new_account';
				$_SESSION['message_extra'] = $_POST['username'];
				header("Location: ". $core->config('website_url'));
			}
		}
		// Check the score to determine what to do.
		else if ($core->config('captcha_disabled') == 0 && $core->config('register_captcha') == 1 && !$res['success'])
		{
			$_SESSION['message'] = 'captcha_nope';
			header("Location: /index.php?module=register");
			die();
		}
	}
}

else
{
	$core->message($core->config('register_off_message'));
}

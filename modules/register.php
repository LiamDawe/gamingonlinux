<?php
$data = file_get_contents("http://api.stopforumspam.org/api?ip=" . $core->ip);
if (strpos($data, "<appears>yes</appears>") !== false)
{
	header('Location: /index.php?module=home&message=spam');
	die();
}

$templating->set_previous('title', 'Register', 1);
$templating->set_previous('meta_description', 'GamingOnLinux.com register page', 1);

require_once("includes/curl_data.php");

$templating->merge('register');

if (core::config('allow_registrations') == 1)
{
	if (core::config('register_captcha') == 1)
	{
		$captcha = '<strong>You must do a captcha to register</strong><br />If you don\'t see a captcha below, then <strong>please allow google repatcha in your privacy plugins</strong>. <div class="g-recaptcha" data-sitekey="6Les6RYTAAAAAGZVgAdkXbPQ7U8AuyqrWrHVbVq4"></div>';
	}

	else
	{
		$captcha = '';
	}

	if (!isset($_POST['register']) && !isset($_GET['twitter_new']) && !isset($_GET['steam_new']))
	{
		$templating->block('main');


		$templating->set('rules', core::config('rules'));
		$templating->set('captcha', $captcha);

		// set time to check against registration time to prevent really fast bots
		$_SESSION['register_time'] = time();
	}

	else if (!isset($_POST['register']) && isset($_GET['twitter_new']))
	{
		$templating->block('twitter_new');

		$templating->set('rules', core::config('rules'));
		$templating->set('captcha', $captcha);

		// set time to check against registration time to prevent really fast bots
		$_SESSION['register_time'] = time();
	}

	else if (!isset($_POST['register']) && isset($_GET['steam_new']))
	{
		$templating->block('steam_new');

		$templating->set('username', $_SESSION['steam_username']);

		$templating->set('rules', core::config('rules'));
		$templating->set('captcha', $captcha);

		// set time to check against registration time to prevent really fast bots
		$_SESSION['register_time'] = time();
	}

	else if (isset($_POST['register']))
	{
		// make them safe and sort the password
		$salt = $user->salt();
		$username =  htmlspecialchars($_POST['username']);
		$safe_password = hash('sha256', $salt.$_POST['password']);

		// check ip bans
		$db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `ip` = ?", array($core->ip));
		if ($db->num_rows() == 1)
		{
			$core->message("You are banned!", NULL, 1);
		}

		else
		{
			if (core::config('register_captcha') == 1)
			{
				$recaptcha=$_POST['g-recaptcha-response'];
				$google_url="https://www.google.com/recaptcha/api/siteverify";
				$ip=$core->ip;
				$url=$google_url."?secret=".core::config('recaptcha_secret')."&response=".$recaptcha."&remoteip=".$ip;
				$res=getCurlData($url);
				$res= json_decode($res, true);
			}

			if ((core::config('register_captcha') == 1 && $res['success']) || core::config('register_captcha') == 0)
			{
				// check fields are set
				if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['verify_password']) || empty($_POST['uemail']))
				{
					$core->message('You left some fields empty, you must fill in all fields when registering, <a href="index.php?module=register">click here togo back and try again!</a>', NULL, 1);
				}

				else
				{

					// check username isnt taken
					$db->sqlquery("SELECT `username` FROM `users` WHERE `username` = ?", array($username));
					if ($db->fetch())
					{
						$core->message('Sorry but that username is taken, please try another! <a href="index.php?module=register">Try again</a> or <a href="index.php">Return to home page</a>.', NULL, 1);
					}

					// check passwords match
					else if ($_POST['password'] != $_POST['verify_password'])
					{
						$core->message('Passwords did not match! <a href="index.php?module=register">Click here to go back and try again!</a>', NULL, 1);
					}

					else
					{
						// get the session register time plus 2 seconds, if it's under that it was too fast and done by a bot
						$register_time = $_SESSION['register_time'] + 2;

						// anti-spam, if a bot auto fills this hidden field don't register them, but say you did
						if (empty($_POST['email']) && time() > $register_time)
						{
							// make random registration code
							$code = sha1(mt_rand(10000,99999).time().$_POST['uemail']);

							// register away
							if ($_POST['register'] == 'Register')
							{
								$db->sqlquery("INSERT INTO `users` SET `username` = ?, `password` = ?, `password_salt` = ?, `email` = ?, `gravatar_email` = ?, `user_group` = 3, `secondary_user_group` = 3, `ip` = ?, `register_date` = ?, `last_login` = ?, `theme` = 'default', `activation_code` = ?", array($username, $safe_password, $salt, $_POST['uemail'], $_POST['uemail'], $core->ip, $core->date, $core->date, $code));
							}

							if ($_POST['register'] == 'twitter')
							{
								$db->sqlquery("INSERT INTO `users` SET `username` = ?, `password` = ?, `password_salt` = ?, `email` = ?, `gravatar_email` = ?, `user_group` = 3, `secondary_user_group` = 3, `ip` = ?, `register_date` = ?, `last_login` = ?, `theme` = 'light', `oauth_provider` = ?, `oauth_uid` = ?, `twitter_username` = ?, `activation_code` = ?", array($username, $safe_password, $salt, $_POST['uemail'], $_POST['uemail'], $core->ip, $core->date, $core->date, $_SESSION['twitter_data']['oauth_provider'], $_SESSION['twitter_data']['uid'], $_SESSION['twitter_data']['twitter_username'], $code));
							}

							if ($_POST['register'] == 'steam')
							{
								$db->sqlquery("INSERT INTO `users` SET `username` = ?, `password` = ?, `password_salt` = ?, `email` = ?, `gravatar_email` = ?, `user_group` = 3, `secondary_user_group` = 3, `ip` = ?, `register_date` = ?, `last_login` = ?, `theme` = 'light', `steam_id` = ?, `steam_username` = ?, `activation_code` = ?", array($username, $safe_password, $salt, $_POST['uemail'], $_POST['uemail'], $core->ip, $core->date, $core->date, $_SESSION['steam_id'], $_SESSION['steam_username'], $code));
							}

							$last_id = $db->grab_id();

							// add one to members count
							$db->sqlquery("UPDATE `config` SET `data_value` = (data_value + 1) WHERE `data_key` = 'total_users'");

							// get the users info to log them in right away!
							$db->sqlquery("SELECT `user_id`, `username`, `user_group` FROM `users` WHERE `user_id` = ?", array($last_id));
							$new_user_info = $db->fetch();

							$_SESSION['user_id'] = $new_user_info['user_id'];
							$_SESSION['username'] = $new_user_info['username'];
							$_SESSION['user_group'] = 3;
							$_SESSION['secondary_user_group'] = 3;
							$_SESSION['theme'] = 'light';
							$_SESSION['activated'] = 0;

							// sort out registration email
							$to  = $_POST['uemail'];

							// subject
							$subject = 'Welcome to GamingOnLinux.com, activation needed!';

							// message
							$message = '
							<html>
							<head>
							<title>Welcome email for GamingOnLinux.com, activation needed!</title>
							</head>
							<body>
							<img src="'.core::config('website_url').core::config('path').'templates/default/images/icon.png" alt="Gaming On Linux">
							<br />
							<p>Hello '.$_POST['username'].',</p>
							<p>Thanks for registering on <a href="'.core::config('website_url').core::config('path').'" target="_blank">'.core::config('website_url').core::config('path').'</a>, The best source for linux games and news.</p>
							<p><strong><a href="'.core::config('website_url').core::config('path').'index.php?module=activate_user&user_id='.$last_id.'&code='.$code.'">You need to activate your account before you can post! Click here to activate!</a></strong></p>
							<p>If you&#39;re new, consider saying hello in the <a href="'.core::config('website_url').core::config('path').'forum/" target="_blank">forum</a>.</p>
							<br style="clear:both">
							<div>
							<hr>
							<p>If you haven&#39;t registered at <a href="'.core::config('website_url').core::config('path').'" target=\"_blank\">'.core::config('website_url').core::config('path').'</a>, Forward this mail to <a href=\"mailto:'.core::config('contact_email').'" target="_blank">'.core::config('contact_email').'</a> with some info about what you want us to do about it.</p>
							<p>Please, Don&#39;t reply to this automated message, We do not read any emails recieved on this email address.</p>
							</div>
							</body>
							</html>
							';

							// To send HTML mail, the Content-type header must be set
							$headers  = 'MIME-Version: 1.0' . "\r\n";
							$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
							$headers .= "From: noreply@gamingonlinux.com\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

							// Mail it
							mail($to, $subject, $message, $headers);
						}

						$core->message("Thank you for registering {$_POST['username']}, you are now logged in, <strong>but you need to confirm you email to continue using the website properly</strong>! <a href=\"index.php\">Click here if you are not redirected.</a>", "index.php");
					}
				}
			}
			// Check the score to determine what to do.
			else if (core::config('register_captcha') == 1 && !$res['success'])
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

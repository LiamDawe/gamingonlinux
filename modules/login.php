<?php
$templating->set_previous('title', 'Login', 1);
$templating->set_previous('meta_description', 'GamingOnLinux.com login forum', 1);

$templating->merge('login');

if (!isset($_POST['action']))
{
	if (!isset($_GET['forgot']) && !isset($_GET['reset']) && !isset($_GET['twitter']) && !isset($_GET['steam']))
	{
		if ($_SESSION['user_id'] == 0)
		{
			if (isset($_GET['message']) && $_GET['message'] == 'error' && isset($_SESSION['login_error']))
			{
				$core->message($_SESSION['login_error'], NULL, 1);
			}

			$templating->block('main', 'login');

			$username = '';
			$username_remembered = '';
			if (isset($_COOKIE['remember_username']))
			{
				$username = $_COOKIE['remember_username'];
				$username_remembered = 'checked';
			}

			if (isset($_SESSION['login_error_username']))
			{
				$username = $_SESSION['login_error_username'];
			}

			$templating->set('username', $username);
			$templating->set('username_remembered', $username_remembered);
		}

		else
		{
			$core->message("You are already logged in!", NULL, 1);
		}
	}

	else if (isset($_GET['forgot']))
	{
		if (isset($_GET['bademail']))
		{
			$core->message("That is not a correct email address!", NULL, 1);
		}
		$templating->block('forgot', 'login');
	}

	else if (isset($_GET['reset']))
	{
		$email = $_GET['email'];
		$code = $_GET['code'];

		// check its a valid time
		$db->sqlquery("SELECT `expires` FROM `password_reset` WHERE `user_email` = ?", array($email));
		$get_time = $db->fetch();
		if (time() > $get_time['expires'])
		{
			// drop any previous requested
			$db->sqlquery("DELETE FROM `password_reset` WHERE `user_email` = ?", array($email));

			$core->message("That reset request has expired, you will need to <a href=\"/index.php?module=login&forgot\">request a new code!</a>");
		}

		// check code and email is valid
		else if ($db->num_rows($db->sqlquery("SELECT `user_email` FROM `password_reset` WHERE `user_email` = ? AND `secret_code` = ?", array($email, $code))) != 1)
		{
			$core->message("That is not a correct password reset request, you will need to <a href=\"/index.php?module=login&forgot\">request a new code!</a>");
		}

		else
		{
			$url_email = rawurlencode($_GET['email']);
			$templating->block('reset');
			$templating->set('code', $code);
			$templating->set('email', $url_email);
		}
	}
	
	else if (isset($_GET['steam']))
	{
		require("includes/steam/steam_login.php");
	
		$steam_user = new steam_user;
		$steam_user->apikey = "AC45D7DB8C91DFD4CC57DC107D6A866A"; // put your API key here
		$steam_user->domain = "http://www.gamingonlinux.com"; // put your domain

		// added 127 for testing on local machine so it still works
		// this checks the last page was from GOL to refer it back to where they were
		if ('www.gamingonlinux.com' == parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) || '127.0.0.1' == parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST))
		{
			if (isset($url['query']))
			{
				if ($url['query'] == 'module=login' || $url['query'] == 'module=login&message=error' || $url['query'] == 'module=register' || $url['query'] == 'module=register&twitter_new')
				{
					$return_url = "index.php";
				}

				else if ($url['query'] != 'module=login' && $url['query'] != 'module=login&message=error' && $url['query'] != 'module=register' && $url['query'] != 'module=register&twitter_new')
				{
					$return_url = $_SERVER['HTTP_REFERER'];
				}
			}

			else if (!isset($url['query']) && $url['path'] != '/index.php')
			{
				$return_url = $_SERVER['HTTP_REFERER'];
			}

			// if there is no php query page, or no url path from within gol, just go to the index
			else
			{
				$return_url = "index.php";
			}

		}

		$steam_user->return_url = $return_url;
		$steam_user->signIn();
	}
	
	else if (isset($_GET['twitter']))
	{
		require("includes/twitter/twitteroauth.php");

		$twitteroauth = new TwitterOAuth(core::config('tw_consumer_key'), core::config('tw_consumer_skey'));

		// Requesting authentication tokens, the parameter is the URL we will be redirected to
		$request_token = $twitteroauth->getRequestToken(core::config('website_url') . 'includes/twitter/getTwitterData.php');

		// Saving them into the session

		$_SESSION['oauth_token'] = $request_token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

		// If everything goes well..
		if ($twitteroauth->http_code == 200)
		{
			// Let's generate the URL and redirect
			$url = $twitteroauth->getAuthorizeURL($request_token['oauth_token']);
			header('Location: ' . $url);
		}

		else
		{
			// It's a bad idea to kill the script, but we've got to know when there's an error.
			die('Something wrong happened.');
		}
	}
}

else if (isset($_POST['action']))
{
	if ($_POST['action'] == 'Login')
	{
		$remember_name = 0;
		if (isset($_POST['remember_name']))
		{
			$remember_name = 1;
		}

		$stay = 0;
		if (isset($_POST['stay']))
		{
			$stay = 1;
		}

		if ($user->login($_POST['username'], $_POST['password'], $remember_name, $stay) == true)
		{
			unset($_SESSION['login_error']);
			unset($_SESSION['login_error_username']);
			$url = parse_url($_SERVER['HTTP_REFERER']);

			// added 127 for testing on local machine so it still works
			// this checks the last page was from GOL to refer it back to where they were
			if ('www.gamingonlinux.com' == parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) || '127.0.0.1' == parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST))
			{
				if (isset($url['query']))
				{
					if ($url['query'] == 'module=login' || $url['query'] == 'module=login&message=error' || $url['query'] == 'module=register' || $url['query'] == 'module=register&twitter_new')
					{
						header("Location: index.php");
					}

					else if ($url['query'] != 'module=login' && $url['query'] != 'module=login&message=error' && $url['query'] != 'module=register' && $url['query'] != 'module=register&twitter_new')
					{
						header("Location: {$_SERVER['HTTP_REFERER']}");
					}
				}

				else if (!isset($url['query']) && $url['path'] != '/index.php')
				{
					header("Location: {$_SERVER['HTTP_REFERER']}");
				}

				// if there is no php query page, or no url path from within gol, just go to the index
				else
				{
					header("Location: index.php");
				}

			}

			// if they aren't from within GOL, go to the index
			else
			{
				header("Location: index.php");
			}
		}

		else
		{
			$_SESSION['login_error'] = $user->message;
			$_SESSION['login_error_username'] = $_POST['username'];
			header("Location: /index.php?module=login&message=error");
		}
	}
	
	// catch anything from post and action them, then do the actual steam login
	else if ($_POST['action'] == 'steam')
	{
		// see if they need to stay logged in
		$stay = 0;
		if (isset($_POST['stay']))
		{
			$stay = 1;
		}
		setcookie('request_stay', $stay, time()+(60*60*24*7), '/', core::config('cookie_domain')); // 1 week

		header("Location: /index.php?module=login&steam");
		die();
	}
	
	// catch anything from post and action them, then do the actual twitter login
	else if ($_POST['action'] == 'twitter')
	{
		// see if they need to stay logged in
		$stay = 0;
		if (isset($_POST['stay']))
		{
			$stay = 1;
		}
		setcookie('request_stay', $stay, time()+(60*60*24*7), '/', core::config('cookie_domain')); // 1 week
		
		header("Location: /index.php?module=login&twitter");
		die();
	}

	else if ($_POST['action'] == 'Send')
	{
		// check if user exists
		$db->sqlquery("SELECT `email` FROM `users` WHERE `email` = ?", array($_POST['email']));
		if ($db->num_rows() == 0)
		{
			header("Location: /index.php?module=login&forgot&bademail");
		}

		else
		{
			$random_string = $core->random_id();

			// drop any previous requested
			$db->sqlquery("DELETE FROM `password_reset` WHERE `user_email` = ?", array($_POST['email']));

			// make expiry 7 days from now
			$next_week = time() + (7 * 24 * 60 * 60);

			// insert number to database with email
			$db->sqlquery("INSERT INTO `password_reset` SET `user_email` = ?, `secret_code` = ?, `expires` = ?", array($_POST['email'], $random_string, $next_week));

			$url_email = rawurlencode($_POST['email']);

			// send mail with link including the key
			$html_message = '<p>Someone, hopefully you, has requested to reset your password on ' . core::config('website_url') . '!</p>
			<p>If you didn\'t request this, don\'t worry! Unless someone has access to your email address it isn\'t an issue!</p>
			<p>Please click <a href="' . core::config('website_url') . 'index.php?module=login&reset&code=' . $random_string . '&email=' . $url_email . '">this link</a> to reset your password</p>';

			$plain_message = 'Someone, hopefully you, has requested to reset your password on ' . core::config('website_url') . '! Please go here: "' . core::config('website_url') . '"index.php?module=login&reset&code=' . $random_string . '&email=' . $url_email . ' to change your password. If you didn\'t request this, you can ignore it as it\'s not a problem unless anyone has access to your email!';

			// Mail it
			if (core::config('send_emails') == 1)
			{
				$mail = new mail($_POST['email'], 'GamingOnLinux.com password reset request', $html_message, $plain_message);
				$mail->send();

				$core->message("An email has been sent to {$_POST['email']} with instructions on how to change your password.");
			}
		}
	}

	// actually change the password as their code was correct and password + confirmation matched
	else if ($_POST['action'] == 'Reset')
	{
		$email = $_GET['email'];
		$code = $_GET['code'];

		// check its a valid time
		$get_time = $db->fetch($db->sqlquery("SELECT `expires` FROM `password_reset` WHERE `user_email` = ?", array($email)));
		if (time() > $get_time['expires'])
		{
			// drop any previous requested
			$db->sqlquery("DELETE FROM `password_reset` WHERE `user_email` = ?", array($email));

			$core->message("That reset request has expired, you will need to <a href=\"/index.php?module=login&forgot\">request a new code!</a>");
		}

		else
		{
			// check code and email is valid
			$db->sqlquery("SELECT `user_email` FROM `password_reset` WHERE `user_email` = ? AND `secret_code` = ?", array($email, $code));
			if ($db->num_rows() != 1)
			{
				$core->message("That is not a correct password reset request! <a href=\"index.php?module=login\">Go back.</a>");
			}

			else
			{
				// check the passwords match
				if ($_POST['password'] != $_POST['password_again'])
				{
					$core->message("The new passwords didn't match! <a href=\"index.php?module=login\">Go back.</a>");
				}

				// change the password
				else
				{
					$new_password = password_hash($_POST['password'], PASSWORD_BCRYPT);

					// new password
					$db->sqlquery("UPDATE `users` SET `password` = ? WHERE `email` = ?", array($new_password, $email));

					// drop any previous requested
					$db->sqlquery("DELETE FROM `password_reset` WHERE `user_email` = ?", array($email));

					$core->message("Your password has been updated! <a href=\"index.php?module=login\">Click here to now login.</a>");
				}
			}
		}
	}
}
?>

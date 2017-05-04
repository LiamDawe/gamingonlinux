<?php
$templating->set_previous('title', 'Login', 1);
$templating->set_previous('meta_description', 'Login page for ' . $core->config('site_title'), 1);

$templating->merge('login');

if (!isset($_POST['action']))
{
	if (!isset($_GET['forgot']) && !isset($_GET['reset']) && !isset($_GET['twitter']) && !isset($_GET['steam']))
	{
		if ($_SESSION['user_id'] == 0)
		{
			$templating->block('main', 'login');
			$templating->set('url', $core->config('website_url'));

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
			
			$twitter_button = '';
			if ($core->config('twitter_login') == 1)
			{	
				$twitter_button = '<a href="'.$core->config('website_url').'index.php?module=login&twitter" class="btn-auth btn-twitter"><span class="btn-icon"><img src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/social/white/twitter.png" /> </span>Sign in with <b>Twitter</b></a>';
			}
			$templating->set('twitter_button', $twitter_button);
			
			$steam_button = '';
			if ($core->config('steam_login') == 1)
			{
				$steam_button = '<a href="'.$core->config('website_url').'index.php?module=login&steam" class="btn-auth btn-steam"><span class="btn-icon"><img src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/social/white/steam.png" /> </span>Sign in with <b>Steam</b></a>';
			}
			$templating->set('steam_button', $steam_button);
			
			$google_button = '';
			if ($core->config('google_login') == 1)
			{
				$client_id = $core->config('google_login_public'); 
				$client_secret = $core->config('google_login_secret');
				$redirect_uri = $core->config('website_url') . 'includes/google/login.php';
				require_once ($core->config('path') . 'includes/google/libraries/Google/autoload.php');
				$client = new Google_Client();
				$client->setClientId($client_id);
				$client->setClientSecret($client_secret);
				$client->setRedirectUri($redirect_uri);
				$client->addScope("email");
				$client->addScope("profile");
				$service = new Google_Service_Oauth2($client);
				$authUrl = $client->createAuthUrl();
				
				$google_button = '<a href="'.$authUrl.'" class="btn-auth btn-google"><span class="btn-icon"><img src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/social/white/google-plus.png" /> </span>Sign in with <b>Google</b></a>';
			}
			$templating->set('google_button', $google_button);
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
	
		$steam_user = new steam_user($user, $core);
		$steam_user->apikey = $core->config('steam_openid_key'); // put your API key here
		$steam_user->domain = $core->config('website_url'); // put your domain
		$steam_user->return_url = $core->config('website_url');
		$steam_user->signIn();
	}
	
	else if (isset($_GET['twitter']))
	{		
		require("includes/twitter/twitteroauth.php");

		$twitteroauth = new TwitterOAuth($core->config('tw_consumer_key'), $core->config('tw_consumer_skey'));

		// Requesting authentication tokens, the parameter is the URL we will be redirected to
		$request_token = $twitteroauth->getRequestToken($core->config('website_url') . 'includes/twitter/getTwitterData.php');

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
			$core->message('We were unable to autheticate you, Twitter might be having issues. If this persists please contact the admins.');
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
			
			header("Location: ".$core->config('website_url'));
		}

		else
		{
			$_SESSION['login_error_username'] = $_POST['username'];
			header("Location: ".$core->config('website_url')."/index.php?module=login");
		}
	}

	else if ($_POST['action'] == 'Send')
	{
		// check if user exists
		$db->sqlquery("SELECT `email` FROM ".$core->db_tables['users']." WHERE `email` = ?", array($_POST['email']));
		if ($db->num_rows() == 0)
		{
			header("Location: ".$core->config('website_url')."index.php?module=login&forgot&bademail");
		}

		else
		{
			$random_string = core::random_id();

			// drop any previous requested
			$db->sqlquery("DELETE FROM `password_reset` WHERE `user_email` = ?", array($_POST['email']));

			// make expiry 7 days from now
			$next_week = time() + (7 * 24 * 60 * 60);

			// insert number to database with email
			$db->sqlquery("INSERT INTO `password_reset` SET `user_email` = ?, `secret_code` = ?, `expires` = ?", array($_POST['email'], $random_string, $next_week));

			$url_email = rawurlencode($_POST['email']);

			// send mail with link including the key
			$html_message = '<p>Someone, hopefully you, has requested to reset your password on ' . $core->config('website_url') . '!</p>
			<p>If you didn\'t request this, don\'t worry! Unless someone has access to your email address it isn\'t an issue!</p>
			<p>Please click <a href="' . $core->config('website_url') . 'index.php?module=login&reset&code=' . $random_string . '&email=' . $url_email . '">this link</a> to reset your password</p>';

			$plain_message = 'Someone, hopefully you, has requested to reset your password on ' . $core->config('website_url') . '! Please go here: "' . $core->config('website_url') . '"index.php?module=login&reset&code=' . $random_string . '&email=' . $url_email . ' to change your password. If you didn\'t request this, you can ignore it as it\'s not a problem unless anyone has access to your email!';

			// Mail it
			if ($core->config('send_emails') == 1)
			{
				$mail = new mail($_POST['email'], $core->config('site_title') . ' password reset request', $html_message, $plain_message);
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

			$core->message("That reset request has expired, you will need to <a href=\"".$core->config('website_url')."/index.php?module=login&forgot\">request a new code!</a>");
		}

		else
		{
			// check code and email is valid
			$db->sqlquery("SELECT `user_email` FROM `password_reset` WHERE `user_email` = ? AND `secret_code` = ?", array($email, $code));
			if ($db->num_rows() != 1)
			{
				$core->message("That is not a correct password reset request! <a href=\"".$core->config('website_url')."index.php?module=login\">Go back.</a>");
			}

			else
			{
				// check the passwords match
				if ($_POST['password'] != $_POST['password_again'])
				{
					$core->message("The new passwords didn't match! <a href=\"".$core->config('website_url')."index.php?module=login\">Go back.</a>");
				}

				// change the password
				else
				{
					$new_password = password_hash($_POST['password'], PASSWORD_BCRYPT);

					// new password
					$db->sqlquery("UPDATE ".$core->db_tables['users']." SET `password` = ? WHERE `email` = ?", array($new_password, $email));

					// drop any previous requested
					$db->sqlquery("DELETE FROM `password_reset` WHERE `user_email` = ?", array($email));

					$core->message("Your password has been updated! <a href=\"".$core->config('website_url')."index.php?module=login\">Click here to now login.</a>");
				}
			}
		}
	}
}
?>

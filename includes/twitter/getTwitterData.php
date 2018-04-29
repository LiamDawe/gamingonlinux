<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

include (APP_ROOT . "/includes/twitter/twitteroauth.php");
include (APP_ROOT . "/includes/twitter/functions.php");

define('YOUR_CONSUMER_KEY', $core->config('tw_consumer_key'));
define('YOUR_CONSUMER_SECRET', $core->config('tw_consumer_skey'));

$cookie_length = 60*60*24*30; // 30 days

if (!empty($_GET['oauth_verifier']) && !empty($_SESSION['oauth_token']) && !empty($_SESSION['oauth_token_secret']))
{
	// We've got everything we need
	$twitteroauth = new TwitterOAuth(YOUR_CONSUMER_KEY, YOUR_CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
	// Let's request the access token
	$access_token = $twitteroauth->getAccessToken($_GET['oauth_verifier']);
	// Save it in a session var
	$_SESSION['access_token'] = $access_token;
	// Let's get the user's info
	$user_info = $twitteroauth->get('account/verify_credentials');

	if (isset($user_info->error))
	{
		// Something's wrong, go back to square 1
		die('error');
	}

	else
	{
		$uid = $user_info->id;
		$username = $user_info->screen_name;
		$twitter_user = new twitter_user();
		$userdata = $twitter_user->checkUser($uid, 'twitter', $username);

		// linking account via usercp
		if ($twitter_user->new == 0)
		{
			header("Location: /usercp.php");
		}

    	// logging in via twitter
		else if ($twitter_user->new == 1)
		{
			if (!isset($userdata) || empty($userdata))
			{
				die('There was an error getting your user data!');
			}

			$user->user_details = $userdata;
		
			// update IP address and last login
			$dbl->run("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $userdata['user_id']));

			$user->check_banned($userdata['user_id']);

			$generated_session = md5(mt_rand() . $userdata['user_id'] . $_SERVER['HTTP_USER_AGENT']);

			$user->new_login($generated_session);

			if ($userdata['social_stay_cookie'] == 1)
			{
				setcookie('gol_session', $generated_session, time()+$cookie_length, '/', $core->config('cookie_domain'));
			}

			header("Location: " . $core->config('website_url'));
		}

		// registering a new account with a twitter handle, send them to register with the twitter data
		else if($twitter_user->new == 2)
		{
			if (!isset($userdata) || empty($userdata))
			{
				die('There was an error getting your user data!');
			}
			
			$_SESSION['twitter_data'] = $userdata;

			header("Location: /index.php?module=register&twitter_new");
		}
	}
}

else
{
    // Something's missing, go back to square 1
    print_r($_SESSION);
    die('error2');
}
?>

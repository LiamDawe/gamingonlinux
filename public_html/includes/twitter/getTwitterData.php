<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

use Abraham\TwitterOAuth\TwitterOAuth;

require APP_ROOT . "/includes/bootstrap.php";
include (APP_ROOT . "/includes/twitter/functions.php");

define('CONSUMER_KEY', $core->config('tw_consumer_key'));
define('CONSUMER_SECRET', $core->config('tw_consumer_skey'));
define('OAUTH_CALLBACK', getenv('OAUTH_CALLBACK'));

$request_token = [];
$request_token['oauth_token'] = $_SESSION['oauth_token'];
$request_token['oauth_token_secret'] = $_SESSION['oauth_token_secret'];

$cookie_length = 60*60*24*60; // 30 days

if (isset($_REQUEST['oauth_token']) && $request_token['oauth_token'] == $_REQUEST['oauth_token'])
{
	// We've got everything we need
	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $request_token['oauth_token'], $request_token['oauth_token_secret']);

	// Let's request the access token
	$access_token = $connection->oauth("oauth/access_token", ["oauth_verifier" => $_REQUEST['oauth_verifier']]);

	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']); 

	// Save it in a session var
	$_SESSION['access_token'] = $access_token;
	// Let's get the user's info
	$user_info = $connection->get('account/verify_credentials');

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

			$lookup = base64_encode(random_bytes(9));
			$validator = base64_encode(random_bytes(18));

			$user->new_login($lookup,$validator);

			setcookie('gol_session', $validator . '.' . $validator, $user->expires_date->getTimestamp(), '/', $user->cookie_domain, 1, 1);

			header("Location: " . $core->config('website_url'));
			die();
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
			die();
		}
	}
}

else
{
    die('Sorry but your authorization details did not match.');
}
?>

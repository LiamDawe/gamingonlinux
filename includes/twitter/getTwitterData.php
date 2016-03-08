<?php
session_start();
require "../class_mysql.php";
include('../config.php');
include('../class_core.php');
require("twitteroauth.php");
require "functions.php";

$db = new mysql($database_host, $database_username, $database_password, $database_db);

// get config
$db->sqlquery("SELECT `data_key`, `data_value` FROM `config`");
$fetch_config = $db->fetch_all_rows();

$config = array();
foreach ($fetch_config as $config_set)
{
	$config[$config_set['data_key']] = $config_set['data_value'];
}

define('YOUR_CONSUMER_KEY', $config['tw_consumer_key']);
define('YOUR_CONSUMER_SECRET', $config['tw_consumer_skey']);

$core = new core();

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
	
	// Print user's info DEBUG ONLY TO SEE WHATS AVAILABLE
	/*
	echo '<pre>';
	print_r($user_info);
	echo '</pre><br/>';*/
    
	if (isset($user_info->error)) 
	{
		// Something's wrong, go back to square 1  
		die('error');
	} 
    
	else 
	{
		$uid = $user_info->id;
        	$username = $user_info->screen_name;
        	$user = new User();
        	$userdata = $user->checkUser($uid, 'twitter', $username);

		// linking account via usercp
        	if ($user->new == 0)
        	{
			header("Location: /usercp.php");
        	}
        	
        	// logging in via twitter
        	else if ($user->new == 1)
        	{
			// update IP address and last login
			$db->sqlquery("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array($core->ip, $core->date, $userdata['user_id']));

			// check if they are banned
			$db->sqlquery("SELECT `banned` FROM `users` WHERE `user_id` = ?", array($userdata['user_id']), 'class_user.php');
			$ban_check = $db->fetch();

			if ($ban_check['banned'] == 1)
			{
				setcookie('gol_stay', "",  time()-60, '/');
				header("Location: /home/banned");
				exit;
			}

			if ($_COOKIE['request_stay'] == 1)
			{
				$generated_session = md5(mt_rand  . $userdata['user_id'] . $_SERVER['HTTP_USER_AGENT']);
							
				setcookie('gol_stay', $userdata['user_id'],  time()+31556926, '/', 'gamingonlinux.com');
				setcookie('gol_session', $generated_session,  time()+31556926, '/', 'gamingonlinux.com'); 

				$db->sqlquery("DELETE FROM `saved_sessions` WHERE `user_id` = ?", array($userdata['user_id']), 'steam_login.php');
				$db->sqlquery("INSERT INTO `saved_sessions` SET `user_id` = ?, `session_id` = ?", array($userdata['user_id'], $generated_session), 'steam_login.php'); 
			}

        		$_SESSION['user_id'] = $userdata['user_id'];
        		$_SESSION['username'] = $userdata['username'];
			$_SESSION['user_group'] = $userdata['user_group'];
			$_SESSION['secondary_user_group'] = $userdata['secondary_user_group'];
			$_SESSION['theme'] = $userdata['theme'];
			$_SESSION['infinite_scroll'] = $userdata['infinite_scroll'];
			$_SESSION['bad'] = 0;
			
        		header("Location: /");
        	}

		// registering a new account with a twitter handle, send them to register with the twitter data
		else if($user->new == 2)
		{
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

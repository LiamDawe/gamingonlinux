<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$user = new user($dbl, $core);

include (APP_ROOT . "/includes/google/functions.php");

require_once (APP_ROOT . '/includes/google/libraries/Google/autoload.php');

//Insert your cient ID and secret 
//You can get it from : https://console.developers.google.com/
$client_id = $core->config('google_login_public'); 
$client_secret = $core->config('google_login_secret');
$redirect_uri = $core->config('website_url') . 'includes/google/login.php';

/************************************************
  Make an API request on behalf of a user. In
  this case we need to have a valid OAuth 2.0
  token for the user, so we need to send them
  through a login flow. To do this we need some
  information from our API console project.
 ************************************************/
$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);

/************************************************
  When we create the service here, we pass the
  client to it. The client then queries the service
  for the required scopes, and uses that when
  generating the authentication URL later.
 ************************************************/
$service = new Google_Service_Oauth2($client);

/************************************************
  If we have a code back from the OAuth 2.0 flow,
  we need to exchange that with the authenticate()
  function. We store the resultant access token
  bundle in the session, and redirect to ourself.
*/
  
if (isset($_GET['code']))
{
	$client->authenticate($_GET['code']);
	$_SESSION['google_access_token'] = $client->getAccessToken();
  
  	$google_user = $service->userinfo->get(); //get user info

	$google_check = new google_check($dbl);
	$userdata = $google_check->checkUser($google_user->email);

	// linking account via usercp
	if ($google_check->new == 0)
	{
		header("Location: /usercp.php");
		die();
	}
	
	// logging in via google
	else if ($google_check->new == 1)
	{
		if (!isset($userdata) || empty($userdata))
		{
			die('There was an error getting your user data!');
		}
		
		// update IP address and last login
		$dbl->run("UPDATE `users` SET `ip` = ?, `last_login` = ? WHERE `user_id` = ?", array(core::$ip, core::$date, $userdata['user_id']));

		$user->check_banned($userdata['user_id']);

		$generated_session = md5(mt_rand()  . $userdata['user_id'] . $_SERVER['HTTP_USER_AGENT']);

		$user->new_login($userdata, $generated_session);

		setcookie('gol_stay', $userdata['user_id'],  time()+31556926, '/', $core->config('cookie_domain'));
		setcookie('gol_session', $generated_session,  time()+31556926, '/', $core->config('cookie_domain'));

		header("Location: /");
		die();
	}

	// registering a new account with a google handle, send them to register with the google data
	else if($google_check->new == 2)
	{
		if (!isset($userdata) || empty($userdata))
		{
			die('There was an error getting your user data!');
		}
		
		$_SESSION['google_name'] = $google_user->name;
		$_SESSION['google_data'] = $userdata;
		$_SESSION['google_avatar'] = $google_user->picture;

		header("Location: /index.php?module=register&google_new");
		die();
	}
}
?>


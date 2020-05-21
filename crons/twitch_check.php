<?php
/*
Twitch Stream grabber, checks if they're live and saves the results to a file for quick access.
This file is meant to be run as a CRON every minute, since Twitch has rate limits in place, this makes it super simple to do it once and store locally.
Which then makes it super fast to query, since we're using a local copy.

GOL Channel User ID: 50905707
*/
define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');

require APP_ROOT . "/includes/cron_bootstrap.php";

define ('ACCESS_TOKEN_FILE', dirname(__FILE__) . '/twitch_access_token');

define ('CHANNEL_ID', 50905707); // just the user id, not sensitive info can store here anyone can get it

function get_access_token($core)
{
	$auth = core::file_get_contents_curl('https://id.twitch.tv/oauth2/token?client_id='.$core->config('twitch_dev_key').'&client_secret='.$core->config('twitch_secret').'&grant_type=client_credentials', "POST", NULL);

	$auth_details = json_decode($auth, true);

	// store the access token
	$to_file = $auth_details['access_token'];

	$fp = fopen(ACCESS_TOKEN_FILE, 'w'); 
	fwrite($fp, $to_file);
	fclose($fp);

	return $auth_details['access_token'];
}
// get a new access token if one doesn't exist
if (!file_exists(ACCESS_TOKEN_FILE))
{
	echo 'Grabbing new access token - file doesn\'t exist';
	get_access_token($core);
}

$access_token = file_get_contents(ACCESS_TOKEN_FILE);

function validate_token ($core, $access_token)
{
	$validate = core::file_get_contents_curl('https://id.twitch.tv/oauth2/validate', 'GET', NULL, array('Authorization: OAuth '.$access_token));

	$validation_details = json_decode($validate, true);

	// need to refresh the details
	if (empty($validation_details))
	{
		echo 'Grabbing new access token - invalid.';
		$access_token = get_access_token($core);

		if ($access_token)
		{
			validate_token($core, $access_token);
		}
		else
		{
			echo 'Unable to generate new access token.';
			return false;
		}
		
	}

	echo 'Access key valid, returning.';
	return $access_token;
}

$access_token = validate_token($core, $access_token);
echo $access_token;
if ($access_token)
{
	// grab details of their stream, will be blank of they're not live)
	$stream = core::file_get_contents_curl("https://api.twitch.tv/helix/streams?user_id=".CHANNEL_ID, "GET", NULL, array("Client-ID: ". $core->config('twitch_dev_key'), 'Authorization: Bearer '.$access_token));
	var_dump($stream);
	$stream_details = json_decode($stream, true);

	// are they streaming a specific game?
	if (isset($stream_details['data'][0]['game_id']))
	{
		$game = core::file_get_contents_curl("https://api.twitch.tv/helix/games?id=".$stream_details['data'][0]['game_id'], "GET", NULL, array("Client-ID: ". $core->config('twitch_dev_key'), 'Authorization: Bearer '.$access_token));
		$game_details = json_decode($game, true);
		$game_name = $game_details['data'][0]['name'];

		$stream_details['game_name'] = $game_name; // add the game name to the details array
	}

	echo PHP_EOL . 'Stream details' . PHP_EOL;

	$to_file = json_encode($stream_details);

	print_r($stream_details);

	$to_file = json_encode($stream_details);

	$fp = fopen(APP_ROOT . '/uploads/goltwitchcheck.json', 'w'); 
	fwrite($fp, $to_file);
	fclose($fp);
}
?>

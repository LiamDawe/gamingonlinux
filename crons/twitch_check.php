<?php
/*
Twitch Stream grabber, checks if they're live and saves the results to a file for quick access.
This file is meant to be run as a CRON every minute, since Twitch has rate limits in place, this makes it super simple to do it once and store locally.
Which then makes it super fast to query, since we're using a local copy.
*/
define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');

require APP_ROOT . "/includes/bootstrap.php";

// grab details of their stream, will be blank of they're not live (API limits ~30 per minute)
$stream = core::file_get_contents_curl("https://api.twitch.tv/helix/streams?user_id=50905707", "GET", NULL, array("Client-ID: ". $core->config('twitch_dev_key')));
$stream_details = json_decode($stream, true);

// are they streaming a specific game?
if (isset($stream_details['data'][0]['game_id']))
{
	$game = core::file_get_contents_curl("https://api.twitch.tv/helix/games?id=".$stream_details['data'][0]['game_id'], "GET", NULL, array("Client-ID: ". $core->config('twitch_dev_key')));
	$game_details = json_decode($game, true);
	$game_name = $game_details['data'][0]['name'];

	$stream_details['game_name'] = $game_name; // add the game name to the details array
}

$to_file = json_encode($stream_details);

$fp = fopen(APP_ROOT . '/uploads/goltwitchcheck.json', 'w'); 
fwrite($fp, $to_file);
fclose($fp);
?>

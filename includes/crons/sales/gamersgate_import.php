<?php
error_reporting(E_ALL);

echo "GamersGate importer started on " .date('d-m-Y H:m:s'). "\n";

define('path', '/home/gamingonlinux/public_html/includes/');

include(path . 'config.php');

include(path . 'class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'class_core.php');
$core = new core();

$url = 'http://www.gamersgate.com/feeds/products?filter=linux,offers&dateformat=timestamp';
if (file_get_contents($url) == true)
{
	// magic
}
else
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach the GamersGate sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	die('GamersGate XML not available!');
}
$get_url = file_get_contents($url);

$get_url = preg_replace("^&(?!#38;)^", "&amp;", $get_url);

$xml = simplexml_load_string($get_url);

// get config
$db->sqlquery("SELECT `data_key`, `data_value` FROM `config`");
$fetch_config = $db->fetch_all_rows();

$config = array();
foreach ($fetch_config as $config_set)
{
	$config[$config_set['data_key']] = $config_set['data_value'];
}

$games = '';
$email = 0;

foreach ($xml->item as $game)
{

	// for seeing what we have available
	//echo '<pre>';
	//print_r($game);
	//echo '</pre>';

	$new_title = html_entity_decode($game->title, ENT_QUOTES);

	// ADD IT TO THE GAMES DATABASE, FOR FUTURE USE
	$db->sqlquery("SELECT `name` FROM `game_list` WHERE `name` = ?", array($new_title));
	if ($db->num_rows() == 0)
	{
		$db->sqlquery("INSERT INTO `game_list` SET `name` = ?", array($new_title));
	}

	$db->sqlquery("UPDATE `game_list` SET `on_sale` = 1 WHERE `name` = ?", array($new_title));

	// they are cet (UTC+1), so add one hour, but they also use CEST in summer, because they are idiots and take their system timestamps, not UTC
	$date = new DateTime(null, new DateTimeZone('Europe/Stockholm'));
	$cet = ($date->getTimestamp()+ $date->getOffset()).'<br />'."\r\n";

	$date = new DateTime(null, new DateTimeZone('UTC'));
	$utc = ($date->getTimestamp()+ $date->getOffset()).'<br />'."\r\n";

	$difference = $cet - $utc;

	$sale_ends = $game->discount_end + $difference;

	//for testing output
	echo 'This is available for Linux!<br />';
	echo "\n* Starting import of ".$game->title."\n";
	echo "URL: ", $game->link, "\n";
	echo "Price: ", $game->price, "\n";
	echo "Original Price: ", $game->srp, "\n";
	echo "DRM: ", $game->drm, "\n";
	echo "Steam Key: ", $game->steam_key, "\n";
	echo "Sale ends: ", $game->discount_end . ' | Original' . ' | ' . $sale_ends . ' | With auto adjustments' . "\n";

	if ($game->price != "-" && $game->price > 0)
	{
		// search if that title exists
		$db->sqlquery("SELECT `info`, `provider_id` FROM `game_sales` WHERE `info` = ? AND `provider_id` = ?", array($new_title, 12), 'gamersgate_import.php');

		// if it does exist, make sure it's not from indiegamestand already
		$check = 0;
		if ($db->num_rows() == 1)
		{
			$check = 1;
			echo "\tI already know about this game, and GamersGate told me about it\n";
		}

		else
		{
			echo "\nI didn't know about this game before.\n";
		}

		$drm_free = 0;
		if ($game->drm == 'DRM Free')
		{
			$drm_free = 1;
		}

		$steam_key = 0;
		if ($game->steam_key == 1)
		{
			$steam_key = 1;
		}

		// all checks out - insert into database here
		if ($check == 0)
		{
			$db->sqlquery("INSERT INTO `game_sales` SET `info` = ?, `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 12, `dollars` = ?, `dollars_original` = ?, `drmfree` = ?, `steam` = ?, `expires` = ?", array($new_title, $game->link, core::$date, $game->price, $game->srp, $drm_free, $steam_key, $sale_ends), 'gamersgate_import.php');

			$sale_id = $db->grab_id();

			echo "\tAdded this game to the sales DB with id: " . $sale_id . ".\n";
			echo "\r\n==================================================\r\n";

			$games .= $new_title . ', Sale ends: ' . $game->discount_end . '<br />';

			$email = 1;
		}

		// if we already have it, just set the price and % off to the current amount (in-case it's different) or if they now have steam/desura keys
		else if ($check == 1)
		{
			$db->sqlquery("UPDATE `game_sales` SET `dollars` = ?, `dollars_original` = ?, `drmfree` = ?, `steam` = ?, `expires` = ? WHERE `info` = ? AND `provider_id` = 12", array($game->price, $game->srp, $drm_free, $steam_key, $sale_ends, $new_title), 'gamersgate_import.php');

			echo "  Updated " . $new_title . " with current price and % off.\n";
			echo "\r\n==================================================\r\n";
		}
	}
	else
	{
		echo $game->title . " has no discount price, not on sale!";
	}
echo "\n"; //Just a bit of white space here.
}

echo "\n\n";//More whitespace, just to make the output look a bit more pretty
/*
if ($email == 1)
{
	// multiple recipients
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL Contact Us - GamersGate sales added';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "<a href=\"http://www.gamingonlinux.com/sales/\">Sales Page</a> - The auto GamersGate salesman has added<br />$games", $headers);

	echo "Mail sent!";
}*/
echo "End of GamersGate import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

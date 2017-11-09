<?php
error_reporting(E_ALL);

echo "GamersGate importer started on " .date('d-m-Y H:m:s'). "\n";

$doc_root = dirname( dirname( dirname( dirname(__FILE__) ) ) );

// we dont need the whole bootstrap
require $doc_root . '/includes/loader.php';
include $doc_root . '/includes/config.php';
$dbl = new db_mysql();
$core = new core($dbl);

$url = 'http://www.gamersgate.com/feeds/products?filter=linux,offers&dateformat=timestamp&country=usa';
if (core::file_get_contents_curl($url) == true)
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
	error_log("Couldn't reach the GamersGate sales XML");	
	die('GamersGate XML not available!');
}
$get_url = core::file_get_contents_curl($url);

$get_url = preg_replace("^&(?!#38;)^", "&amp;", $get_url);

$xml = simplexml_load_string($get_url);

$on_sale = [];

foreach ($xml->item as $game)
{
	// for seeing what we have available
	//echo '<pre>';
	//print_r($game);
	//echo '</pre>';

	$new_title = html_entity_decode($game->title, ENT_QUOTES);

	// they are cet (UTC+1), so add one hour, but they also use CEST in summer, not UTC *sigh*
	/*$date = new DateTime(null, new DateTimeZone('Europe/Stockholm'));
	$cet = ($date->getTimestamp()+ $date->getOffset()).'<br />'."\r\n";

	$date = new DateTime(null, new DateTimeZone('UTC'));
	$utc = ($date->getTimestamp()+ $date->getOffset()).'<br />'."\r\n";

	$difference = $cet - $utc;

	$sale_ends = $game->discount_end + $difference;*/

	//for testing output
	echo 'This is available for Linux!<br />';
	echo "\n* Starting import of ".$game->title."\n";
	echo "URL: ", $game->link, "\n";
	echo "Price: ", $game->price, "\n";
	echo "Original Price: ", $game->srp, "\n";
	echo "DRM: ", $game->drm, "\n";
	echo "Steam Key: ", $game->steam_key, "\n";
	//echo "Sale ends: ", $game->discount_end . ' | Original' . ' | ' . $sale_ends . ' | With auto adjustments' . "\n";

	if ($game->price != "-" && $game->price > 0)
	{
		// ADD IT TO THE GAMES DATABASE
		$game_list = $dbl->run("SELECT `id` FROM `calendar` WHERE `name` = ?", array($new_title))->fetch();
			
		if (!$game_list)
		{
			$dbl->run("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `on_sale` = 1", array($new_title, date('Y-m-d'))); // they don't give the release date, just add in today's date, we can fix manually later if/when we need to
			
			// need to grab it again
			$game_list = $dbl->run("SELECT `id` FROM `calendar` WHERE `name` = ?", array($new_title))->fetch();
		}
		else
		{
			$dbl->run("UPDATE `calendar` SET `on_sale` = 1 WHERE `id` = ?", array($game_list['id']));
		}
			
		$on_sale[] = $game_list['id'];
			
		$check_sale = $dbl->run("SELECT 1 FROM `sales` WHERE `game_id` = ? AND `store_id` = 8", array($game_list['id']))->fetch();

		// all checks out - insert into database here
		if (!$check_sale)
		{
			$dbl->run("INSERT INTO `sales` SET `game_id` = ?, `store_id` = 8, `accepted` = 1, `sale_dollars` = ?, `original_dollars` = ?, `link` = ?", array($game_list['id'], $game->price, $game->srp, $game->link));
			
			$sale_id = $dbl->new_id();
			
			echo "\tAdded ".$new_title." to the sales DB with id: " . $sale_id . ".\n";
		}
	}
	echo "\n"; //Just a bit of white space here.
}

// remove any not found on sale
$in  = str_repeat('?,', count($on_sale) - 1) . '?';
$dbl->run("DELETE FROM `sales` WHERE `game_id` NOT IN ($in) AND `store_id` = 8", $on_sale);

echo "End of GamersGate import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

<?php
define('DEBUG', (isset($argv[1])? true : false) ); //Start using `php gamerepublic_import.php debug`
echo "Games Republic importer ". (DEBUG? "in debug mode": "") ." started on " .date('d-m-Y H:m:s'). "\n";

define('path', '/home/gamingonlinux/public_html/includes/');

include(path . 'config.php');

include(path . 'class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'class_core.php');
$core = new core();

$date = strtotime(gmdate("d-n-Y H:i:s"));

$url = 'https://linux.gamesrepublic.com/xml/catalog?currency=usd&count=all&mode=OnlyPromotions';
if (file_get_contents($url) == true)
{
	// magic
}
else
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach the Games Republic sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	die('Games Republic XML not available!');
}

$xml = simplexml_load_string(file_get_contents($url));

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

foreach ($xml->group->o as $game)
{

	// for seeing what we have available
	//but for the love of god, do not let it do this every 5 minutes

	if (DEBUG) {
		echo '<pre>';
		print_r($game);
		echo '</pre>';
	}

	echo "\n* Checking if ".$game->{'name'}." supports Linux<br />";

	$os_options = array();
	foreach ($game->attrs->{'platforms'}->platform as $os)
	{
		$os_options[] = $os;
	}

	// put Operating Systems into an array so we can loop through the Linux ones
	if (in_array("Linux", $os_options))
	{
		/*
		//for testing output
		echo 'This is available for Linux!<br />';
		echo "\n* Starting import of ".$game->{'name'}."\n";
		echo "URL: ", $game->attributes()->url, "\n";
		echo "Price USD: ", $game->attributes()->priceUSD, "\n";
		echo "Price GBP: ", $game->attributes()->priceGBP, "\n";
		echo "Price EUR: ", $game->attributes()->priceEUR, "\n";
		echo "DRM Free: ", $game->attributes()->isDrmFree, "\n";
		echo "% off: ", $game->{'discount'}, "%\n";
		echo "Sale ends: ", $game->attributes()->promotionTo. "\n";
		echo "Steam: " . $game->attrs->{'productProtections'}->{'productProtection'};*/

		// ADD IT TO THE GAMES DATABASE, FOR FUTURE USE
		$db->sqlquery("SELECT `name` FROM `game_list` WHERE `name` = ?", array($game->{'name'}));
		if ($db->num_rows() == 0)
		{
			$db->sqlquery("INSERT INTO `game_list` SET `name` = ?", array($game->{'name'}));
		}

		// search if that title exists
		$db->sqlquery("SELECT `info`, `provider_id` FROM `game_sales` WHERE `info` = ?", array($game->{'name'}), 'gamesrepublic_import.php');

		// if it does exist, make sure it's not from indiegamestand already
		$check = 0;
		if ($db->num_rows() >= 1)
		{
				while ($test = $db->fetch())
				{
					if ($test['provider_id'] == 33)
					{
						$check = 1;
					}
				}

				// tell the outcome
				if ($check == 1)
				{
					echo "\tI already know about this game, and GamesRepublic told me about it\n";
				}

				else
				{
					echo "\tI already know about this game, however GamesRepublic wasn't the one who told me about it\n";
				}
			}

			else
			{
				echo "\nI didn't know about this game before.\n";
			}

			$drm_free = 0;
			if ($game->attributes()->isDrmFree == 'True')
			{
				$drm_free = 1;
			}

			$steam = 0;
			if ($game->attrs->{'productProtections'}->{'productProtection'} == 'Steam')
			{
				$steam = 1;
			}

			$expires = strtotime($game->attributes()->promotionTo) - 3600;
			if (core::config('summer_time') == 1)
			{
				$expires = strtotime($game->attributes()->promotionTo);
			}

			// all checks out - insert into database here
			if ($check == 0)
			{
				$db->sqlquery("INSERT INTO `game_sales` SET `info` = ?, `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 33, `dollars` = ?, `dollars_original` = ?, `pounds` = ?, `pounds_original` = ?, `euros` = ?, `euros_original` = ?, `drmfree` = ?, `steam` = ?, `expires` = ?", array($game->{'name'}, $game->attributes()->url, core::$date, $game->attributes()->priceUSD, $game->attributes()->regularPriceUSD, $game->attributes()->priceGBP, $game->attributes()->regularPriceGBP, $game->attributes()->priceEUR, $game->attributes()->regularPriceEUR, $drm_free, $steam, $expires));

				$sale_id = $db->grab_id();

				echo "\tAdded this game to the sales DB with id: " . $sale_id . ".\n";
				echo "\r\n==================================================\r\n";

				$games .= $game->{'name'} . ', Sale ends: ' . $game->attributes()->promotionTo . '<br />';

				$email = 1;
			}

			// if we already have it, just set the price and % off to the current amount (in-case it's different) or if they now have steam/desura keys
			else if ($check == 1)
			{
				$db->sqlquery("UPDATE `game_sales` SET `dollars` = ?, `dollars_original` = ?, `pounds` = ?, `pounds_original` = ?, `euros` = ?, `euros_original` = ?, `drmfree` = ?, `steam` = ?, `expires` = ? WHERE `info` = ? AND `provider_id` = 33", array($game->attributes()->priceUSD, $game->attributes()->regularPriceUSD, $game->attributes()->priceGBP, $game->attributes()->regularPriceGBP, $game->attributes()->priceEUR, $game->attributes()->regularPriceEUR, $drm_free, $steam, $expires, $game->{'name'}));

				echo "  Updated " . $game->{'name'} . " with current price.\n";
				echo "\r\n==================================================\r\n";
			}

	} //End of if [linux]
	else
	{
		echo "\tBuggers, this game isn't for linux!\n";
		echo "\r\n==================================================\r\n";
	}
echo "\n"; //Just a bit of white space here.
}

echo "\n\n";//More whitespace, just to make the output look a bit more pretty

/*
if ($email == 1)
{
	// multiple recipients
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL Contact Us - GamesRepublic sales added';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "<a href=\"http://www.gamingonlinux.com/sales/\">Sales Page</a> - The auto Games Republic salesman has added<br />$games", $headers);

	echo "Mail sent!";
}*/
echo "End of Games Republic import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

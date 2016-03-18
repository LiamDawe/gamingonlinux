<?php
// IGS Sales Expire at Midnight EST Time
echo "IndieGameStand importer started on " .date('d-m-Y H:m:s'). "\n";

include('/home/gamingonlinux/public_html/includes/config.php');

include('/home/gamingonlinux/public_html/includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('/home/gamingonlinux/public_html/includes/class_core.php');
$core = new core();

$date = strtotime(gmdate("d-n-Y H:i:s"));

$url = 'https://indiegamestand.com/store/salefeed.php';
if (file_get_contents($url) == true)
{
	// magic
}
else
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach IGS sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	die('IGS XML not available!');
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

foreach ($xml->channel->item as $game)
{
	/*
	// for seeing what we have available
	echo '<pre>';
	print_r($game);
	echo '</pre>';
	*/

	echo "\n* Checking if ".$game->{'title'}." supports Linux\n";

	echo "\n Linux: {$game->{'platforms'}->linux}, Windows: {$game->{'platforms'}->windows}, Mac: {$game->{'platforms'}->mac}\n\n";

	// put Operating Systems into an array so we can loop through the Linux ones
	if ($game->{'platforms'}->linux == 'Yes')
	{
		// ADD IT TO THE GAMES DATABASE, FOR FUTURE USE
		$db->sqlquery("SELECT `name` FROM `game_list` WHERE `name` = ?", array($game->{'title'}));
		if ($db->num_rows() == 0)
		{
			$db->sqlquery("INSERT INTO `game_list` SET `name` = ?", array($game->{'title'}));
		}

		$db->sqlquery("UPDATE `game_list` SET `on_sale` = 1 WHERE `name` = ?", array($game->{'title'}));

		if ($game->{'discountpercent'} != 0)
		{
			//for testing output

			echo "\n* Starting import of ".$game->{'title'}."\n";
			echo "Title: ", $game->{'title'}, "\n";
			echo "URL: ", $game->{'link'}, "\n";
			echo "Price: ", $game->price, "\n";
			echo "Steam key: ", $game->steamkeys, "\n";
			echo "% off: ", $game->{'discountpercent'}, "%\n";
			echo "Sale ends: ", $game->saleends. "\n";

			$title = $game->{'title'};
			$discount = $game->{'discountpercent'};

			// search if that title exists
			$db->sqlquery("SELECT `info`, `provider_id` FROM `game_sales` WHERE `info` = ?", array($game->{'title'}), 'igs_import.php');

			// if it does exist, make sure it's not from indiegamestand already
			$check = 0;
			if ($db->num_rows() >= 1)
			{
				while ($test = $db->fetch())
				{
					if ($test['provider_id'] == 22)
					{
						$check = 1;
					}
				}

				// tell the outcome
				if ($check == 1)
				{
					echo "\tI already know about this game, and IndieGameStand told me about it\n";
				}

				else
				{
					echo "\tI already know about this game, however IndieGameStand wasn't the one who told me about it\n";
				}
			}

			else
			{
				echo "\nI didn't know about this game before.\n";
			}

			// all checks out - insert into database here
			if ($check == 0)
			{
				$steam = 0;

				if ($game->steamkeys == 'yes')
				{
					$steam = 1;
				}

				//Create a new DateTime object from a date stamp, with the timezome EST
				$timestamp = date_create($game->saleends, new DateTimeZone("EST"));

				//Get the UNIX time stamp
				var_dump($timestamp->getTimestamp(), $timestamp);

				//Change the timezone to UTC, this converts it
				//$timestamp->setTimezone(new DateTimeZone("UTC"));

				$db->sqlquery("INSERT INTO `game_sales` SET `info` = ?, `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 22, `savings` = ?, `dollars` = ?, `steam` = ?, `expires` = ?, `imported_image_link` = ?, `drmfree` = ?", array($title, $game->{'link'}, core::$date, "$discount% off", $game->price, $steam, $timestamp->getTimestamp(), $game->imageurl, $game->drmfree), 'igs_import.php');

				$sale_id = $db->grab_id();

				echo "\tAdded this game to the sales DB with id: " . $sale_id . ".\n";
				echo "\r\n==================================================\r\n";

				$games .= $game->{'title'} . ', Sale ends: ' . $game->saleends . '<br />';

				$email = 1;
			}

			// if we already have it, just set the price and % off to the current amount (in-case it's different) or if they now have steam/desura keys
			else if ($check == 1)
			{
				$steam = 0;

				if ($game->steamkeys == 'yes')
				{
					$steam = 1;
				}

				//Create a new DateTime object from a date stamp, with the timezome EST
				$timestamp = date_create($game->saleends, new DateTimeZone("EST"));

				//Get the UNIX time stamp
				//var_dump($timestamp->getTimestamp(), $timestamp);

				//Change the timezone to UTC, this converts it
				$timestamp->setTimezone(new DateTimeZone("UTC"));

				$db->sqlquery("UPDATE `game_sales` SET `savings` = ?, `dollars` = ?, `steam` = ?, `expires` = ?, `imported_image_link` = ?, `drmfree` = ? WHERE `info` = ? AND `provider_id` = 22", array("$discount% off", $game->price, $steam, $timestamp->getTimestamp(), $game->imageurl, $game->drmfree, $title), 'igs_import.php');

				echo "  Updated " . $game->{'title'} . " with current price and % off.\n";
				echo "\r\n==================================================\r\n";
			}
		}

		else if ($game->{'discountpercent'} == 0)
		{
			echo "\n No discount, must be an error! Moving on...\n";
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
	$subject = 'GOL Contact Us - IndieGameStand sales added';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "<a href=\"http://www.gamingonlinux.com/sales/\">Sales Page</a> - The auto IndieGameStand salesman has added<br />$games", $headers);

	echo "Mail sent!";
}*/
echo "End of indiegamestand import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

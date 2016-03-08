<?php
echo "Itch importer started on " .date('d-m-Y H:m:s'). "\n";

define('path', '/home/gamingonlinux/public_html/');

include(path . 'includes/config.php');

include(path . 'includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'includes/class_core.php');
$core = new core();

$date = strtotime(gmdate("d-n-Y H:i:s"));

$url = 'http://itch.io/browse/platform-linux/price-sale.xml';
if (file_get_contents($url) == true)
{
	// magic
}
else
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach Itch.io sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	die('Itch XML not available!');
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
	/*
	// for seeing what we have available
	echo '<pre>';
	print_r($game);
	echo '</pre>';
	*/

	$game->plainTitle = html_entity_decode($game->plainTitle, ENT_QUOTES);

	// put Operating Systems into an array so we can loop through the Linux ones
	if ($game->{'platforms'}->linux == 'yes')
	{
		// ADD IT TO THE GAMES DATABASE, FOR FUTURE USE
		$db->sqlquery("SELECT `name` FROM `game_list` WHERE `name` = ?", array($game->plainTitle));
		if ($db->num_rows() == 0)
		{
			$db->sqlquery("INSERT INTO `game_list` SET `name` = ?", array($game->plainTitle));
		}

		$db->sqlquery("UPDATE `game_list` SET `on_sale` = 1 WHERE `name` = ?", array($game->plainTitle));

		echo "* Starting import of ".$game->plainTitle."\n";

		echo "\n Linux: {$game->{'platforms'}->linux}, Windows: {$game->{'platforms'}->windows}, Mac: {$game->{'platforms'}->mac}\n\n";

		if ($game->fullPrice != "$0.00" && $game->fullPrice != "£0.00" && $game->fullPrice != "0.00€")
		{
			if ($game->currency == 'USD')
			{
				$currency = 'dollars';
			}

			if ($game->currency == 'AUD')
			{
				$currency = 'dollars';
			}

			if ($game->currency == 'CAD')
			{
				$currency = 'dollars';
			}

			if ($game->currency == 'GBP')
			{
				$currency = 'pounds';
			}

			if ($game->currency == 'EUR')
			{
				$currency = 'euros';
			}

			$price = mb_substr($game->price, 1, null,'UTF-8');
			$price_original = mb_substr($game->fullPrice, 1,null, 'UTF-8');

			//for testing output
			/*
			echo "Title: ", $game->{'plainTitle'}, "<br />\n";
			echo "URL: ", $game->{'link'}, "<br />\n";
			echo "Currency: " . $currency . "<br />\n";
			echo "Price: $" . $price . "<br />\n";
			echo "Original Price: $" . $dollars_original . "<br />\n";
			echo "% off: ", $game->{'discountpercent'}, "%<br />\n";
			echo "<br />==================================================<br />";*/

			$title = $game->{'plainTitle'};
			$discount = $game->{'discountpercent'};

			// search if that title exists
			$db->sqlquery("SELECT `info`, `provider_id` FROM `game_sales` WHERE `info` = ?", array($game->{'plainTitle'}));

			// if it does exist, make sure it's not from indiegamestand already
			$check = 1;
			if ($db->num_rows() >= 1)
			{
				while ($test = $db->fetch())
				{
					if ($test['provider_id'] == 28)
					{
						$check = 0;
						echo "\tI already know about this game, and itch told me about it\n";
					}

					else
					{
						echo "\tI already know about this game, however itch wasn't the one who told me about it\n";
					}
				}
			}

			else
			{
				echo "\tI didn't know about this game before.\n";
			}

			// all checks out - insert into database here
			if ($check == 1)
			{
				$timestamp = strtotime($game->saleends);

				$expires = $timestamp;

				$db->sqlquery("INSERT INTO `game_sales` SET `info` = ?, `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 28, `savings` = ?, `{$currency}` = ?, `{$currency}_original` = ?, `expires` = ?, `imported_image_link` = ?, `drmfree` = 1", array($title, $game->{'link'}, $core->date, "$discount% off", $price, $price_original, $expires, $game->imageurl));

				$sale_id = $db->grab_id();

				echo "\tAdded this game to the sales DB with id: " . $sale_id . ".\n";

				$games .= $game->{'plainTitle'} . '<br />';

				$email = 1;
			}

			// if we already have it, just set the price and % off to the current amount (in-case it's different) or if they now have steam/desura keys
			else
			{
				$timestamp = strtotime($game->saleends);

				$expires = $timestamp;

				$db->sqlquery("UPDATE `game_sales` SET `savings` = ?, `{$currency}` = ?, `{$currency}_original` = ?, `expires` = ?, `imported_image_link` = ?, `drmfree` = 1 WHERE `info` = ? AND `provider_id` = 28", array("$discount% off", $price, $price_original, $expires, $game->imageurl, $title));

				echo "  Updated " .$game->{'plainTitle'} . " with current price and % off.\n";
			}
		} // end of if not free anyway
	} //End of if [linux]
	else
	{
		echo "\tBuggers, this game isn't for linux!\n";
	}
echo "\n"; //Just a bit of white space here.
}

echo "\n\n";//More whitespace, just to make the output look a bit more pretty

/*
if ($email == 1)
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL Contact Us - Itch.io sales added';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "<a href=\"http://www.gamingonlinux.com/sales/\">Sales Page</a> - The auto Itch.io salesman has added<br />$games", $headers);

	echo "Mail sent!";
}*/
echo "End of Itch.io import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

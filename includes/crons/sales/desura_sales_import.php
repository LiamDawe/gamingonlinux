<?php
echo "Desura importer started on " .date('d-m-Y H:m:s'). "\n";

define('path', '/home/gamingonlinux/public_html/');

include(path . 'includes/config.php');

include(path . 'includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'includes/class_core.php');
$core = new core();

$date = strtotime(gmdate("d-n-Y H:i:s"));

$url = 'http://rss.desura.com/games/feed/rss.xml?cache=sale';
if (file_get_contents($url) == true)
{
	// magic
}
else
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach Desura sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	die('Desura XML not available!');
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

// DEBUG
/*
echo '<pre>';
print_r($xml);
echo '</pre>';*/

foreach ($xml->browse->game as $game)
{
	/*
	// for seeing what we have available
	echo '<pre>';
	print_r($game);
	echo '</pre>';
	*/

	echo "* Starting import of ".$game->{'name'}."\n";

	// put Operating Systems into an array so we can loop through the Linux ones
	$os_options = array();
	foreach ($game->{'platforms'}->platform as $os)
	{
		$os_options[] = $os;
	}

	echo "\tThe game is avaible for the following systems: " . implode(', ', $os_options) . "\n";

	// put Operating Systems into an array so we can loop through the Linux ones
	if (in_array("Linux", $os_options))
	{

		//for testing output
		echo "Title: ", $game->{'name'}, "\n";
		echo "URL: ", $game->{'url'}, "\n";

		$counter = 0;
		foreach($game->saleprices->price as $a)
		{
			echo "<br />Prices:<br />";
			foreach($a->attributes() as $b => $c)
			{
				$counter++;
				//echo $game->saleprices->price . "<br />";

				if ($counter == 1)
				{
					$usd = $game->saleprices->price[0];
					echo "Test USD:" . $usd;
				}

				if ($counter == 3)
				{
					$eur = $game->saleprices->price[2];
					echo "Test EUR" . $eur;
				}

				if ($counter == 4)
				{
					$gbp = $game->saleprices->price[3];
					echo "Test GBP" . $gbp;
				}
			}
		}

		$counter = 0;
		foreach($game->prices->price as $a)
		{
			echo "Original Price: " . $c . " ";
			foreach($a->attributes() as $b => $c)
			{
				$counter++;
				echo $game->prices->price . "<br />";

				if ($counter == 1)
				{
					$usd_original = $game->prices->price[0];
				}

				if ($counter == 3)
				{
					$eur_original = $game->prices->price[2];
				}

				if ($counter == 4)
				{
					$gbp_original = $game->prices->price[3];
				}
			}
		}

		echo "% off: ", $game->{'salepercent'}, "%\n";
		echo "<br />==================================================<br />";

		$title = $game->{'name'};
		$discount = $game->{'salepercent'};

		// search if that title exists
		$db->sqlquery("SELECT `info`, `provider_id` FROM `game_sales` WHERE `info` = ?", array($game->{'name'}));

		// if it does exist, make sure it's not from desura already
		$check = 1;
		if ($db->num_rows() >= 1)
		{
			while ($test = $db->fetch())
			{
				// set the check to 0 as it already exists from this website
				if ($test['provider_id'] == 2)
				{
					$check = 0;

				}
			}


			// tell the outcome
			if ($check == 0)
			{
				echo "\tI already know about this game, and Desura told me about it\n";
			}

			else
			{
				echo "\tI already know about this game, however Desura wasn't the one who told me about it\n";
			}
		}

		else
		{
			echo "\tI didn't know about this game before.\n";
		}

		$expires = strtotime($game->datesaleend)+3600;

		// we need to add it as we didn't find it from indiegamestand
		if ($check == 1)
		{
			$db->sqlquery("INSERT INTO `game_sales` SET `info` = ?, `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 2, `savings` = ?, `pounds` = ?, `pounds_original` = ?, `dollars` = ?, `dollars_original` = ?, `euros` = ?, `euros_original` = ?, `desura` = 1, `expires` = ?, `imported_image_link` = ?", array($title, $game->{'url'}, $core->date, "$discount% off", $gbp, $gbp_original, $usd, $usd_original, $eur, $eur_original, $expires, $game->{logo}));

			$sale_id = $db->grab_id();

			echo "\tAdded this game to the sales DB with id: " . $sale_id . ".\n";

			$games .= $game->{'name'} . '<br />';

			$email = 1;
		}

		// if we already have it, just set the price and % off to the current amount (in-case it's different) or if they now have steam/desura keys
		else
		{
			$db->sqlquery("UPDATE `game_sales` SET `savings` = ?, `pounds` = ?, `pounds_original` = ?, `dollars` = ?, `dollars_original` = ?, `euros` = ?, `euros_original` = ?,`desura` = 1, `expires` = ?, `imported_image_link` = ? WHERE `info` = ? AND `provider_id` = 2", array("$discount% off", $gbp, $gbp_original, $usd, $usd_original, $eur, $eur_original, $expires, $game->{'logo'}, $title));

			echo "  Updated " .$game->{'name'} . " with current information.\n";
		}
	} //End of if [linux]
	else
	{
		echo "\tBuggers, this game isn't for linux!\n";
	}
echo "\n"; //Just a bit of white space here.
}

echo "\n\n";//More whitespace, just to make the output look a bit more pretty

if ($email == 1)
{
	// multiple recipients
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL Contact Us - Desura sales added';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "<a href=\"http://www.gamingonlinux.com/sales/\">Sales Page</a> - The auto Desura salesman has added<br />$games", $headers);

	echo "Mail sent!";
}
echo "End of Desura import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

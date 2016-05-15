<?php
// IF THEY CHANGE THE API URL: F12 in chrome -> network tab, go to the store and click Linux, the url will appear
echo "Humble Store importer started on " .date('d-m-Y H:m:s'). "\n";

define('path', '/home/gamingonlinux/public_html/');

include(path . 'includes/config.php');

include(path . 'includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'includes/class_core.php');
$core = new core();

include(path . 'includes/curl_data.php');

$date = strtotime(gmdate("d-n-Y H:i:s"));

//Their API endpoint
$api_endpoint = "https://www.humblebundle.com/store/api";

//Only do one call to the API, no need to make 2 HTTP requests to their servers just so see if it's alive ~ Piratelv
// A . combines 2 strings with each other
$json = getCurlData($api_endpoint."?request=1&page_size=20&notabot=true&page=0&platform=linux");
if ($json == false)
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach Humble sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	die('Humble JSON not available!');
}

$email = 0;

$games = '';

$stop = 0;

$i = 0;

do
{
	$json = json_decode(getCurlData($api_endpoint."?request=1&page_size=20&notabot=true&page=$i&platform=linux"));

	//echo '<pre>';
	//var_dump($json->results);

	if (empty($json->results))
	{
		$stop = 1;
	}

	else if ($json->num_results != 0)
	{
		$use_sale = 0;
		foreach ($json->results as $game)
		{
			//var_dump($game->icon_dict->download);
			//echo '<pre>';
			//var_dump($game);

			if (isset($game->platforms))
			{
				if (in_array('linux', $game->platforms))
				{
					$use_sale = 1;
				}
			}

			if ($use_sale == 1)
			{
				/* NOT CURRENTLY USING IT
				// ADD IT TO THE GAMES DATABASE, FOR FUTURE USE
				$db->sqlquery("SELECT `local_id`, `name` FROM `game_list` WHERE `name` = ?", array($game->human_name));
				if ($db->num_rows() == 0)
				{
					$db->sqlquery("INSERT INTO `game_list` SET `name` = ?", array($game->human_name));
				}

				$db->sqlquery("UPDATE `game_list` SET `on_sale` = 1 WHERE `name` = ?", array($game->human_name));*/

				if (isset($game->current_price))
				{
					if ($game->current_price[0] != $game->full_price[0])
					{

						echo '<img src="' . $game->storefront_icon .' " alt=""/><br />Link: https://www.humblebundle.com/store/p/' . $game->machine_name . '<br />' .  $game->human_name . ' Current Price: $' . $game->current_price[0]  .  ', Full Price: $' . $game->full_price[0] . '<br />';

						$website = 'https://www.humblebundle.com/store/p/' . $game->machine_name;
						$drm_free = 0;
						$steam = 0;

						if (in_array('download', $game->delivery_methods))
						{
							$drm_free = 1;
						}

						if (in_array('steam', $game->delivery_methods))
						{
							$steam = 1;
						}

						echo 'DRM Free: ' . $drm_free . '<br />';
						echo 'Steam Key: ' . $steam . '<br />';

						$sale_end = $game->sale_end+3600;

						// need to check if we already have it to insert it
						// search if that title exists
						$db->sqlquery("SELECT `info` FROM `game_sales` WHERE `info` = ? AND `provider_id` = 11", array($game->human_name));

						// if it does exist, make sure it's not from humble already
						if ($db->num_rows() == 0)
						{
							$db->sqlquery("INSERT INTO `game_sales` SET `info` = ?, `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 11, `dollars` = ?, `dollars_original` = ?, `steam` = ?, `drmfree` = ?, `expires` = ?", array($game->human_name, $website, core::$date, $game->current_price[0], $game->full_price[0], $steam, $drm_free, $sale_end));

							$sale_id = $db->grab_id();

							echo "\tAdded this game to the sales DB with id: " . $sale_id . ".\n";

							$games .= $game->human_name . '<br />';

							$email = 1;
						}

						// if we already have it, just update it
						else
						{
							$db->sqlquery("UPDATE `game_sales` SET `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 11, `dollars` = ?, `dollars_original` = ?, `steam` = ?, `drmfree` = ?, `expires` = ? WHERE `provider_id` = 11 AND info = ?", array($website, core::$date, $game->current_price[0], $game->full_price[0], $steam, $drm_free, $sale_end, $game->human_name));

							echo "Updated {$game->human_name} with the latest information<br />";
						}
					}
				}
			}
			$use_sale = 0;
		}
	}
	$i++;
} while ($stop == 0);

/*
if ($email == 1)
{
	// multiple recipients
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL Contact Us - Humble sales added';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "<a href=\"http://www.gamingonlinux.com/sales/\">Sales Page</a> - The auto Humble Store salesman has added<br />$games", $headers);

	echo "Mail sent!";
}*/

// update the time it was last run
$db->sqlquery("UPDATE `config` SET `data_value` = ? WHERE `data_key` = 'humble_import_lastrun'", array(core::$date));

echo "End of Humble Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

<?php
echo "Humble Store importer started on " .date('d-m-Y H:m:s'). "\n";

define('path', '/home/gamingonlinux/public_html/');

include(path . 'includes/config.php');

include(path . 'includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'includes/class_core.php');
$core = new core();

$date = strtotime(gmdate("d-n-Y H:i:s"));

//Their API endpoint
$api_endpoint = "https://www.humblebundle.com/store/api";

//Only do one call to the API, no need to make 2 HTTP requests to their servers just so see if it's alive ~ Piratelv
// A . combines 2 strings with each other
$json = file_get_contents($api_endpoint."?request=3&page_size=20&sort=discount&page=0&platform=linux");
if ($json == false)
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach Humble sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	die('Humble XML not available!');
}

$email = 0;

$games = '';

$stop = 0;

$i = 0;

do
{
	$json = json_decode(file_get_contents($api_endpoint."?request=3&page_size=20&sort=discount&page=$i&platform=linux"), true);

	if (empty($json['results']))
	{
		$stop = 1;
	}

	else if (!empty($json['results']))
	{
		foreach ($json['results'] as $game)
		{
			if (in_array('linux', $game['platforms']))
			{
				// ADD IT TO THE GAMES DATABASE, FOR FUTURE USE
				$db->sqlquery("SELECT `local_id`, `name` FROM `game_list` WHERE `name` = ?", array($game['human_name']));
				if ($db->num_rows() == 0)
				{
					$db->sqlquery("INSERT INTO `game_list` SET `name` = ?", array($game['human_name']));

					// need to grab it again
					$db->sqlquery("SELECT `name`, `local_id` FROM `game_list` WHERE `name` = ?", array($games['title']));
					$game_list = $db->fetch();
				}

				$db->sqlquery("UPDATE `game_list` SET `on_sale` = 1 WHERE `name` = ?", array($game['human_name']));

				//print_r($game);
				if (isset($game['current_price']))
				{
					if ($game['current_price'][0] != $game['full_price'][0])
					{
						echo '<img src="https://www.humblebundle.com'. $game['storefront_featured_image_small'] .' " alt=""/><br />Link: https://www.humblebundle.com/store/p/' . $game['machine_name'] . '<br />' .  $game['human_name'] . ' Current Price: $' . $game['current_price'][0]  .  ', Full Price: $' . $game['full_price'][0] . '<br />';

						$website = 'https://www.humblebundle.com/store/p/' . $game['machine_name'];
						$image = 'https://www.humblebundle.com' . $game['storefront_featured_image_small'];
						$drm_free = 0;
						$steam = 0;

						if (in_array('download', $game['delivery_methods']))
						{
							$drm_free = 1;
						}

						if (in_array('steam', $game['delivery_methods']))
						{
							$steam = 1;
						}

						echo 'DRM Free: ' . $drm_free . '<br />';
						echo 'Steam Key: ' . $steam . '<br />';

						$sale_end = $game['sale_end']+3600;

						// need to check if we already have it to insert it
						// search if that title exists
						$db->sqlquery("SELECT `info` FROM `game_sales` WHERE `info` = ? AND `provider_id` = 11", array($game['human_name']));

						// if it does exist, make sure it's not from humble already
						if ($db->num_rows() == 0)
						{
							$db->sqlquery("INSERT INTO `game_sales` SET `info` = ?, `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 11, `dollars` = ?, `dollars_original` = ?, `imported_image_link` = ?, `steam` = ?, `drmfree` = ?, `expires` = ?", array($game['human_name'], $website, core::$date, $game['current_price'][0], $game['full_price'][0], $image, $steam, $drm_free, $sale_end));

							$sale_id = $db->grab_id();

							echo "\tAdded this game to the sales DB with id: " . $sale_id . ".\n";

							$games .= $game['human_name'] . '<br />';

							$email = 1;
						}

						// if we already have it, just update it
						else
						{
							$db->sqlquery("UPDATE `game_sales` SET `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 11, `dollars` = ?, `dollars_original` = ?, `imported_image_link` = ?, `steam` = ?, `drmfree` = ?, `expires` = ? WHERE `provider_id` = 11 AND info = ?", array($website, core::$date, $game['current_price'][0], $game['full_price'][0], $image, $steam, $drm_free, $sale_end, $game['human_name']));

							echo "Updated {$game['human_name']} with the latest information<br />";
						}
					}
				}
			}
		}

		//print_r($json);
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
echo "End of Humble Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

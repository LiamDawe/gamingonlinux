<?php
ini_set('display_errors',1);

define('path', '/home/gamingonlinux/public_html/');

include(path . 'includes/config.php');

include(path . 'includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'includes/class_core.php');
$core = new core();

include(path . 'includes/curl_data.php');

$removed_counter = 0;
$games = '';

$game_ids = array();

//
// remove Humble Store sales that are no longer listed (for ones that had no end date)
//
echo "Starting Humble Store remover<br />\r\n";

// Their API endpoint
$api_endpoint = "https://www.humblebundle.com/store/api";

$on_sale = array();

$stop = 0;

$i = 0;

//Only do one call to the API, no need to make 2 HTTP requests to their servers just so see if it's alive ~ Piratelv
// A . combines 2 strings with each other
$json = getCurlData($api_endpoint."?request=1&page_size=20&notabot=true&page=0&platform=linux");
if ($json == true)
{
	do
	{
		$json = json_decode(getCurlData($api_endpoint."?request=1&page_size=20&notabot=true&page=$i&platform=linux"));

		if (empty($json->results))
		{
			$stop = 1;
		}

		else if ($json->num_results != 0)
		{
			$use_sale = 0;
			foreach ($json->results as $game)
			{
				if (isset($game->platforms))
				{
					if (in_array('linux', $game->platforms))
					{
						$use_sale = 1;
					}
				}

				if ($use_sale = 1)
				{
					if (isset($game->current_price))
					{
						if ($game->current_price[0] != $game->full_price[0])
						{
							$on_sale[] = $game->human_name;
						}
					}
				}
			}
		}
		$i++;
	} while ($stop == 0);

	// now search our database for all humble sales and match them up with current sales, if it doesn't match then it's no longer on sale so remove it!
	$db->sqlquery("SELECT `id`, `info` FROM `game_sales` WHERE `provider_id` = 11 AND `accepted` = 1");
	$currently_in_db = $db->fetch_all_rows();

	//echo 'CURRENTLY ON SALE<br />';
	//print_r($on_sale);
	//echo '<br />IN OUR DB<br />';
	//print_r($currently_in_db);

	$removed_counter_humble = 0;

	foreach ($currently_in_db as $value=> $in_db)
	{
		if (!in_array($in_db['info'], $on_sale))
		{
			$db->sqlquery("SELECT `has_screenshot`,`screenshot_filename` FROM `game_sales` WHERE `info` = ? AND `provider_id` = 11", array($in_db['info']));
			$get_ss = $db->fetch();
			if ($get_ss['has_screenshot'] == 1)
			{
				unlink('/home/gamingonlinux/public_html/uploads/sales/' . $get_ss['screenshot_filename']);
			}

			$db->sqlquery("DELETE FROM `game_sales` WHERE `info` = ? AND `provider_id` = 11", array($in_db['info']));

			echo $in_db['info'] . " Removed from database \n";

			$removed_counter_humble++;
			$removed_counter++;
			$games .= " {$in_db['info']} from Humble<br />";
			$game_ids[] = $in_db['id'];
		}
	}
	if ($removed_counter_humble == 0)
	{
		echo "No games to remove from Humble<br />\r\n";
	}
}
else
{
	echo "Couldn't access the Humble feed.<br />\r\n";
}

// remove any admin notifications for ended sales that have been removed
$game_ids_removed = implode(',', $game_ids);
if (!empty($game_ids_removed))
{
	$db->sqlquery("DELETE FROM `admin_notifications` WHERE `sale_id` IN (?) AND `sale_id` != 0", array($game_ids_removed));
}
?>

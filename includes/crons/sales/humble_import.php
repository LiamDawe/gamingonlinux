<?php
// IF THEY CHANGE THE API URL: F12 in chrome -> network tab, find API mentions
echo "Humble Store importer started on " . date('d-m-Y H:m:s'). "\n";

$doc_root = dirname( dirname( dirname( dirname(__FILE__) ) ) );

// we dont need the whole bootstrap
require $doc_root . '/includes/loader.php';
include $doc_root . '/includes/config.php';
$dbl = new db_mysql();
$core = new core($dbl);

$date = strtotime(gmdate("d-n-Y H:i:s"));

//Their API endpoint
$api_endpoint = "https://www.humblebundle.com/store/api/";

$json = core::file_get_contents_curl($api_endpoint."search?sort=discount&filter=onsale&request=2&page_size=20&page=0&platform[]=linux");
if ($json == false)
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach Humble sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	error_log("Couldn't reach the Humble sales json");
	die('Humble JSON not available!');
}

$email = 0;

$new_games = array();

$games = '';

$stop = 0;

$i = 0;

do
{
	$json = json_decode(core::file_get_contents_curl($api_endpoint."search?sort=discount&filter=onsale&request=2&page_size=20&page=$i&platform[]=linux"));

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
				$sane_name = preg_replace("/(™|®|©|&trade;|&reg;|&copy;|&#8482;|&#174;|&#169;)/", "", $game->human_name); // remove junk	
				$sane_name = trim($sane_name);

				echo $sane_name."\n";

				if (isset($game->current_price))
				{
					if ($game->current_price[0] != $game->full_price[0])
					{

						echo '<img src="' . $game->featured_image_small .' " alt=""/><br />Link: https://www.humblebundle.com/store/p/' . $game->machine_name . '<br />' .  $game->human_name . ' Current Price: $' . $game->current_price[0]  .  ', Full Price: $' . $game->full_price[0] . '<br />';

						$website = 'https://www.humblebundle.com/store/p/' . $game->machine_name;
					
						// ADD IT TO THE GAMES DATABASE
						$game_list = $dbl->run("SELECT `id` FROM `calendar` WHERE `name` = ?", array($sane_name))->fetch();
			
						if (!$game_list)
						{
							$dbl->run("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `on_sale` = 1", array($sane_name, date('Y-m-d')));// they don't give the release date, just add in today's date, we can fix manually later if/when we need to
			
							// need to grab it again
							$game_list = $dbl->run("SELECT `id` FROM `calendar` WHERE `name` = ?", array($sane_name))->fetch();
						}
						else
						{
							$dbl->run("UPDATE `calendar` SET `on_sale` = 1 WHERE `id` = ?", array($sane_name));
						}
			
						$on_sale[] = $game_list['id'];

						$check_sale = $dbl->run("SELECT 1 FROM `sales` WHERE `game_id` = ? AND `store_id` = 4", array($game_list['id']))->fetch();
						
						// if it does exist, make sure it's not from GOG already
						if (!$check_sale)
						{
							$dbl->run("INSERT INTO `sales` SET `game_id` = ?, `store_id` = 4, `accepted` = 1, `sale_dollars` = ?, `original_dollars` = ?, `link` = ?", array($game_list['id'], $game->current_price[0], $game->full_price[0], $website));
						
							$sale_id = $dbl->new_id();
						
							echo "\tAdded ".$sane_name." to the sales DB with id: " . $sale_id . ".\n";
						}
					}
				}
			}
			$use_sale = 0;
		}
	}
	$i++;
} while ($stop == 0);

$total_on_sale = count($on_sale);

// remove any not found on sale
if (isset($total_on_sale) && $total_on_sale > 0)
{
	$in  = str_repeat('?,', count($on_sale) - 1) . '?';
	$dbl->run("DELETE FROM `sales` WHERE `game_id` NOT IN ($in) AND `store_id` = 4", $on_sale);
}

echo "End of Humble Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

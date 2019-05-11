<?php
// IF THEY CHANGE THE API URL: F12 in chrome -> network tab, find API mentions
define("APP_ROOT", dirname( dirname( dirname( dirname(__FILE__) ) ) ));

require APP_ROOT . '/includes/bootstrap.php';

$game_sales = new game_sales($dbl, $templating, $user, $core);

echo "Humble Store importer started on " . date('d-m-Y H:m:s'). "\n";

$date = strtotime(gmdate("d-n-Y H:i:s"));

//Their API endpoint
$api_endpoint = "https://www.humblebundle.com/store/api/";

$json = core::file_get_contents_curl($api_endpoint."search?sort=discount&filter=onsale&request=2&page_size=20&page=0&platform[]=linux");
if ($json == false)
{
	$subject = 'GOL ERROR - Cannot reach Humble sales importer';

	$html_message = "Could not reach the importer!";
	$plain_message = "Could not reach the importer!";

	$mail = new mailer($core);
	$mail->sendMail('liamdawe@gmail.com', $subject, $html_message, $plain_message);

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
				$sane_name = $game_sales->clean_title($game->human_name);
				$stripped_title = $game_sales->stripped_title($sane_name);

				echo $sane_name."\n";

				if (isset($game->current_price))
				{
					if ($game->current_price[0] != $game->full_price[0])
					{

						echo '<img src="' . $game->featured_image_small .' " alt=""/><br />Link: https://www.humblebundle.com/store/p/' . $game->machine_name . '<br />' .  $game->human_name . ' Current Price: $' . $game->current_price[0]  .  ', Full Price: $' . $game->full_price[0] . '<br />';

						$website = 'https://www.humblebundle.com/store/p/' . $game->machine_name . '?partner=gamingonlinux';
					
						// ADD IT TO THE GAMES DATABASE
						$game_list = $dbl->run("SELECT `id`, `stripped_name` FROM `calendar` WHERE `name` = ?", array($sane_name))->fetch();

						// check for a parent game, if this game is also known as something else, and the detected name isn't the one we use
						$check_dupes = $dbl->run("SELECT `real_id` FROM `item_dupes` WHERE `name` = ?", array($sane_name))->fetch();
			
						if (!$game_list && !$check_dupes)
						{
							$dbl->run("INSERT INTO `calendar` SET `name` = ?, `stripped_name` = ?, `date` = ?, `approved` = 1", array($sane_name,$stripped_title, date('Y-m-d')));// they don't give the release date, just add in today's date, we can fix manually later if/when we need to
			
							// need to grab it again
							$game_list = $dbl->run("SELECT `id` FROM `calendar` WHERE `name` = ?", array($sane_name))->fetch();
						}

						$game_id = $game_list['id'];
						if ($check_dupes)
						{
							$game_id = $check_dupes['real_id'];
						}
			
						$on_sale[] = $game_id;

						$check_sale = $dbl->run("SELECT `id`, `sale_dollars` FROM `sales` WHERE `game_id` = ? AND `store_id` = 4", array($game_id))->fetch();
						
						// if it does exist, make sure it's not from HUMBLE already
						if (!$check_sale)
						{
							$dbl->run("INSERT INTO `sales` SET `game_id` = ?, `store_id` = 4, `accepted` = 1, `sale_dollars` = ?, `original_dollars` = ?, `link` = ?", array($game_id, $game->current_price[0], $game->full_price[0], $website));
						
							$sale_id = $dbl->new_id();

							$game_sales->notify_wishlists($game_id);
						
							echo "\tAdded ".$sane_name." to the sales DB with id: " . $sale_id . ".\n";
						}
						else
						{
							// update the current USD price, if it's wrong
							if ($check_sale['sale_dollars'] != $game->current_price[0])
							{
								$dbl->run("UPDATE `sales` SET `sale_dollars` = ? WHERE `id` = ?", array($game->current_price[0], $check_sale['id']));
							}
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

$dbl = NULL;
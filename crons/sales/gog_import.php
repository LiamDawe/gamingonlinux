<?php
error_reporting(-1);

define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/public_html');

require APP_ROOT . '/includes/bootstrap.php';

echo "GOG importer started on " .date('d-m-Y H:m:s'). "\n";

use claviska\SimpleImage;

$date = strtotime(gmdate("d-n-Y H:i:s"));

$url = 'http://www.gog.com/games/feed?format=json&page=1';
if (core::file_get_contents_curl($url) == true)
{
	// magic
}
else
{
	$to = $core->config('contact_email');
	$subject = 'GOL ERROR - Cannot reach GOG sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL - GOG Sales Importer <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	error_log("Couldn't reach the GOG sales XML");
	die('GOG XML not available!');
}

$on_sale = [];

$urlMask = 'http://www.gog.com/games/feed?format=json&page=%d';

$page = 0;
do {
	$url = sprintf($urlMask, ++$page);
	$array = json_decode(core::file_get_contents_curl($url), true);
	$count = count($array['games']);
	if ($count > 0)
	{
		printf("Page #%d: %d product(s)\n", $page, $count);

		// DEBUG: Check whole array
		//print_r($array['games']);

		foreach ($array['games'] as $games)
		{
			//echo $games['title'] . "\n";
			//echo "Linux Support: " . $games['linux_compatible'] . "\n";

			if ($games['linux_compatible'] == 1 && $games['discount_raw'] > 0)
			{
				$website = $games['short_link'] . '/?pp=b2a10a6c3dcadb10c8ffd734c1bab896d55cf0ec';
				$website2 = $games['short_link'] . '?pp=b2a10a6c3dcadb10c8ffd734c1bab896d55cf0ec';
				$image = $games['img_cover'];
				$current_price =  $games['price_raw']/100; //LoL what, ['price_raw'] is the discounted price
				$original_price = round( $games['price_raw'] / (100 - $games['discount']), 2);  //Looks like ['discount'] is a % discount

				//DAFUQ $games['discount'] is off by 1%, php is high or something

				// var_dump( (100 - $games['discount']), $games['discount'] );

				// what the fuck GOG, seriously, stop re-ordering the fucking "The"
				// Sometimes you do "The Name", sometimes you do "Name, The" - make your bloody mind up
				if (strpos($games['title'], ', The - The') !== false)
				{
					$games['title'] = str_replace(', The - The', ' - The', $games['title']);
					$games['title'] = 'The ' . $games['title'];
				}
				if (strpos($games['title'], ', The') !== false)
				{
					$games['title'] = str_replace(', The', '', $games['title']);
					$games['title'] = 'The ' . $games['title'];
				}

				$games['title'] = $gamedb->clean_title($games['title']);

				/*echo $games['title'] . "\n";
				echo "* Original Price: $". $original_price ."\n";
				echo "* Price Now: $" . $current_price . "\n";*/

				// give it a proper url we can use, gog don't use a properly url, they just put // at the start - really helpful guys
				$image_clean = str_replace('//', '', $games['img_icon']);
				$image = 'https://' . $image_clean;

				echo $image . '<br />';

				// ADD IT TO THE GAMES DATABASE
				$game_list = $dbl->run("SELECT `id`, `small_picture`, `gog_link` FROM `calendar` WHERE BINARY `name` = ? OR `gog_link` = ? OR `gog_link` = ? OR `gog_link` = ?", array($games['title'], $website, $games['short_link'], $website2))->fetch();

				// check for a parent game, if this game is also known as something else, and the detected name isn't the one we use
				$check_dupes = $dbl->run("SELECT `real_id` FROM `item_dupes` WHERE BINARY `name` = ?", array($games['title']))->fetch();

				if (!$game_list && !$check_dupes)
				{
					$dbl->run("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `gog_link` = ?, `approved` = 1", array($games['title'], $games['original_release_date'], $website));
					$new_id = $dbl->new_id();
					
					$dbl->run("INSERT INTO `itemdb_calendar` SET `item_id` = ?, `store_id` = 5, `release_date` = ?", array($new_id, $games['original_release_date']));

					// need to grab it again
					$game_list = $dbl->run("SELECT `id`, `small_picture`, `gog_link` FROM `calendar` WHERE `name` = ?", array($games['title']))->fetch();

					$game_id = $game_list['id'];
				}

				$game_id = $game_list['id'];
				if ($check_dupes)
				{
					$game_id = $check_dupes['real_id'];
				}

				// if the game list has no picture, grab it and save it
				if ($game_list['small_picture'] == NULL || $game_list['small_picture'] == '')
				{
					$saved_file = $core->config('path') . 'uploads/gamesdb/small/' . $game_id . '.jpg';
					$core->save_image($image, $saved_file);

					// make their image match the sizing of Steam images
					$img = new SimpleImage();
					$img->fromFile($saved_file)->resize(120, 45)->toFile($saved_file);

					$dbl->run("UPDATE `calendar` SET `small_picture` = ? WHERE `id` = ?", [$game_id . '.jpg', $game_id]);
				}

				if ($game_list['gog_link'] == NULL || $game_list['gog_link'] == '')
				{
					$dbl->run("UPDATE `calendar` SET `gog_link` = ? WHERE `id` = ?", array($website, $game_id));
				}

				$on_sale[] = $game_id;

				$check_sale = $dbl->run("SELECT 1 FROM `sales` WHERE `game_id` = ? AND `store_id` = 5", array($game_id))->fetch();

				// if it does exist, make sure it's not from GOG already
				if (!$check_sale)
				{
					$dbl->run("INSERT INTO `sales` SET `game_id` = ?, `store_id` = 5, `accepted` = 1, `sale_dollars` = ?, `original_dollars` = ?, `link` = ?", array($game_id, $current_price, $original_price, $website));

					$sale_id = $dbl->new_id();

					$gamedb->notify_wishlists($game_id);

					//echo "\tAdded ".$games['title']." to the sales DB with id: " . $sale_id . ".\n";
				}
			}
		}
	}
} while ($count > 0);

$total_on_sale = count($on_sale);

// remove any not found on sale
if (isset($total_on_sale) && $total_on_sale > 0)
{
	$in  = str_repeat('?,', count($on_sale) - 1) . '?';
	$dbl->run("DELETE FROM `sales` WHERE `game_id` NOT IN ($in) AND `store_id` = 5", $on_sale);
}

echo "\n\n";
echo "End of GOG import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

$dbl = NULL;
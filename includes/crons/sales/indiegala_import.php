<?php
error_reporting(E_ALL);

define("APP_ROOT", dirname( dirname( dirname( dirname(__FILE__) ) ) ));

require APP_ROOT . '/includes/bootstrap.php';

$game_sales = new game_sales($dbl, $templating, $user, $core);

echo "IndieGala importer started on " .date('d-m-Y H:m:s'). "\n";

$url = 'https://www.indiegala.com/store_games_rss?sale=true';
$get_data = core::file_get_contents_curl($url);
if ($get_data)
{
	// magic
}
else
{
	$to = $core->config('contact_email');
	$subject = 'GOL ERROR - Cannot reach the IndieGala sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	error_log("Couldn't reach the IndieGala sales XML");	
	die('IndieGala XML not available!');
}

$xml = simplexml_load_string($get_data);

$on_sale = [];

for ($i = 1; $i <= $xml->channel->totalPages; $i++) 
{
	if ($i > 1)
	{
		$next_page_url = 'https://www.indiegala.com/store_games_rss?sale=true&page='.$i;
		$get_data = core::file_get_contents_curl($next_page_url);
		$xml = simplexml_load_string($get_data);	
	}

	foreach ($xml->channel->browse->item as $game)
	{
		$on_linux = 0;
		foreach ($game->platforms->platform as $platform)
		{
			//print_r($platform);
			if ($platform == 'Linux')
			{
				$on_linux = 1;
			}
		}
		if ($on_linux == 1)
		{
			$new_title = html_entity_decode($game->title, ENT_QUOTES);
			$stripped_title = $game_sales->stripped_title($new_title);
			$new_title = $game_sales->clean_title($new_title);

			// for seeing what we have available
			/*echo '<pre>';
			print_r($game);
			echo '</pre>';

			//for testing output
			echo 'This is available for Linux!<br />';
			echo "\n* Starting import of ".$new_title."\n";
			echo "URL: ", $game->link, "\n";
			echo "Price USD: ", $game->discountPriceUSD, "\n";
			echo "Original Price: ", $game->priceUSD, "\n";*/

			$usd_sale_price = NULL;
			$usd_normal_price = NULL;
			if ($game->discountPriceUSD > 0 && $game->priceUSD > 0)
			{
				$usd_sale_price = $game->discountPriceUSD;
				$usd_normal_price = $game->priceUSD;
			}
		
			// first check it exists based on the normal name
			$game_list = $dbl->run("SELECT `id` FROM `calendar` WHERE `name` = ?", array($new_title))->fetchOne();

			// check for a parent game, if this game is also known as something else, and the detected name isn't the one we use
			$check_dupes = $dbl->run("SELECT `real_id` FROM `item_dupes` WHERE `name` = ?", array($new_title))->fetch();
					
			if (!$game_list && !$check_dupes)
			{
				// not found, checked the stripped name
				$game_list_stripped = $dbl->run("SELECT `id` FROM `calendar` WHERE `stripped_name` = ?", array($stripped_title))->fetchOne();
				if (!$game_list_stripped)
				{
					$dbl->run("INSERT INTO `calendar` SET `name` = ?, `stripped_name` = ?, `date` = ?, `approved` = 1", array($new_title, $stripped_title, date('Y-m-d'))); // they don't give the release date, just add in today's date, we can fix manually later if/when we need to
		
					$game_id = $dbl->new_id();
				}
				else
				{
					$game_id = $game_list_stripped;
				}
			}
			else
			{
				$game_id = $game_list;
				if ($check_dupes)
				{
					$game_id = $check_dupes['real_id'];
				}
			}
					
			$on_sale[] = $game_id;
			
			$check_sale = $dbl->run("SELECT `id`, `sale_dollars` FROM `sales` WHERE `game_id` = ? AND `store_id` = 3", array($game_id))->fetch();
		
			// all sorted out - insert into the sales database
			if (!$check_sale)
			{
				$dbl->run("INSERT INTO `sales` SET `game_id` = ?, `store_id` = 3, `accepted` = 1, `sale_dollars` = ?, `original_dollars` = ?, `sale_pounds` = ?, `original_pounds` = ?, sale_euro = ?, `original_euro` = ?, `link` = ?", array($game_id, $usd_sale_price, $usd_normal_price, $game->discountPriceGBP, $game->priceGBP, $game->discountPriceEUR, $game->priceEUR, $game->link));
					
				$sale_id = $dbl->new_id();

				$game_sales->notify_wishlists($game_id);
					
				echo "\tAdded ".$new_title." to the sales DB with id: " . $sale_id . ".\n";
			}
			else
			{
				// update prices if they're wrong
				if ($check_sale['sale_dollars'] != $usd_sale_price)
				{
					$dbl->run("UPDATE `sales` SET `sale_dollars` = ? WHERE `id` = ?", array($usd_sale_price, $check_sale['id']));
				}
			}
		}
		echo "\n"; //Just a bit of white space here.
	}
}

$total_on_sale = count($on_sale);

// remove any not found on sale
if (isset($total_on_sale) && $total_on_sale > 0)
{
	$in  = str_repeat('?,', count($on_sale) - 1) . '?';
	$dbl->run("DELETE FROM `sales` WHERE `game_id` NOT IN ($in) AND `store_id` = 3", $on_sale);
}

echo "End of IndieGala import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

$dbl = NULL;
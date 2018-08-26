<?php
// http://simplehtmldom.sourceforge.net/
include('simple_html_dom.php');

define("APP_ROOT", dirname( dirname( dirname( dirname(__FILE__) ) ) ));

require APP_ROOT . '/includes/bootstrap.php';

$game_sales = new game_sales($dbl, $templating, $user, $core);

echo "Steam Store importer started on " .date('d-m-Y H:m:s'). "\n";

$currencies = array(1 => ['key' => "us", 'sql' => 'dollars'], 2 => ['key' => "fr", 'sql' => 'euro'], 3 => ['key' => "gb", 'sql' => 'pounds']);

// get last currency we looped through
$cron_info = $dbl->run("SELECT `data_currency` FROM `crons` WHERE `name` = 'steam_sales_import'")->fetch();

if ($cron_info['data_currency'] == NULL || $cron_info['data_currency'] == '')
{
	$currency_id = 1;
}
else if ($cron_info['data_currency'] <= 3)
{
	$currency_id = $cron_info['data_currency'];
}

echo 'Currency ID: ' . $currency_id . "\n";

$on_sale = [];

$sale_price_field = 'sale_'.$currencies[$currency_id]['sql'];
$original_price_field = 'original_'.$currencies[$currency_id]['sql'];

$page = 1;
$stop = 0;

// only SteamOS/Linux (Games/DLC/Hardware/Bundles) NOT videos/movies
$url = "http://store.steampowered.com/search/results?category1=998%2C994%2C21%2C993%2C996&os=linux&specials=1&cc=".$currencies[$currency_id]['key']."&page=";

do
{
	$html = file_get_html($url . $page);

	$get_sales = $html->find('a.search_result_row');

	if (empty($get_sales))
	{
		$stop = 1;
	}
	else
	{
		foreach($get_sales as $element)
		{
			$link = $element->href;

			$title = $game_sales->clean_title($element->find('span.title', 0)->plaintext);	
			$title = html_entity_decode($title); // as we are scraping an actual html page, make it proper for the database
			$stripped_title = $game_sales->stripped_title($title);
			echo $title . "\n";

			$image = $element->find('div.search_capsule img', 0)->src;
			echo $image . "\n";

			$clean_release_date = NULL;
			$release_date_raw = $element->find('div.search_released', 0)->plaintext;
			echo 'Raw release date: ' . $release_date_raw . "\n";
			$trimmed_date = trim($release_date_raw);	
			$remove_comma = str_replace(',', '', $trimmed_date);
			$parsed_release_date = strtotime($remove_comma);
			// so we can get rid of items that only have the year nice and simple
			$length = strlen($remove_comma);
			$parsed_release_date = date("Y-m-d", $parsed_release_date);
			$has_day = DateTime::createFromFormat('F Y', $remove_comma);
				
			if ($parsed_release_date != '1970-01-01' && $length != 4 && $has_day == FALSE)
			{
				$clean_release_date = $parsed_release_date;
				echo 'Cleaned release date: ' . $clean_release_date . "\n";
			}

			foreach ($element->find('div.discounted') as $price)
			{
				$prices = trim($price->plaintext);
				$prices = explode(' ', $prices);

				$number_format_original = preg_replace("/[^0-9,.]/", "", $prices[0]); // numbers only
				$original_price = trim($number_format_original); // make sure no whitespace
					
				if (trim($prices[1]) == 'Free')
				{
					$price_now = 0;
				}
				else
				{
					$number_format = preg_replace("/[^0-9,.]/", "", $prices[1]); // numbers only
					$price_now = trim($number_format); // make sure no whitespace
				}

				// deal with comma instead of decimal for euro
				if ($currencies[$currency_id]['key'] == 'fr')
				{
					$original_price = str_replace(',','.', $original_price);
					$price_now = str_replace(',','.', $price_now);
				}

				echo 'Original price: ' . $original_price . ' ' . $currencies[$currency_id]['key'] . "\n";
				echo 'Price now: ' . $price_now  . ' ' . $currencies[$currency_id]['key'] . "\n";

				$bundle = 0;
				$steam_id = NULL;
				if (strpos($link, '/app/') !== false) 
				{
					$steam_id = preg_replace('~http:\/\/store\.steampowered\.com\/app\/([0-9]*)\/.*~', '$1', $link);
				}

				if (strpos($link, '/sub/') !== false) 
				{
					$bundle = 1;
				}

				echo 'SteamID: ' . $steam_id . "\n";

				// ADD IT TO THE GAMES DATABASE
				$select_sql = "SELECT `id`, `small_picture`, `steam_id`, `bundle`, `date`, `stripped_name` FROM `calendar` WHERE `name` = ?";
				$game_list = $dbl->run($select_sql, array($title))->fetch();

				// check for a parent game, if this game is also known as something else, and the detected name isn't the one we use
				$check_dupes = $dbl->run("SELECT `real_id` FROM `item_dupes` WHERE `name` = ?", array($title))->fetch();
				
				if (!$game_list && !$check_dupes)
				{
					$dbl->run("INSERT INTO `calendar` SET `name` = ?, `stripped_name` = ?, `date` = ?, `steam_link` = ?, `steam_id` = ?, `bundle` = ?, `approved` = 1", array($title, $stripped_title, $clean_release_date, $link, $steam_id, $bundle));
				
					// need to grab it again
					$game_list = $dbl->run($select_sql, array($title))->fetch();
				
					$game_id = $game_list['id'];
				}
				else
				{
					$game_id = $game_list['id'];
					if ($check_dupes)
					{
						$game_id = $check_dupes['real_id'];
					}
				
					$dbl->run("UPDATE `calendar` SET `steam_link` = ? WHERE `id` = ?", array($link, $game_id));
				}

				// if the game list has no picture, grab it and save it
				if ($game_list['small_picture'] == NULL || $game_list['small_picture'] == '')
				{
					$saved_file = $core->config('path') . 'uploads/gamesdb/small/' . $game_id . '.jpg';
					$core->save_image($image, $saved_file);
					$dbl->run("UPDATE `calendar` SET `small_picture` = ? WHERE `id` = ?", [$game_id . '.jpg', $game_id]);
				}

				// if it has no steam_id, give it one
				if ($game_list['steam_id'] == NULL || $game_list['steam_id'] == '')
				{
					$dbl->run("UPDATE `calendar` SET `steam_id` = ? WHERE `id` = ?", [$steam_id, $game_id]);
				}

				// if we haven't checked if it's a bundle yet
				if ($game_list['bundle'] == NULL || $game_list['bundle'] == '')
				{
					$dbl->run("UPDATE `calendar` SET `bundle` = ? WHERE `id` = ?", [$bundle, $game_id]);
				}

				// if it has no date
				if ($game_list['date'] == NULL || $game_list['date'] == '')
				{
					$dbl->run("UPDATE `calendar` SET `date` = ? WHERE `id` = ?", [$clean_release_date, $game_id]);
				}

				// if the name hasn't been stripped for comparisons yet (older data)
				if ($game_list['stripped_name'] == NULL || $game_list['stripped_name'] == '')
				{
					$dbl->run("UPDATE `calendar` SET `stripped_name` = ? WHERE `id` = ?", [$stripped_title, $game_id]);
				}
				
				if (!in_array($game_id, $on_sale))
				{
					$on_sale[] = $game_id;
				}
				
				$check_sale = $dbl->run("SELECT `id` FROM `sales` WHERE `game_id` = ? AND `store_id` = 6", array($game_id))->fetchOne();
				
				// if it does exist, make sure it's not from Steam already
				if (!$check_sale)
				{
					$dbl->run("INSERT INTO `sales` SET `game_id` = ?, `store_id` = 6, `accepted` = 1, `$sale_price_field` = ?, `$original_price_field` = ?, `link` = ?", array($game_id, $price_now, $original_price, $link));
				
					$sale_id = $dbl->new_id();

					$game_sales->notify_wishlists($game_id);
				}
				// update it with the current info
				else
				{
					$dbl->run("UPDATE `sales` SET `$sale_price_field` = ?, `$original_price_field` = ? WHERE `id` = ?", [$price_now, $original_price, $check_sale]);
				}
			}
		}
		$page++;
	}
} while ($stop == 0);

$total_on_sale = count($on_sale);

// remove any not found on sale that were added at least 12 hours ago (to prevent removing some that randomly don't show up)
if (isset($total_on_sale) && $total_on_sale > 0)
{
	$in  = str_repeat('?,', count($on_sale) - 1) . '?';
	$dbl->run("DELETE FROM `sales` WHERE `game_id` NOT IN ($in) AND `store_id` = 6 AND `date_added` < DATE_SUB(NOW(), INTERVAL 12 HOUR)", $on_sale);
}

echo 'Total on sale: ' . $total_on_sale . "\n";

if ($cron_info['data_currency'] == 3)
{
	$currency_id = 1;
}
else
{
	$currency_id = $currency_id + 1;
}

$dbl->run("UPDATE `crons` SET `last_ran` = ?, `data_currency` = ? WHERE `name` = 'steam_sales_import'", [core::$sql_date_now, $currency_id]);

echo "End of Steam Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

$dbl = NULL;
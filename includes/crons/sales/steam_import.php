<?php
// TODO
// ?cc=us for dollars, lets get pounds working first eh boyos!

// http://simplehtmldom.sourceforge.net/
include('simple_html_dom.php');

$doc_root = dirname( dirname( dirname( dirname(__FILE__) ) ) );

// we dont need the whole bootstrap
require $doc_root . '/includes/loader.php';
include $doc_root . '/includes/config.php';
$dbl = new db_mysql();
$core = new core($dbl);

echo "Steam Store importer started on " .date('d-m-Y H:m:s'). "\n";

$page = 1;
$stop = 0;
$titles = array();

$url = "http://store.steampowered.com/search/results?os=linux&specials=1&snr=1_7_7_204_7&page=";

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
			//echo $element->href . '<br />';

			$title = trim($element->find('span.title', 0)->plaintext);
			$title = preg_replace("/(™|®|©|&trade;|&reg;|&copy;|&#8482;|&#174;|&#169;)/", "", $title); // remove junk		
			$title = html_entity_decode($title); // as we are scraping an actual html page, make it proper for the database	
			$titles[] = $title;
			echo $title . '<br />';

			$image = $element->find('div.search_capsule img', 0)->src;
			echo $image . '<br />';

			foreach ($element->find('div.discounted') as $price)
			{
				var_dump($price->plaintext);
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

				echo 'Original price: ' . $original_price . '<br />';
				echo 'Price now: ' . $price_now  . '<br />';

				// ADD IT TO THE GAMES DATABASE
				$game_list = $dbl->run("SELECT `id`, `also_known_as`, `small_picture` FROM `calendar` WHERE `name` = ?", array($title))->fetch();
			
				if (!$game_list)
				{
					$dbl->run("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `steam_link` = ?, `on_sale` = 1", array($title, date('Y-m-d'), $link));
			
					// need to grab it again
					$game_list = $dbl->run("SELECT `id`,`small_picture` FROM `calendar` WHERE `name` = ?", array($title))->fetch();
			
					$game_id = $game_list['id'];
				}
				else
				{
					// check for a parent game, if this game is also known as something else, and the detected name isn't the one we use
					$game_id = $game_list['id'];
					if ($game_list['also_known_as'] != NULL && $game_list['also_known_as'] != 0)
					{
						$game_id = $game_list['also_known_as'];
					}
			
					$dbl->run("UPDATE `calendar` SET `on_sale` = 1 WHERE `id` = ?", array($game_id));
				}

				// if the game list has no picture, grab it and save it
				if ($game_list['small_picture'] == NULL || $game_list['small_picture'] == '')
				{
					$saved_file = $core->config('path') . 'uploads/sales/' . $game_list['id'] . '.jpg';
					$core->save_image($image, $saved_file);
					$dbl->run("UPDATE `calendar` SET `small_picture` = ? WHERE `id` = ?", [$saved_file, $game_list['id']]);
				}
			
				$on_sale[] = $game_id;
			
				$check_sale = $dbl->run("SELECT `id` FROM `sales` WHERE `game_id` = ? AND `store_id` = 6", array($game_id))->fetchOne();
			
				// if it does exist, make sure it's not from Steam already
				if (!$check_sale)
				{
					$dbl->run("INSERT INTO `sales` SET `game_id` = ?, `store_id` = 6, `accepted` = 1, `sale_dollars` = ?, `original_dollars` = ?, `link` = ?", array($game_id, $price_now, $original_price, $link));
			
					$sale_id = $dbl->new_id();
			
					//echo "\tAdded ".$games['title']." to the sales DB with id: " . $sale_id . ".\n";
				}
				// update it with the current info
				else
				{
					$dbl->run("UPDATE `sales` SET `sale_dollars` = ?, `original_dollars` = ? WHERE `id` = ?", [$price_now, $original_price, $check_sale]);
				}
			}
		}
		$page++;
	}
} while ($stop == 0);

$total_on_sale = count($on_sale);

// remove any not found on sale
$in  = str_repeat('?,', count($on_sale) - 1) . '?';
$dbl->run("DELETE FROM `sales` WHERE `game_id` NOT IN ($in) AND `store_id` = 6", $on_sale);

echo 'Total on sale: ' . $total_on_sale . "\n";

$dbl->run("UPDATE `crons` SET `last_ran` = ?, `data` = ? WHERE `name` = 'steam_sales'", [core::$sql_date_now, $total_on_sale]);

echo "End of Steam Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/public_html');
define("THIS_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/crons');

// http://simplehtmldom.sourceforge.net/
include(THIS_ROOT . '/simple_html_dom.php');

require APP_ROOT . '/includes/cron_bootstrap.php';

$game_sales = new game_sales($dbl, $templating = NULL, $user = NULL, $core);

echo "Steam DLC Updater Store importer started on " .date('d-m-Y H:m:s'). "\n";

$updated_list = [];

$page = 1;
$stop = 0;

$url = "http://store.steampowered.com/search/results?sort_by=Released_DESC&tags=-1&category1=21&os=linux&page=1";

do
{
	$html = file_get_html($url . $page);

	echo 'Page: ' . $page . "\r\n";

	$get_games = $html->find('a.search_result_row');

	if (empty($get_games))
	{
		$stop = 1;
	}
	else
	{
		foreach($get_games as $element)
		{
			$link = $element->href;

			$title = $game_sales->clean_title($element->find('span.title', 0)->plaintext);	
			$title = html_entity_decode($title); // as we are scraping an actual html page, make it proper for the database	
			$stripped_title = $game_sales->stripped_title($title);
			echo $title . PHP_EOL;

			$image = $element->find('div.search_capsule img', 0)->src;
			echo $image . PHP_EOL;

			$steam_id = NULL;
			if (strpos($link, '/app/') !== false) 
			{
				$steam_id = preg_replace('~https:\/\/store\.steampowered\.com\/app\/([0-9]*)\/.*~', '$1', $link);
			}
			echo 'steam id is ' . $steam_id;

			$bundle = 0;
			if (strpos($link, '/sub/') !== false || strpos($link, '/bundle/') !== false) 
			{
				$bundle = 1;
			}

			$release_date_raw = $element->find('div.search_released', 0)->plaintext;
			$clean_release_date = $game_sales->steam_release_date($release_date_raw);

			// ADD IT TO THE GAMES DATABASE
			$game_list = $dbl->run("SELECT `id`, `small_picture`, `bundle`, `date`, `stripped_name`, `steam_link`, `is_dlc`, `steam_id` FROM `calendar` WHERE BINARY `name` = ?", array($title))->fetch();

			// check for a parent game, if this game is also known as something else, and the detected name isn't the one we use
			$check_dupes = $dbl->run("SELECT `real_id` FROM `item_dupes` WHERE BINARY `name` = ?", array($title))->fetch();

			// check for name change, insert different name into dupes table and keep original name
			$name_change = $dbl->run("SELECT `id` FROM `calendar` WHERE `steam_id` = ? AND BINARY `name` != ?", array($steam_id, $title))->fetchOne();
			if ($name_change)
			{
				$exists = $dbl->run("SELECT 1 FROM `item_dupes` WHERE `real_id` = ? AND BINARY `name` = ?", array($name_change, $title))->fetchOne();
				if (!$exists)
				{
					$dbl->run("INSERT IGNORE INTO `item_dupes` SET `real_id` = ?, `name` = ?", array($name_change, $title));
				}
			}			

			if ($game_list)
			{
				$game_id = $game_list['id'];
				if ($check_dupes)
				{
					$game_id = $check_dupes['real_id'];
				}
				if ($name_change)
				{
					$game_id = $name_change;
				}

				$updated_list[] = $game_id;

				// update rows as needed that are empty
				$update = 0;
				$sql_updates = array();
				$sql_data = array();
				if ($game_list['steam_link'] == NULL || $game_list['steam_link'] == '')
				{
					$update = 1;
					$sql_updates[] = '`steam_link` = ?';
					$sql_data[] = $link;
				}

				// if the game list has no picture, grab it and save it
				if ($game_list['small_picture'] == NULL || $game_list['small_picture'] == '')
				{
					$update = 1;
					$saved_file = $core->config('path') . 'uploads/gamesdb/small/' . $game_id . '.jpg';
					$core->save_image($image, $saved_file);
					$sql_updates[] = '`small_picture` = ?';
					$sql_data[] = $game_id . '.jpg';
				}

				// if we haven't checked if it's a bundle yet
				if ($game_list['bundle'] == NULL || $game_list['bundle'] == '')
				{
					$update = 1;
					$sql_updates[] = '`bundle` = ?';
					$sql_data[] = $bundle;
				}
				
				// if it has no date
				if ($game_list['date'] == NULL || $game_list['date'] == '')
				{
					$update = 1;
					$sql_updates[] = '`date` = ?';
					$sql_data[] = $clean_release_date;
				}

				// if the name hasn't been stripped for comparisons yet (older data)
				if ($game_list['stripped_name'] == NULL || $game_list['stripped_name'] == '')
				{
					$update = 1;
					$sql_updates[] = '`stripped_name` = ?';
					$sql_data[] = $stripped_title;
				}

				if ($game_list['steam_id'] == NULL || $game_list['steam_id'] == '')
				{
					$update = 1;
					$sql_updates[] = '`steam_id` = ?';
					$sql_data[] = $steam_id;
				}

				// dlc check
				if ($game_list['is_dlc'] == NULL || $game_list['is_dlc'] == 0)
				{
					echo 'Not listed as DLC - updating.' . PHP_EOL;

					$update = 1;
					$sql_updates[] = '`is_dlc` = 1';
				}

				if ($update == 1)
				{
					$sql_data[] = $game_id;
					$dbl->run("UPDATE `calendar` SET " . implode(', ', $sql_updates) . " WHERE `id` = ?", $sql_data);
				}
			}
		}
		// free up memory
		$html->__destruct();
		unset($html);
		$html = null;
	}
	$page++;
} while ($stop == 0);

$total_updated = count($updated_list);

echo 'Total updated: ' . $total_updated . ". Last page: ". $page . "\n";

//$dbl->run("UPDATE `crons` SET `last_ran` = ?, `data` = ? WHERE `name` = 'steam_sales'", [core::$sql_date_now, $total_on_sale]);

echo "End of Steam DLC Updater Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

<?php
define("APP_ROOT", dirname( dirname( dirname( dirname(__FILE__) ) ) ));

// http://simplehtmldom.sourceforge.net/
include(APP_ROOT . '/includes/crons/sales/simple_html_dom.php');

require APP_ROOT . '/includes/bootstrap.php';

require APP_ROOT . '/includes/cron_helpers.php';

echo "Steam Free Games Store importer started on " .date('d-m-Y H:m:s'). "\n";

$new_games = [];

$page = 1;
$stop = 0;

$url = "http://store.steampowered.com/search/results?os=linux&snr=1_7_7_204_7&tags=113&category1=998&supportedlang=english&page=";

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

			$title = clean_title($element->find('span.title', 0)->plaintext);	
			$title = html_entity_decode($title); // as we are scraping an actual html page, make it proper for the database
			$stripped_title = stripped_title($title);	
			echo $title . "\n";

			$image = $element->find('div.search_capsule img', 0)->src;
			echo $image . "\n";

			$bundle = 0;
			if (strpos($link, '/sub/') !== false) 
			{
				$bundle = 1;
			}

			$release_date_raw = $element->find('div.search_released', 0)->plaintext;
			$clean_release_date = steam_release_date($release_date_raw);

			// ADD IT TO THE GAMES DATABASE
			$game_list = $dbl->run("SELECT `id`, `also_known_as`, `small_picture`, `bundle`, `date`, `steam_link`, `stripped_name`, `free_game` FROM `calendar` WHERE `name` = ?", array($title))->fetch();
				
			if (!$game_list)
			{
				$dbl->run("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `steam_link` = ?, `free_game` = 1, `bundle` = ?, `approved` = 1, `stripped_name` = ?", array($title, $clean_release_date, $link, $bundle, $stripped_title));
				
				$new_id = $dbl->new_id();

				$new_games[] = $new_id;

				$saved_file = $core->config('path') . 'uploads/gamesdb/small/' . $new_id . '.jpg';
				$core->save_image($image, $saved_file);
				$dbl->run("UPDATE `calendar` SET `small_picture` = ? WHERE `id` = ?", [$new_id . '.jpg', $new_id]);
			}
			else
			{
				// check for a parent game, if this game is also known as something else, and the detected name isn't the one we use
				$game_id = $game_list['id'];
				if ($game_list['also_known_as'] != NULL && $game_list['also_known_as'] != 0)
				{
					$game_id = $game_list['also_known_as'];
				}

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
					$saved_file = $core->config('path') . 'uploads/gamesdb/small/' . $game_list['id'] . '.jpg';
					$core->save_image($image, $saved_file);
					$sql_updates[] = '`small_picture` = ?';
					$sql_data[] = $game_list['id'] . '.jpg';
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

				// if it has no date
				if ($game_list['free_game'] == NULL || $game_list['free_game'] == 0)
				{
					$update = 1;
					$sql_updates[] = '`free_game` = 1';
				}

				if ($update == 1)
				{
					$sql_data[] = $game_id;
					$dbl->run("UPDATE `calendar` SET " . implode(', ', $sql_updates) . " WHERE `id` = ?", $sql_data);
				}
			}
		}
	}
	$page++;
} while ($stop == 0);

$total_found_new = count($new_games);

echo 'Total new found: ' . $total_found_new . "\n";

//$dbl->run("UPDATE `crons` SET `last_ran` = ?, `data` = ? WHERE `name` = 'steam_sales'", [core::$sql_date_now, $total_on_sale]);

echo "End of Steam Free Games Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

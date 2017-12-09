<?php
define("APP_ROOT", dirname( dirname( dirname( dirname(__FILE__) ) ) ));

// http://simplehtmldom.sourceforge.net/
include(APP_ROOT . '/includes/crons/sales/simple_html_dom.php');

require APP_ROOT . '/includes/bootstrap.php';

$game_sales = new game_sales($dbl, $templating, $user, $core);

echo "Steam Free Games Store importer started on " .date('d-m-Y H:m:s'). "\n";


$new_games = [];

$page = 1;
$stop = 0;

$url = "http://store.steampowered.com/search/results?os=linux&snr=1_7_7_204_7&tags=113&category1=998&supportedlang=english&page=";

do
{
	$html = file_get_html($url . $page);

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
			echo $title . "\n";

			$image = $element->find('div.search_capsule img', 0)->src;
			echo $image . "\n";

			$bundle = 0;
			if (strpos($link, '/app/') !== false) 
			{
				$steam_id = preg_replace('~http:\/\/store\.steampowered\.com\/app\/([0-9]*)\/.*~', '$1', $link);
			}

			if (strpos($link, '/sub/') !== false) 
			{
				$bundle = 1;
				$steam_id = preg_replace('~http:\/\/store\.steampowered\.com\/sub\/([0-9]*)\/.*~', '$1', $link);
			}

			echo 'SteamID: ' . $steam_id . "\n";

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

			// ADD IT TO THE GAMES DATABASE
			$game_list = $dbl->run("SELECT `id`, `also_known_as`, `small_picture`, `steam_id`, `bundle`, `date` FROM `calendar` WHERE `name` = ?", array($title))->fetch();
				
			if (!$game_list)
			{
				$dbl->run("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `steam_link` = ?, `free_game` = 1, `steam_id` = ?, `bundle` = ?, `approved` = 1", array($title, $clean_release_date, $link, $steam_id, $bundle));

				$new_games[] = $game_id;
				
				// need to grab it again
				$game_list = $dbl->run("SELECT `id`,`small_picture`, `steam_id`, `bundle`, `date` FROM `calendar` WHERE `name` = ?", array($title))->fetch();
				
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
				
				$dbl->run("UPDATE `calendar` SET `steam_link` = ? WHERE `id` = ?", array($link, $game_id));
			}

			// if the game list has no picture, grab it and save it
			if ($game_list['small_picture'] == NULL || $game_list['small_picture'] == '')
			{
				$saved_file = $core->config('path') . 'uploads/gamesdb/small/' . $game_list['id'] . '.jpg';
				$core->save_image($image, $saved_file);
				$dbl->run("UPDATE `calendar` SET `small_picture` = ? WHERE `id` = ?", [$game_list['id'] . '.jpg', $game_list['id']]);
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
		}
	}
	$page++;
} while ($stop == 0);

$total_found_new = count($new_games);

echo 'Total new found: ' . $total_found_new . "\n";

//$dbl->run("UPDATE `crons` SET `last_ran` = ?, `data` = ? WHERE `name` = 'steam_sales'", [core::$sql_date_now, $total_on_sale]);

echo "End of Steam Free Games Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

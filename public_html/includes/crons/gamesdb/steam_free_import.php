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

			$release_date_raw = $element->find('div.search_released', 0)->plaintext;
			$clean_release_date = steam_release_date($release_date_raw);

			$steam_id = NULL;
			if (strpos($link, '/app/') !== false) 
			{
				$steam_id = preg_replace('~https:\/\/store\.steampowered\.com\/app\/([0-9]*)\/.*~', '$1', $link);
			}
			echo 'steam id is ' . $steam_id;

			// ADD IT TO THE GAMES DATABASE
			$game_list = $dbl->run("SELECT 1 FROM `calendar` WHERE `name` = ?", array($title))->fetch();

			// check for a parent game, if this game is also known as something else, and the detected name isn't the one we use
			$check_dupes = $dbl->run("SELECT `real_id` FROM `item_dupes` WHERE `name` = ?", array($title))->fetch();

			// check for name change, insert different name into dupes table and keep original name
			$name_change = $dbl->run("SELECT `id` FROM `calendar` WHERE `steam_id` = ? AND `name` != ?", array($steam_id, $title))->fetchOne();
			if ($name_change)
			{
				$exists = $dbl->run("SELECT 1 FROM `item_dupes` WHERE `real_id` = ? AND `name` = ?", array($name_change, $title))->fetchOne();
				if (!$exists)
				{
					$dbl->run("INSERT IGNORE INTO `item_dupes` SET `real_id` = ?, `name` = ?", array($name_change, $title));
				}
			}
				
			if (!$game_list && !$check_dupes && !$name_change)
			{
				$check_dupes = $dbl->run("SELECT 1 FROM `item_dupes` WHERE `name` = ?", array($title))->fetch();
				if (!$check_dupes)
				{
					$dbl->run("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `steam_link` = ?, `free_game` = 1, `approved` = 1, `stripped_name` = ?", array($title, $clean_release_date, $link, $stripped_title));
					
					$new_id = $dbl->new_id();

					$new_games[] = $new_id;

					$saved_file = $core->config('path') . 'uploads/gamesdb/small/' . $new_id . '.jpg';
					$core->save_image($image, $saved_file);
					$dbl->run("UPDATE `calendar` SET `small_picture` = ? WHERE `id` = ?", [$new_id . '.jpg', $new_id]);
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

$total_found_new = count($new_games);

echo 'Total new found: ' . $total_found_new . "\n";

echo "End of Steam Free Games Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";
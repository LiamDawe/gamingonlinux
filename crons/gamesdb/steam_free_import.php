<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/public_html');
define("THIS_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/crons');

// http://simplehtmldom.sourceforge.net/
include(THIS_ROOT . '/simple_html_dom.php');

require APP_ROOT . '/includes/cron_bootstrap.php';

$game_sales = new game_sales($dbl, $templating = NULL, $user = NULL, $core);

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

			$title = $game_sales->clean_title($element->find('span.title', 0)->plaintext);	
			$title = html_entity_decode($title); // as we are scraping an actual html page, make it proper for the database
			$stripped_title = $game_sales->stripped_title($title);	
			echo $title . "\n";

			$image = $element->find('div.search_capsule img', 0)->src;
			echo $image . "\n";

			$release_date_raw = $element->find('div.search_released', 0)->plaintext;
			$clean_release_date = $game_sales->steam_release_date($release_date_raw);

			$steam_id = NULL;
			if (strpos($link, '/app/') !== false) 
			{
				$steam_id = preg_replace('~https:\/\/store\.steampowered\.com\/app\/([0-9]*)\/.*~', '$1', $link);
			}
			echo 'steam id is ' . $steam_id . PHP_EOL;

			$game_id = $game_sales->get_correct_info($title, $steam_id);
			
			if ($game_id === false)
			{
				echo PHP_EOL . 'Not found in DB - adding.' . PHP_EOL;
				$dbl->run("INSERT INTO `calendar` SET `name` = ?, `steam_id` = ?, `date` = ?, `steam_link` = ?, `free_game` = 1, `approved` = 1, `stripped_name` = ?", array($title, $steam_id, $clean_release_date, $link, $stripped_title));
					
				$new_id = $dbl->new_id();

				$new_games[] = $new_id;

				$saved_file = $core->config('path') . 'uploads/gamesdb/small/' . $new_id . '.jpg';
				$core->save_image($image, $saved_file);
				$dbl->run("UPDATE `calendar` SET `small_picture` = ? WHERE `id` = ?", [$new_id . '.jpg', $new_id]);
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

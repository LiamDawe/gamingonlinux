<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/public_html');
define("THIS_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/crons');

// http://simplehtmldom.sourceforge.net/
include(THIS_ROOT . '/simple_html_dom.php');

require APP_ROOT . '/includes/bootstrap.php';

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

			$title = $gamedb->clean_title($element->find('span.title', 0)->plaintext);	
			$title = html_entity_decode($title); // as we are scraping an actual html page, make it proper for the database
			$stripped_title = $gamedb->stripped_title($title);	
			echo $title . "\n";

			$image = $element->find('div.search_capsule img', 0)->src;
			echo $image . "\n";

			$release_date_raw = $element->find('div.search_released', 0)->plaintext;
			$clean_release_date = $gamedb->steam_release_date($release_date_raw);

			$steam_id = NULL;
			if (strpos($link, '/app/') !== false) 
			{
				$steam_id = preg_replace('~https:\/\/store\.steampowered\.com\/app\/([0-9]*)\/.*~', '$1', $link);
			}
			echo 'steam id is ' . $steam_id . PHP_EOL;

			$game_details = $gamedb->get_correct_info($title, $steam_id);
			
			if ($game_details === false)
			{
				echo PHP_EOL . 'Not found in DB - adding.' . PHP_EOL;
				$dbl->run("INSERT INTO `calendar` SET `name` = ?, `steam_id` = ?, `date` = ?, `steam_link` = ?, `free_game` = 1, `approved` = 1, `stripped_name` = ?", array($title, $steam_id, $clean_release_date, $link, $stripped_title));
					
				$new_id = $dbl->new_id();

				$new_games[] = array('release_date' => $clean_release_date, 'name' => $title, 'link' => $link);

				$saved_file = $core->config('path') . 'uploads/gamesdb/small/' . $new_id . '.jpg';
				$core->save_image($image, $saved_file);
				$dbl->run("UPDATE `calendar` SET `small_picture` = ? WHERE `id` = ?", [$new_id . '.jpg', $new_id]);
			}
			else
			{
				$update = 0;
				$sql_updates = array();
				$sql_data = array();

				// if the game list has no picture, grab it and save it
				if ($game_details['small_picture'] == NULL || $game_details['small_picture'] == '')
				{
					$update = 1;
					$saved_file = $core->config('path') . 'uploads/gamesdb/small/' . $game_details['id'] . '.jpg';
					$core->save_image($image, $saved_file);
					$sql_updates[] = '`small_picture` = ?';
					$sql_data[] = $game_details['id'] . '.jpg';

					echo PHP_EOL . 'Game missing small image - adding.' . PHP_EOL;
				}

				if ($update == 1)
				{
					$sql_data[] = $game_details['id'];
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

$total_found_new = count($new_games);

echo 'Total new found: ' . $total_found_new . "\n";

if ($total_found_new > 0)
{
	$email_output = array();
	foreach ($new_games as $new)
	{
		$email_output[] = 'Release Date: ' . $new['release_date'] . ' | Name: ' . $new['name'] . ' | Link: <a href="'.$new['link'].'">' . $new['link'] . '</a>';
		$email_output_plain[] = 'Release Date: ' . $new['release_date'] . ' | Name: ' . $new['name'] . ' | Link: ' . $new['link'];
	}

	$html_message = implode("<br />", $email_output);
	$html_message .= '<br />Total pages scanned: ' . $page;

	$plain_message = implode("\n", $email_output_plain);
	$plain_message .= "\nTotal pages scanned: " . $page;

	$to = $core->config('contact_email');
	$subject = 'GOL Steam New - Found New Free Games';

	// Mail it
	if ($core->config('send_emails') == 1)
	{
		$mail = new mailer($core);
		$mail->sendMail($to, $subject, $html_message, $plain_message);
	}
}

echo "End of Steam Free Games Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

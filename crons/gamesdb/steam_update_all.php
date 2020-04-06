<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/public_html');
define("THIS_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/crons');

// http://simplehtmldom.sourceforge.net/
include(THIS_ROOT . '/simple_html_dom.php');

require APP_ROOT . '/includes/cron_bootstrap.php';

$game_sales = new game_sales($dbl, $templating = NULL, $user = NULL, $core);

echo "Steam Games Updater Store importer started on " .date('d-m-Y H:m:s'). "\n";

$updated_list = [];
$new_games = [];

$page = 1;
$stop = 0;

$url = "http://store.steampowered.com/search/?sort_by=Released_DESC&tags=-1&category1=998&os=linux&page=";

do
{
	$html = file_get_html($url . $page);

	if ($html)
	{
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

				$bundle = 0;
				$steam_id = NULL;
				if (strpos($link, '/app/') !== false) 
				{
					$steam_id = preg_replace('~https:\/\/store\.steampowered\.com\/app\/([0-9]*)\/.*~', '$1', $link);
				}
				echo 'steam id is ' . $steam_id;

				if (strpos($link, '/sub/') !== false || strpos($link, '/bundle/') !== false) 
				{
					$bundle = 1;
				}

				$release_date_raw = $element->find('div.search_released', 0)->plaintext;
				$clean_release_date = $game_sales->steam_release_date($release_date_raw);

				// ADD IT TO THE GAMES DATABASE
				$game_list = $dbl->run("SELECT `id`, `steam_id`, `small_picture`, `bundle`, `date`, `stripped_name`, `steam_link` FROM `calendar` WHERE BINARY `name` = ?", array($title))->fetch();

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

				if (!$game_list && !$check_dupes && !$name_change)
				{
					$dbl->run("INSERT INTO `calendar` SET `name` = ?, `stripped_name` = ?, `date` = ?, `steam_link` = ?, `bundle` = ?, `approved` = 1, `steam_id` = ?", array($title, $stripped_title, $clean_release_date, $link, $bundle, $steam_id));

					$new_id = $dbl->new_id();

					$new_games[] = array('release_date' => $clean_release_date, 'name' => $title, 'link' => $link);

					$saved_file = $core->config('path') . 'uploads/gamesdb/small/' . $new_id . '.jpg';
					$core->save_image($image, $saved_file);
					$dbl->run("UPDATE `calendar` SET `small_picture` = ? WHERE `id` = ?", [$new_id . '.jpg', $new_id]);
				}	
				else
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

					if ($game_list['steam_id'] == NULL || $game_list['steam_id'] == '')
					{
						$update = 1;
						$sql_updates[] = '`steam_id` = ?';
						$sql_data[] = $steam_id;
					}

					// if the game list has no picture, grab it and save it
					if ($game_list['small_picture'] == NULL || $game_list['small_picture'] == '')
					{
						echo 'No small picture, updating.' . PHP_EOL;
						$update = 1;
						$saved_file = $core->config('path') . 'uploads/gamesdb/small/' . $game_id . '.jpg';
						$core->save_image($image, $saved_file);
						$sql_updates[] = '`small_picture` = ?';
						$sql_data[] = $game_id . '.jpg';
					}

					if ($game_list['bundle'] === 0 && $bundle == 1)
					{
						echo 'Bundle was set to 0 but it is a bundle, changing status to 1.'. PHP_EOL;
						$update = 1;
						$sql_updates[] = '`bundle` = ?';
						$sql_data[] = 1;					
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
	}
	else
	{
		$stop = 1;
	}
} while ($stop == 0);

$total_updated = count($updated_list);
$total_added = count($new_games);

$html_message = '';
$plain_message = '';
if ($total_added > 0)
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
	$subject = 'GOL Steam New';

	// Mail it
	if ($core->config('send_emails') == 1)
	{
		$mail = new mailer($core);
		$mail->sendMail($to, $subject, $html_message, $plain_message);
	}
}

echo 'Total updated: ' . $total_updated . ". Total new: ".$total_added.". Last page: ". $page . "\n";

echo "End of Steam Games Updater Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

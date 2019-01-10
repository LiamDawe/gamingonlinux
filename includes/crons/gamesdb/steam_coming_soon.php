<?php
define("APP_ROOT", dirname( dirname( dirname( dirname(__FILE__) ) ) ));

// http://simplehtmldom.sourceforge.net/
include(APP_ROOT . '/includes/crons/sales/simple_html_dom.php');

require APP_ROOT . '/includes/bootstrap.php';

require APP_ROOT . '/includes/cron_helpers.php';

echo "Steam Games Coming Soon Store importer started on " .date('d-m-Y H:m:s'). "\n";

$new_games = [];

$page = 1;
$stop = 0;

$url = "http://store.steampowered.com/search/results?os=linux&category1=998&supportedlang=english&filter=comingsoon&os=linux&page=";

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

			if (empty(trim($release_date_raw)))
			{
				$release_date_raw = NULL;
			}
			
			// ADD IT TO THE GAMES DATABASE
			$game_list = $dbl->run("SELECT `id`, `small_picture`, `bundle`, `date`, `steam_link`, `stripped_name`, `soon_date` FROM `calendar` WHERE `name` = ?", array($title))->fetch();

			// check for a parent game, if this game is also known as something else, and the detected name isn't the one we use
			$check_dupes = $dbl->run("SELECT `real_id` FROM `item_dupes` WHERE `name` = ?", array($title))->fetch();

			// check for name change, this time we will change the name since it's an unreleased game
			$name_change = $dbl->run("SELECT `id` FROM `calendar` WHERE `steam_id` = ? AND `name` != ?", array($steam_id, $title))->fetchOne();
			if ($name_change)
			{
				$dbl->run("UPDATE `calendar` SET `name` = ? WHERE `id` = ?", array($title, $name_change));
			}
					
			if (!$game_list && !$check_dupes && !$name_change)
			{				
				$dbl->run("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `steam_link` = ?, `bundle` = ?, `approved` = 1, `stripped_name` = ?, `soon_date` = ?", array($title, $clean_release_date, $link, $bundle, $stripped_title, $release_date_raw));
					
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

				$dbl->run("UPDATE `calendar` SET `date` = ? WHERE `id` = ?", array($clean_release_date, $game_id));

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

				if ($game_list['stripped_name'] == NULL || $game_list['stripped_name'] == '')
				{
					$update = 1;
					$sql_updates[] = '`stripped_name` = ?';
					$sql_data[] = $stripped_title;
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

				// If there's no proper date, make sure it's kept up to date for changes
				if ($release_date_raw)
				{
					$update = 1;
					$sql_updates[] = '`soon_date` = ?';
					$sql_data[] = $release_date_raw;
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

if ($total_found_new > 0)
{
	$email_output = array();
	foreach ($new_games as $new)
	{
		$email_output[] = 'Release Date: ' . $new['release_date'] . ' | Name: ' . $new['name'] . ' | Link: <a href="'.$new['link'].'">' . $new['link'] . '</a>';
	}

	$html_message = implode("<br />", $email_output);

	$html_message .= '<br />Total pages scanned: ' . $page;

	$to = $core->config('contact_email');
	$subject = 'GOL Steam New - Coming Soon';

	// Mail it
	if ($core->config('send_emails') == 1)
	{
		$mail = new mailer($core);
		$mail->sendMail($to, $subject, $html_message);
	}
}

echo 'Total new found: ' . $total_found_new . "\n";

//$dbl->run("UPDATE `crons` SET `last_ran` = ?, `data` = ? WHERE `name` = 'steam_sales'", [core::$sql_date_now, $total_on_sale]);

echo "End of Steam Games Coming Soon Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

<?php
ini_set("memory_limit", "-1");

define("APP_ROOT", dirname( dirname( dirname( dirname(__FILE__) ) ) ));

// http://simplehtmldom.sourceforge.net/
include(APP_ROOT . '/includes/crons/sales/simple_html_dom.php');

require APP_ROOT . '/includes/bootstrap.php';

require APP_ROOT . '/includes/cron_helpers.php';

$games_months = array();
$games_done = array();
$last_page = 0;

function do_loop($start_page = 1, $games_months, $games_done, $last_page)
{
	$page = 1;
	if ($start_page != 1)
	{
		$page = $start_page;
	}
	
	$stop = 0;
	$current_item = 0;

	$url = "http://store.steampowered.com/search/?sort_by=Released_DESC&tags=-1&category1=998&os=linux&page=";

	do
	{
		$html = file_get_html($url . $page);

		echo 'Page: ' . $page . "\r\n";

		$get_games = $html->find('a.search_result_row');

		// set the last page
		if ($last_page == 0)
		{
			$testing = $html->find("div.search_pagination_right", 0);
			foreach ($testing->find('a') as $page_link)
			{
				if (is_numeric($page_link->plaintext))
				{
					$last_page = $page_link->plaintext;
				}
				else
				{
					break;
				}
				echo 'LAST PAGE: ' . $last_page . PHP_EOL;
			}
		}

		if (empty($get_games))
		{
			if ($page >= $last_page)
			{
				$stop = 1;
			}
			else
			{
				echo 'LOOPING, NOT LAST PAGE' . PHP_EOL;
				do_loop($page - 1, $games_months, $games_done, $last_page);
			}
		}
		else
		{
			foreach($get_games as $element)
			{
				$current_item++;
				// if it has a price it has released/pre-released
				$price = $element->find('div.search_price_discount_combined', 0);

				if (isset($price->{'data-price-final'}))
				{
					$link = $element->href;

					$title = clean_title($element->find('span.title', 0)->plaintext);	
					$title = html_entity_decode($title); // as we are scraping an actual html page, make it proper for the database	
					$stripped_title = stripped_title($title);
					echo $title . "\n";

					$release_date_raw = $element->find('div.search_released', 0)->plaintext;
					$clean_release_date = strtotime(steam_release_date($release_date_raw));

					if (!in_array($title, $games_done)) // prevent dupes
					{
						$games_done[] = $title;

						if (array_key_exists(date('Y-m', $clean_release_date), $games_months))
						{
							$games_months[date('Y-m', $clean_release_date)]++;
						}
						else
						{
							$games_months[date('Y-m', $clean_release_date)] = 1;
						}
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
}

do_loop(1, $games_months, $games_done, $last_page); // let's do it!

ksort($games_months);

print_r($games_months);

echo "Last page: ". $page . "\n";

$csv_file = new SplFileObject('steam_dates.csv', 'w');

foreach ($games_months as $date => $total) 
{
    $csv_file->fputcsv([$date,$total]);
}
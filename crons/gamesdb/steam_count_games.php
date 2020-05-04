<?php
ini_set("memory_limit", "-1");

define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/public_html');
define("THIS_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/crons');

// http://simplehtmldom.sourceforge.net/
include(THIS_ROOT . '/simple_html_dom.php');

require APP_ROOT . '/includes/cron_bootstrap.php';

$game_sales = new game_sales($dbl, $templating = NULL, $user = NULL, $core);

class looper
{
	public $games_months = array();
	public $games_done = array();
	public $last_page = 0;
	public $page = 1;
	
	public function do_loop($start_page = 1, $game_sales)
	{
		if ($start_page != 1)
		{
			$this->page = $start_page;
		}
		
		$stop = 0;
		$current_item = 0;

		$url = "http://store.steampowered.com/search/?sort_by=Released_DESC&tags=-1&category1=998&os=linux&page=";

		do
		{
			$html = file_get_html($url . $this->page);

			echo 'Page: ' . $this->page . "\r\n";

			$get_games = $html->find('a.search_result_row');

			// set the last page
			if ($this->last_page == 0)
			{
				$testing = $html->find("div.search_pagination_right", 0);
				foreach ($testing->find('a') as $page_link)
				{
					if (is_numeric($page_link->plaintext))
					{
						$this->last_page = $page_link->plaintext;
					}
					else
					{
						break;
					}
					echo 'LAST PAGE: ' . $this->last_page . PHP_EOL;
				}
			}

			if (empty($get_games))
			{
				if ($this->page >= $this->last_page)
				{
					$stop = 1;
				}
				else
				{
					echo 'LOOPING, NOT LAST PAGE' . PHP_EOL;
					$this->do_loop($this->page - 1, 1, $game_sales);
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

						$title = $game_sales->clean_title($element->find('span.title', 0)->plaintext);	
						$title = html_entity_decode($title); // as we are scraping an actual html page, make it proper for the database	
						$stripped_title = $game_sales->stripped_title($title);
						echo $title . "\n";

						$release_date_raw = $element->find('div.search_released', 0)->plaintext;
						$clean_release_date = strtotime($game_sales->steam_release_date($release_date_raw));

						if (!in_array($title, $this->games_done)) // prevent dupes
						{
							$this->games_done[] = $title;

							if (array_key_exists(date('Y-m', $clean_release_date), $this->games_months))
							{
								$this->games_months[date('Y-m', $clean_release_date)]++;
							}
							else
							{
								$this->games_months[date('Y-m', $clean_release_date)] = 1;
							}
						}
					}	
				}
				// free up memory
				$html->__destruct();
				unset($html);
				$html = null;
			}
			$this->page++;
		} while ($stop == 0);
	}
}

$steamloop = new looper();

$steamloop->do_loop(1, $game_sales); // let's do it!

ksort($steamloop->games_months);

print_r($steamloop->games_months);

echo "Last page: ". $steamloop->page . "\n";

$csv_file = new SplFileObject('steam_dates.csv', 'w');

foreach ($steamloop->games_months as $date => $total) 
{
    $csv_file->fputcsv([$date,$total]);
}
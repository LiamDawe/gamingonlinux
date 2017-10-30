<?php
error_reporting(-1);

echo "GOG importer started on " .date('d-m-Y H:m:s'). "\n";

$doc_root = dirname( dirname( dirname( dirname(__FILE__) ) ) );

// we dont need the whole bootstrap
require $doc_root . '/includes/loader.php';
include $doc_root . '/includes/config.php';
$dbl = new db_mysql();
$core = new core();

$date = strtotime(gmdate("d-n-Y H:i:s"));

$url = 'http://www.gog.com/games/feed?format=json&page=1';
if (core::file_get_contents_curl($url) == true)
{
	// magic
}
else
{
	$to = $core->config('contact_email');
	$subject = 'GOL ERROR - Cannot reach GOG sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL - GOG Sales Importer <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	error_log("Couldn't reach the GOG sales XML");
	die('GOG XML not available!');
}

$on_sale = [];

$urlMask = 'http://www.gog.com/games/feed?format=json&page=%d';

$page = 0;
do {
	$url = sprintf($urlMask, ++$page);
	$array = json_decode(core::file_get_contents_curl($url), true);
	$count = count($array['games']);
	printf("Page #%d: %d product(s)\n", $page, $count);

	// DEBUG: Check whole array
	//print_r($array['games']);

	foreach ($array['games'] as $games)
	{
		//echo $games['title'] . "\n";
		//echo "Linux Support: " . $games['linux_compatible'] . "\n";

		if ($games['linux_compatible'] == 1 && $games['discount_raw'] > 0)
		{
			$website = $games['short_link'];
			$image = $games['img_cover'];
			$current_price =  $games['price_raw']/100; //LoL what, ['price_raw'] is the discounted price
			$original_price = round( $games['price_raw'] / (100 - $games['discount']), 2);  //Looks like ['discount'] is a % discount

			//DAFUQ $games['discount'] is off by 1%, php is high or something

			// var_dump( (100 - $games['discount']), $games['discount'] );

			// what the fuck GOG, seriously, stop re-ordering the fucking "The"
			// Sometimes you do "The Name", sometimes you do "Name, The" - make your bloody mind up
			if (strpos($games['title'], ', The - The') !== false)
			{
				$games['title'] = str_replace(', The - The', ' - The', $games['title']);
				$games['title'] = 'The ' . $games['title'];
			}
			if (strpos($games['title'], ', The') !== false)
			{
				$games['title'] = str_replace(', The', '', $games['title']);
				$games['title'] = 'The ' . $games['title'];
			}

			/*echo $games['title'] . "\n";
			echo "* Original Price: $". $original_price ."\n";
			echo "* Price Now: $" . $current_price . "\n";*/

			// ADD IT TO THE GAMES DATABASE
			$game_list = $dbl->run("SELECT `id`, `also_known_as` FROM `calendar` WHERE `name` = ?", array($games['title']))->fetch();

			if (!$game_list)
			{
				$dbl->run("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `gog_link` = ?, `on_sale` = 1", array($games['title'], $games['original_release_date'], $website));

				// need to grab it again
				$game_list = $dbl->run("SELECT `id` FROM `calendar` WHERE `name` = ?", array($games['title']))->fetch();

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

				$dbl->run("UPDATE `calendar` SET `on_sale` = 1 WHERE `id` = ?", array($game_id));
			}

			$on_sale[] = $game_id;

			$check_sale = $dbl->run("SELECT 1 FROM `sales` WHERE `game_id` = ? AND `store_id` = 5", array($game_id))->fetch();

			// if it does exist, make sure it's not from GOG already
			if (!$check_sale)
			{
				$dbl->run("INSERT INTO `sales` SET `game_id` = ?, `store_id` = 5, `accepted` = 1, `sale_dollars` = ?, `original_dollars` = ?, `link` = ?", array($game_id, $current_price, $original_price, $website));

				$sale_id = $dbl->new_id();

				//echo "\tAdded ".$games['title']." to the sales DB with id: " . $sale_id . ".\n";
			}
		}
	}
} while ($count > 0);

// remove any not found on sale
$in  = str_repeat('?,', count($on_sale) - 1) . '?';
$dbl->run("DELETE FROM `sales` WHERE `game_id` NOT IN ($in) AND `store_id` = 5", $on_sale);

echo "\n\n";
echo "End of GOG import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

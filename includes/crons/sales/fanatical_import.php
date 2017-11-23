<?php
error_reporting(-1);

define("APP_ROOT", dirname( dirname( dirname( dirname(__FILE__) ) ) ));

require APP_ROOT . '/includes/bootstrap.php';

$game_sales = new game_sales($dbl, $templating, $user, $core);

echo "Fanatical importer started on " .date('d-m-Y H:m:s'). "\n";

$date = strtotime(gmdate("d-n-Y H:i:s"));

$url = 'https://api.fanatical.com/api/feed?cc=US&auth=gamingonlinux';
if (core::file_get_contents_curl($url) == true)
{
	// magic
}
else
{
	$to = $core->config('contact_email');
	$subject = 'GOL ERROR - Cannot reach Fanatical sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL - Fanatical Sales Importer <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	error_log("Couldn't reach the Fanatical sales XML");
	die('Fanatical XML not available!');
}

$on_sale = [];

$array = json_decode(core::file_get_contents_curl($url), true);

// DEBUG: Check whole array
//print_r($array['data']);

foreach ($array['data'] as $games)
{
	if (in_array('linux', $games['operating_systems']) && $games['discount_percent'] > 0 && substr($games['title'], -6) != 'Bundle')
	{
		$website = str_replace('https://', '', $games['url']);
		$current_price =  $games['current_price']['USD'];
		$original_price = $games['regular_price']['USD'];

		$games['title'] = $game_sales->clean_title($games['title']);

		echo $games['title'] . "\n";
		echo "* Original Price: $". $original_price ."\n";
		echo "* Price Now: $" . $current_price . "\n";

		$release_date = date('Y-m-d', $games['release_date']);

		// ADD IT TO THE GAMES DATABASE
		$game_list = $dbl->run("SELECT `id`, `also_known_as` FROM `calendar` WHERE `name` = ?", array($games['title']))->fetch();

		if (!$game_list)
		{
			$dbl->run("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `on_sale` = 1", array($games['title'], $release_date));

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

		$check_sale = $dbl->run("SELECT 1 FROM `sales` WHERE `game_id` = ? AND `store_id` = 2", array($game_id))->fetch();

		// if it does exist, make sure it's not from Fanatical already
		if (!$check_sale)
		{
			$share_sale = 'http://www.shareasale.com/r.cfm?u=1644082&b=880704&m=66498&urllink='.$website;
			$dbl->run("INSERT INTO `sales` SET `game_id` = ?, `store_id` = 2, `accepted` = 1, `sale_dollars` = ?, `original_dollars` = ?, `link` = ?", array($game_id, $current_price, $original_price, $share_sale));

			$sale_id = $dbl->new_id();

			echo "\tAdded ".$games['title']." to the sales DB with id: " . $sale_id . ".\n";
		}
	}
}

$total_on_sale = count($on_sale);

// remove any not found on sale
if (isset($total_on_sale) && $total_on_sale > 0)
{
	$in  = str_repeat('?,', count($on_sale) - 1) . '?';
	$dbl->run("DELETE FROM `sales` WHERE `game_id` NOT IN ($in) AND `store_id` = 2", $on_sale);
}

echo "\n\n";
echo "End of Fanatical import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";
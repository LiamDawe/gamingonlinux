<?php
define("APP_ROOT", dirname( dirname( dirname( dirname(__FILE__) ) ) ));

require APP_ROOT . '/includes/bootstrap.php';

$game_sales = new game_sales($dbl, $templating, $user, $core);

echo "Chrono.gg Store importer started on " . date('d-m-Y H:m:s'). "\n";

$date = strtotime(gmdate("d-n-Y H:i:s"));

//Their API endpoint
$url = "https://api.chrono.gg/sale";

$json = core::file_get_contents_curl($url);
if ($json == false)
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach the Chrono.gg sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	error_log("Couldn't reach the Chrono.gg sales json");
	die('Chrono.gg JSON not available!');
}

$game = json_decode(core::file_get_contents_curl($url));

if (!empty($game))
{
	$use_sale = 0;

	if (isset($game->platforms))
	{
		if (in_array('linux', $game->platforms))
		{
			$use_sale = 1;
		}
	}

	if ($use_sale == 1)
	{
		$sane_name = $game_sales->clean_title($game->name);

		echo $sane_name."\n";

		echo 'Current Price: $' . $game->sale_price  .  ', Full Price: $' . $game->normal_price . "\n";

		$website = $game->unique_url;
					
		// ADD IT TO THE GAMES DATABASE
		$game_list = $dbl->run("SELECT `id` FROM `calendar` WHERE `name` = ?", array($sane_name))->fetch();
			
		if (!$game_list)
		{
			$dbl->run("INSERT INTO `calendar` SET `name` = ?, `approved` = 1", array($sane_name));
			
			// need to grab it again
			$game_list = $dbl->run("SELECT `id` FROM `calendar` WHERE `name` = ?", array($sane_name))->fetch();
		}

		$check_sale = $dbl->run("SELECT 1 FROM `sales` WHERE `game_id` = ? AND `store_id` = 9", array($game_list['id']))->fetch();
						
		// if it does exist, make sure it's not from Chrono.gg already
		if (!$check_sale)
		{
			$now = new DateTime($game->end_date);
			$end_date = $now->format('Y-m-d H:m:s');

			$dbl->run("INSERT INTO `sales` SET `game_id` = ?, `store_id` = 9, `accepted` = 1, `sale_dollars` = ?, `original_dollars` = ?, `link` = ?, `end_date` = ?", array($game_list['id'], $game->sale_price, $game->normal_price, $website, $end_date));
						
			$sale_id = $dbl->new_id();

			$game_sales->notify_wishlists($game_list['id']);
						
			echo "\tAdded ".$sane_name." to the sales DB with id: " . $sale_id . ".\n";
		}
	}

	if ($use_sale == 0)
	{
		$dbl->run("DELETE FROM `sales` WHERE `store_id` = 9"); // delete any left, since they only do one at a time
	}
}

else
{
	$dbl->run("DELETE FROM `sales` WHERE `store_id` = 9"); // delete any left, since they only do one at a time
}

$dbl->run("DELETE FROM `sales` WHERE `store_id` = 9 AND `end_date` < NOW()");

echo "End of Chrono.gg Store import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

$dbl = NULL;
<?php
error_reporting(-1);

echo "GOG importer started on " .date('d-m-Y H:m:s'). "\n";

include('/home/gamingonlinux/public_html/includes/config.php');

include('/home/gamingonlinux/public_html/includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('/home/gamingonlinux/public_html/includes/class_core.php');
$core = new core();

$date = strtotime(gmdate("d-n-Y H:i:s"));

$url = 'http://www.gog.com/games/feed?format=json&page=1';
if (file_get_contents($url) == true)
{
	// magic
}
else
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach GOG sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	die('GOG XML not available!');
}

// get config
$db->sqlquery("SELECT `data_key`, `data_value` FROM `config`");
$fetch_config = $db->fetch_all_rows();

$config = array();
foreach ($fetch_config as $config_set)
{
	$config[$config_set['data_key']] = $config_set['data_value'];
}

$games_added = '';
$email = 0;

$urlMask = 'http://www.gog.com/games/feed?format=json&page=%d';

$page = 0;
do {
	$url = sprintf($urlMask, ++$page);
	$array = json_decode(file_get_contents($url), true);
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

			echo $games['title'] . "\n";
			echo "* Original Price: $". $original_price ."\n";
			echo "* Price Now: $" . $current_price . "\n";

			// ADD IT TO THE GAMES DATABASE, FOR FUTURE USE
			$games['title'] = $games['title'];

			$db->sqlquery("SELECT `name`, `local_id` FROM `game_list` WHERE `name` = ?", array($games['title']));
			$game_list = $db->fetch();
			if ($db->num_rows() == 0)
			{
				$db->sqlquery("INSERT INTO `game_list` SET `name` = ?", array($games['title']));

				// need to grab it again
				$db->sqlquery("SELECT `name`, `local_id` FROM `game_list` WHERE `name` = ?", array($games['title']));
				$game_list = $db->fetch();
			}

			$db->sqlquery("UPDATE `game_list` SET `on_sale` = 1 WHERE `name` = ?", array($games['title']));

			$db->sqlquery("SELECT `info` FROM `game_sales` WHERE `info` = ? AND `provider_id` = 34", array($games['title']));

			// if it does exist, make sure it's not from GOG already
			if ($db->num_rows() == 0)
			{
				$db->sqlquery("INSERT INTO `game_sales` SET `list_id` = ?, `info` = ?, `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 34, `dollars` = ?, `dollars_original` = ?, `imported_image_link` = ?, `drmfree` = 1", array($game_list['local_id'], $games['title'], $website, core::$date, $current_price, $original_price, $image));

				$sale_id = $db->grab_id();

				echo "\tAdded this game to the sales DB with id: " . $sale_id . ".\n";

				$games_added .= $games['title'] . '<br />';

				$email = 1;
			}

			// if we already have it, just update it
			else
			{
				$db->sqlquery("UPDATE `game_sales` SET `website` = ?, `dollars` = ?, `dollars_original` = ?, `imported_image_link` = ? WHERE `provider_id` = 34 AND info = ?", array($website, $current_price, $original_price, $image, $games['title']));

				echo "Updated {$games['title']} with the latest information<br />";
			}
		}
	}
} while ($count > 0);


echo "\n\n";//More whitespace, just to make the output look a bit more pretty

/*
if ($email == 1)
{
	// multiple recipients
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL Contact Us - GOG sales added';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "<a href=\"http://www.gamingonlinux.com/sales/\">Sales Page</a> - The auto GOG salesman has added<br />$games_added", $headers);

	echo "Mail sent!";
}*/
echo "End of GOG import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

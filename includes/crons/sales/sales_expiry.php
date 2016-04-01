<?php
ini_set('display_errors',1);

//define('path', '/home/gamingonlinux/public_html/includes/');
define('path', '/mnt/storage/public_html/includes/');

include(path . 'config.php');

include(path . 'class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include(path . 'class_core.php');
$core = new core();

$removed_counter = 0;
$games = '';
$game_ids = array();

$db->sqlquery("SELECT `has_screenshot`,`screenshot_filename` FROM `game_sales` WHERE `expires` <= ? AND `expires` > 0", array(core::$date));
$get_all = $db->fetch_all_rows();

foreach($get_all as $sale)
{
	if ($sale['has_screenshot'] == 1)
	{
		unlink('/home/gamingonlinux/public_html/uploads/sales/' . $sale['screenshot_filename']);
	}
}

// remove old sales but keep sales with no date on them
$db->sqlquery("SELECT `id`, `info`, `list_id` FROM `game_sales` WHERE `expires` <= ? AND `expires` > 0", array(core::$date), 'sales_expiry.php');
$removing_now = $db->fetch_all_rows();

foreach ($removing_now as $remove)
{
	echo "{$remove['info']} for reaching expiry time<br />";
	$games .= "{$remove['info']} for reaching expiry time<br />";
	$removed_counter++;
	$game_ids[] = $remove['id'];
}

$db->sqlquery("DELETE FROM `game_sales` WHERE `expires` <= ? AND `expires` > 0", array(core::$date), 'sales_expiry.php');

//
// remove indiegamestand sales that are no longer listed (for ones that had no end date)
//
echo "Starting IndieGameStand remover<br />\r\n";

$url = 'https://indiegamestand.com/store/salefeed.php';
$xml = simplexml_load_string(file_get_contents($url));

$igs_on_sale = array();

foreach ($xml->channel->item as $game)
{
	$igs_on_sale[] = (string) $game->{'title'};
}

// now search our database for all indiegamestand sales and match them up with current sales, if it doesn't match then it's no longer on sale so remove it!
$db->sqlquery("SELECT `id`, `info` FROM `game_sales` WHERE `provider_id` = 22 AND `accepted` = 1");
$igs_currently_in_db = $db->fetch_all_rows();

//print_r($currently_in_db);

$removed_counter_igs = 0;

foreach ($igs_currently_in_db as $value=> $in_db)
{
		if (!in_array($in_db['info'], $igs_on_sale))
		{
			$db->sqlquery("SELECT `has_screenshot`,`screenshot_filename` FROM `game_sales` WHERE `info` = ? AND `provider_id` = 22", array($in_db['info']));
			$get_ss = $db->fetch();
			if ($get_ss['has_screenshot'] == 1)
			{
				unlink('/home/gamingonlinux/public_html/uploads/sales/' . $get_ss['screenshot_filename']);
			}

			$db->sqlquery("DELETE FROM `game_sales` WHERE `info` = ? AND `provider_id` = 22", array($in_db['info']));

			echo $in_db['info'] . " Removed from database \n";

			$removed_counter_igs++;
			$removed_counter++;
			$games .= "{$in_db['info']} from IndieGameStand<br />";
			$game_ids[] = $in_db['id'];
		}
}

if ($removed_counter_igs == 0)
{
	echo "No games to remove from IndieGameStand<br />\r\n";
}

//
// remove GamersGate sales that are no longer listed (for ones that had no end date)
//
echo "Starting GamersGate remover<br />\r\n";

$url = 'http://www.gamersgate.com/feeds/products?filter=linux,offers&dateformat=timestamp';

$get_url = file_get_contents($url);

$get_url = preg_replace("^&(?!#38;)^", "&amp;", $get_url);

$xml = simplexml_load_string($get_url);

$igs_on_sale = array();

foreach ($xml->item as $game)
{
	$new_title = html_entity_decode($game->title, ENT_QUOTES);
	$gg_on_sale[] = (string) $new_title;
}

// now search our database for all indiegamestand sales and match them up with current sales, if it doesn't match then it's no longer on sale so remove it!
$db->sqlquery("SELECT `id`, `info` FROM `game_sales` WHERE `provider_id` = 12 AND `accepted` = 1");
$gg_currently_in_db = $db->fetch_all_rows();

//print_r($currently_in_db);

$removed_counter_gg = 0;

foreach ($gg_currently_in_db as $value=> $in_db)
{
	if (!in_array($in_db['info'], $gg_on_sale))
	{
		$db->sqlquery("SELECT `has_screenshot`,`screenshot_filename` FROM `game_sales` WHERE `info` = ? AND `provider_id` = 12", array($in_db['info']));
		$get_ss = $db->fetch();
		if ($get_ss['has_screenshot'] == 1)
		{
			unlink('/home/gamingonlinux/public_html/uploads/sales/' . $get_ss['screenshot_filename']);
		}

		$db->sqlquery("DELETE FROM `game_sales` WHERE `info` = ? AND `provider_id` = 12", array($in_db['info']));

		echo $in_db['info'] . " Removed from database \n";

		$removed_counter_gg++;
		$removed_counter++;
		$games .= "{$in_db['info']} from GamersGate<br />";
		$game_ids[] = $in_db['id'];
	}
}

if ($removed_counter_gg == 0)
{
	echo "No games to remove from GamersGate<br />\r\n";
}

//
// remove Games Republic sales that are no longer listed (for ones that had no end date)
//
echo "Starting Games Republic remover<br />\r\n";

$url = 'https://linux.gamesrepublic.com/xml/catalog?currency=usd&count=all&mode=OnlyPromotions';
if (file_get_contents($url) == true)
{
	$xml = simplexml_load_string(file_get_contents($url));

	$on_sale = array();

	foreach ($xml->group->o as $game)
	{
		$on_sale[] = $game->{'name'};
	}

	// now search our database for all desura sales and match them up with current sales, if it doesn't match then it's no longer on sale so remove it!
	$db->sqlquery("SELECT `id`, `info` FROM `game_sales` WHERE `provider_id` = 33 AND `accepted` = 1");
	$currently_in_db = $db->fetch_all_rows();

	//print_r($currently_in_db);

	$removed_counter_gamesrepublic = 0;

	foreach ($currently_in_db as $value=> $in_db)
	{
		if (!in_array($in_db['info'], $on_sale))
		{
			$db->sqlquery("SELECT `has_screenshot`,`screenshot_filename` FROM `game_sales` WHERE `info` = ? AND `provider_id` = 2", array($in_db['info']));
			$get_ss = $db->fetch();
			if ($get_ss['has_screenshot'] == 1)
			{
				unlink('/home/gamingonlinux/public_html/uploads/sales/' . $get_ss['screenshot_filename']);
			}

			$db->sqlquery("DELETE FROM `game_sales` WHERE `info` = ? AND `provider_id` = 33", array($in_db['info']));

			echo $in_db['info'] . " Removed from database \n";

			$removed_counter_gamesrepublic++;
			$removed_counter++;
			$games .= " {$in_db['info']} from GamesRepublic<br />";
			$game_ids[] = $in_db['id'];
		}
	}
	if ($removed_counter_gamesrepublic == 0)
	{
		echo "No games to remove from Games Republic<br />\r\n";
	}
}
else
{
	echo "Couldn't access the Games Republic feed.<br />\r\n";
}

//
// remove GOG sales that are no longer listed (for ones that had no end date)
//
echo "Starting GOG remover<br />\r\n";
$urlMask = 'http://www.gog.com/games/feed?format=json&page=%d';

if (file_get_contents(sprintf($urlMask, 1)) == true)
{
	$page = 1;
	$count = 1;

	do {
		$url = sprintf($urlMask, $page++);
		$gogarray = json_decode(file_get_contents($url), true);
		$count = count($gogarray['games']);
		printf("Page #%d: %d product(s)\n", $page, $count);
		if (is_array($gogarray)){
		foreach ($gogarray['games'] as $goggames)
		{
			if ($goggames['linux_compatible'] == 1 && $goggames['discount_raw'] > 0)
			{
				$gog_on_sale[] = $goggames['title'];
			}
		}}
	} while ($count > 0);

		// now search our database for all desura sales and match them up with current sales, if it doesn't match then it's no longer on sale so remove it!
		$db->sqlquery("SELECT `id`, `info` FROM `game_sales` WHERE `provider_id` = 34 AND `accepted` = 1");
		$currently_in_db = $db->fetch_all_rows();

		//print_r($gog_on_sale);
		//print_r($currently_in_db);

		$removed_counter_gog = 0;

		foreach ($currently_in_db as $value=> $in_db)
		{
			if (!in_array($in_db['info'], $gog_on_sale))
			{
				$db->sqlquery("SELECT `has_screenshot`,`screenshot_filename` FROM `game_sales` WHERE `info` = ? AND `provider_id` = 34", array($in_db['info']));
				$get_ss = $db->fetch();
				if ($get_ss['has_screenshot'] == 1)
				{
					unlink('/home/gamingonlinux/public_html/uploads/sales/' . $get_ss['screenshot_filename']);
				}

				$db->sqlquery("DELETE FROM `game_sales` WHERE `info` = ? AND `provider_id` = 34", array($in_db['info']));

				echo $in_db['info'] . " Removed from database \n";

				$removed_counter_gog++;
				$removed_counter++;
				$games .= " {$in_db['info']} from GOG<br />";
				$game_ids[] = $in_db['id'];
			}
		}
		if ($removed_counter_gog == 0)
		{
			echo "No games to remove from GOG<br />\r\n";
		}
}
else
{
	echo "Couldn't access the GOG feed.<br />\r\n";
}

/*
//
// Remove Itch.io sales
//
echo "Starting Itch.io remover<br />\r\n";

$url = 'http://itch.io/browse/platform-linux/price-sale.xml';
$xml = simplexml_load_string(file_get_contents($url));

$on_sale = array();

foreach ($xml->item as $game)
{
	$on_sale[] = (string) $game->{'plainTitle'};
}

// now search our database for all desura sales and match them up with current sales, if it doesn't match then it's no longer on sale so remove it!
$db->sqlquery("SELECT `id`, `info` FROM `game_sales` WHERE `provider_id` = 28 AND `accepted` = 1");
$currently_in_db = $db->fetch_all_rows();

//print_r($currently_in_db);

$removed_counter_itch = 0;

foreach ($currently_in_db as $value=> $in_db)
{
	if (!in_array($in_db['info'], $on_sale))
	{
		$db->sqlquery("SELECT `has_screenshot`,`screenshot_filename` FROM `game_sales` WHERE `info` = ? AND `provider_id` = 28", array($in_db['info']));
		$get_ss = $db->fetch();
		if ($get_ss['has_screenshot'] == 1)
		{
			unlink('/home/gamingonlinux/public_html/uploads/sales/' . $get_ss['screenshot_filename']);
		}

		$db->sqlquery("DELETE FROM `game_sales` WHERE `info` = ? AND `provider_id` = 28", array($in_db['info']));

		echo $in_db['info'] . " Removed from database \n";

		$removed_counter_itch++;
		$removed_counter++;
		$games .= " {$in_db['info']} from Itch.io<br />";
		$game_ids[] = $in_db['id'];
	}
}
if ($removed_counter_itch == 0)
{
	echo "No games to remove from Itch.io<br />\r\n";
}*/

//
// remove Fireflower Games sales that are no longer listed (for ones that had no end date)
//
require_once(path . 'simplepie/autoloader.php');
class FireFlowerFeed {
	const PROVIDER_ID = 99;
	public $feed;

	const NAMESPACE_SOFTWARE = 'http://stenke.org/rss/software/0.1/';
	const NAMESPACE_GOOGLE = 'http://base.google.com/ns/1.0';

	public $feed_url = "http://fireflowergames.com/?feed=products&sale=1";

	function __construct($url = null){
		if(!$url){
			$url = $this->feed_url;
		}
		$feed = new SimplePie();
		$feed->set_feed_url($url);
		$feed->enable_cache(false);
		$feed->init();
		$this->feed = $feed;
		//$feed->handle_content_type();
	}

	function get_products(){
		$feed = $this->feed;
		$products = array();
		foreach($feed->get_items() as $item)
		{
			$product = new stdClass();
			$product->title = $item->get_title();
			$products[] = $product->title;
		}

		return $this->products = $products;
	}

	private function parse_price($tag){
		$tag = array_shift($tag);
		return (float)trim(str_replace('EUR', '', $tag['data']));
	}
}

$feed = new FireFlowerFeed();
$on_sale = $feed->get_products();

// now search our database for all desura sales and match them up with current sales, if it doesn't match then it's no longer on sale so remove it!
$db->sqlquery("SELECT `id`, `info` FROM `game_sales` WHERE `provider_id` = 9 AND `accepted` = 1");
$currently_in_db = $db->fetch_all_rows();

//print_r($currently_in_db);

//print_r($on_sale);

$removed_counter_ffg = 0;

foreach ($currently_in_db as $value=> $in_db)
{
		if (!in_array($in_db['info'], $on_sale))
		{
			$db->sqlquery("SELECT `has_screenshot`,`screenshot_filename` FROM `game_sales` WHERE `info` = ? AND `provider_id` = 9", array($in_db['info']));
			$get_ss = $db->fetch();
			if ($get_ss['has_screenshot'] == 1)
			{
				unlink('/home/gamingonlinux/public_html/uploads/sales/' . $get_ss['screenshot_filename']);
			}

			$db->sqlquery("DELETE FROM `game_sales` WHERE `info` = ? AND `provider_id` = 9", array($in_db['info']));

			echo $in_db['info'] . " Removed from database from fireflower games\n";

			$removed_counter_ffg++;
			$removed_counter++;
			$games .= " {$in_db['info']} from Fireflower Games<br />";
			$game_ids[] = $in_db['id'];
		}
}

if ($removed_counter_ffg == 0)
{
	echo "No games to remove from FireFlower Games<br />\r\n";
}

// remove any admin notifications for ended sales that have been removed
$game_ids_removed = implode(',', $game_ids);
if (!empty($game_ids_removed))
{
	$db->sqlquery("DELETE FROM `admin_notifications` WHERE `sale_id` IN (?) AND `sale_id` != 0", array($game_ids_removed));
}

// update the time it was last run
$db->sqlquery("UPDATE `config` SET `data_value` = ? WHERE `data_key` = 'sales_expiry_lastrun'", array(core::$date));

echo "\n\n<br />End of sales expiry cron @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

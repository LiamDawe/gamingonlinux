<?php
echo "Fireflower Games Importer started on " . date('d-m-Y H:m:s') . "\n";

require_once('/home/gamingonlinux/public_html/includes/simplepie/autoloader.php');
$url = "http://fireflowergames.com/?feed=products&sale=1";
if (file_get_contents($url) == true)
{
	// magic
}
else
{
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL ERROR - Cannot reach Fireflower Games sales importer';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "Could not reach the importer!", $headers);
	die('Fireflower Games XML not available!');
}

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
		$feed->init();
		$this->feed = $feed;
//		$feed->handle_content_type();
	}

	function get_products(){
		$feed = $this->feed;
		$products = array();
		foreach($feed->get_items() as $item){
			$product = new stdClass();
			$product->url = $item->get_permalink();
			$product->title = $item->get_title();
			$product->description = $item->get_description();
			$product->content = $item->get_content();
			$product->price = $this->parse_price($item->get_item_tags(FireFlowerFeed::NAMESPACE_GOOGLE, 'price'));
			$product->sale_price = $this->parse_price($item->get_item_tags(FireFlowerFeed::NAMESPACE_GOOGLE,'sale_price'));
			$drm = array_shift($item->get_item_tags(FireFlowerFeed::NAMESPACE_SOFTWARE, 'drm'));
			$product->image_link = array_shift($item->get_item_tags(FireFlowerFeed::NAMESPACE_GOOGLE, 'image_link'));

			$product->drm_free = 1;
			if ($drm['data'] == 'Yes')
			{
				$product->drm_free = 0;
			}

			$platforms = array();
			foreach($item->get_item_tags(FireFlowerFeed::NAMESPACE_SOFTWARE, 'platform') as $platform){
				$platforms[] = $platform['data'];
			}
			$product->platforms = $platforms;
			$product->linux = (int)in_array('Linux', $platforms);


			$products[] = $product;
		}

		return $this->products = $products;
	}

	private function parse_price($tag){
		$tag = array_shift($tag);
		return (float)trim(str_replace('EUR', '', $tag['data']));
	}
}

$feed = new FireFlowerFeed();
$products = $feed->get_products();

include('/home/gamingonlinux/public_html/includes/config.php');
include('/home/gamingonlinux/public_html/includes/class_mysql.php');
include('/home/gamingonlinux/public_html/includes/class_core.php');

$db = new mysql($database_host, $database_username, $database_password, $database_db);
$core = new core();
$date = strtotime(gmdate("d-n-Y H:i:s"));
$email = 0;
$games = array();

foreach($products as $product)
{
	echo "* Starting import of ".$product->title."\n";
	if($product->linux)
	{
		// ADD IT TO THE GAMES DATABASE, FOR FUTURE USE
		$product->title = $product->title;
		$db->sqlquery("SELECT `name` FROM `game_list` WHERE `name` = ?", array($product->title));
		if ($db->num_rows() == 0)
		{
			$db->sqlquery("INSERT INTO `game_list` SET `name` = ?", array($product->title));
		}

		$db->sqlquery("UPDATE `game_list` SET `on_sale` = 1 WHERE `name` = ?", array($product->title));

		$ok_to_import = true;
		$db->sqlquery("SELECT `info`, `provider_id`, `accepted` FROM `game_sales` WHERE `info` = ?", array($product->title));
		if ($db->num_rows() >= 1)
		{
			while ($test = $db->fetch())
			{
				if ($test['provider_id'] == 9 && $test['accepted'] == 1)
				{
					$ok_to_import = false;
					echo "\tI already know about this game, and fireflower games told me about it\n";
				}

				else
				{
					echo "\tI already know about this game, however fireflower games wasn't the one who told me about it\n";
				}
			}
		}

		else
		{
			echo "\tI didn't know about this game before.\n";
		}

		if ($ok_to_import == true)
		{
			// test the info we get
			echo "Test the info:<br />
			$product->title,<br />
			$product->url,<br />
			core::$date,<br />
			$product->price,<br />
			$product->sale_price,<br />
			$product->drm_free<br />
			$product->image_link";

			$db->sqlquery("INSERT INTO `game_sales` SET `info` = ?, `website` = ?, `date` = ?, `accepted` = 1, `euros` = ?, `euros_original` = ?, `drmfree` = ?, `imported_image_link` = ?, `provider_id` = 9",
				array(
					$product->title,
					$product->url,
					core::$date,
					$product->sale_price,
					$product->price,
					$product->drm_free,
					$product->image_link['data']
				), 'ffg_sales_import.php'
			);
			$sale_id = $db->grab_id();

			echo "\tAdded this game to the sales DB with id: " . $sale_id . ".\n";
			$games[] = $product->title;
			$email = 1;
		}

		else
		{
			$db->sqlquery("UPDATE `game_sales` SET `website` = ?, `date` = ?, `accepted` = 1, `euros` = ?, `euros_original` = ?, `drmfree` = ?, `imported_image_link` = ? WHERE `info` = ? AND `provider_id` = 9",
				array(
					$product->url,
					core::$date,
					$product->sale_price,
					$product->price,
					$product->drm_free,
					$product->image_link['data'],
					$product->title,
				), 'ffg_sales_import.php'
			);
			echo "\Already have it, so giving it the latest information!\n";
		}
	}

	else
	{
		echo "\tBuggers, this game isn't for linux!\n";
	}
	echo "\n";
}
echo "\n\n";

/*
if ($email == 1)
{
	// multiple recipients
	$to = 'liamdawe@gmail.com';
	$subject = 'GOL Contact Us - FireFlowe Games sales added';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

	mail($to, $subject, "<a href=\"http://www.gamingonlinux.com/sales/\">Sales Page</a> - The auto FireFlower Games salesman has added<br />" . implode('<br/>', $games), $headers);
	echo "Mailed the latest games!\n";
}*/
echo "End of Fireflower Games import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

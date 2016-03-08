<?php
session_start();

header("Cache-Control: no-cache, must-revalidate");
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

ini_set('display_errors', 'On');
error_reporting(E_ALL);

date_default_timezone_set('UTC');

include('config.php');

include('class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('class_core.php');
$core = new core();

include('class_user.php');
$user = new user();

if(!isset($_GET['gameid']))
{
	return false;
}

if(isset($_GET['gameid']))
{
	$gameinfo = new GameInfo;
	$gameinfo->grab_sale($_GET['gameid']);
	echo json_encode($gameinfo);
}
else
{
    echo "Sorry we couldn't find the requested game!";
}
return True;
class GameInfo
{
    public $title;
    public $info;
    
    function grab_sale($game_id)
    {
	global $core, $db, $user;
	
	$db->sqlquery("SELECT s.`id`, s.`info`, s.`website`, s.`date`, s.`provider_id`, s.`drmfree`, s.`pr_key`, s.`steam`, s.savings, s.`desura`, s.pwyw, s.beat_average, s.pounds, s.pounds_original, s.dollars, s.dollars_original,s.euros, s.euros_original, s.has_screenshot, s.screenshot_filename, s.`expires`, s.`pre-order`, s.`game_assets_only`, s.bundle, s.`imported_image_link`, p.`name` FROM `game_sales` s LEFT JOIN `game_sales_provider` p ON s.provider_id = p.provider_id WHERE s.`accepted` = 1 AND s.id = ?", array($game_id));
	
	$sale = $db->fetch();
	
	$website = $sale['website'];
	if ($sale['provider_id'] == 1 && $sale['bundle'] == 1)
	{
		$steam_appid = filter_var($sale['website'], FILTER_SANITIZE_NUMBER_INT);
		$website = "http://store.steampowered.com/sub/{$steam_appid}/";
	}
	$info = "<a href=\"{$website}\">" . $sale['info'] . "</a>";
	
	$this->title = $info;
	
	// check to see if we need to put in the category name or not
	$provider = '';
	if ($sale['provider_id'] != 0)
	{
		$provider = "<a href=\"/sales/provider/{$sale['provider_id']}\">{$sale['name']}</a> > ";
	}
	
	$screenshot = '';
	
	if ($sale['has_screenshot'] == 1)
	{
		$screenshot = "<a href=\"{$sale['website']}\"><img src=\"/uploads/sales/{$sale['screenshot_filename']}\" alt=\"{$sale['info']}-screenshot\" /></a><br />";
	}
	
	$expires = '';
	if ($sale['expires'] > 0)
	{
		$expires = 'Expires in ' . $core->getRemaining($core->date, $sale['expires']) . '<br />';
	}
	
	$preorder = '';
	$drmfree = '';
	$pr_key = '';
	$steam = '';
	$desura = '';
	$game_assets = '';
	$bundle = '';
	
	if ($sale['pre-order'] == 1)
	{
		$preorder = ' <span class="label label-info">Pre-order</span> ';
	}
	
	if ($sale['drmfree'] == 1)
	{
		$drmfree = ' <span class="label label-success">DRM Free</span> ';
	}
	if ($sale['pr_key'] == 1)
	{
		$pr_key = ' <span class="label label-warning">Requires Product Key</span> ';
	}
	if ($sale['steam'] == 1)
	{
		$steam = ' <span class="label label-default">Steam Key</span> ';
	}
	if ($sale['desura'] == 1)
	{
		$desura = ' <span class="label label-info">Desura Key</span> ';
	}	
	if ($sale['game_assets_only'] == 1)
	{
		$game_assets = ' <span class="label label-warning"><a href="#" class="white-link" data-toggle="tooltip" title="Game assets only, for use with an outside engine not included in the purchase">Game Assets Only</a></span> ';
	}
	if ($sale['bundle'] == 1)
	{
		$bundle = ' <span class="label label-info">Game Bundle</span> ';
	}
	
	$savings_pounds = '';
	if ($sale['pounds_original'] != 0)
	{
		$savings = 1 - ($sale['pounds'] / $sale['pounds_original']);
		$savings_pounds = '<span class="label label-info">' . round($savings * 100) . '% off</span>';
	}
		
	else if (!empty($sale['savings']))
	{
		$savings_pounds = "<span class=\"label label-info\">{$sale['savings']}</span>";
	}
		
	$savings_dollars = '';
	if ($sale['dollars_original'] != 0)
	{
		$savings = 1 - ($sale['dollars'] / $sale['dollars_original']);
		$savings_dollars = '<span class="label label-info">' . round($savings * 100) . '% off</span>';
	}
		
	else if (!empty($sale['savings']))
	{
		$savings_dollars = "<span class=\"label label-info\">{$sale['savings']}</span>";
	}
		
	$savings_euros = '';
	if ($sale['euros_original'] != 0)
	{
		$savings = 1 - ($sale['euros'] / $sale['euros_original']);
		$savings_euros = '<span class="label label-info">' . round($savings * 100) . '% off</span>';
	}
		
	else if (!empty($sale['savings']))
	{
		$savings_euros = "<span class=\"label label-info\">{$sale['savings']}</span>";
	}
	
	$pounds_original = '';
	if ($sale['pounds_original'] > 0)
	{
		$pounds_original  = ' <strike>£' . $sale['pounds_original'] . '</strike> ';
	}
	
	$pounds = '';
	if ($sale['pounds'] == 0 && $sale['pounds_original'] > 0)
	{
		$pounds = '<strong>£0.00</strong> ' . $savings_pounds . '<br />';
	}
	
	if ($sale['pounds'] > 0)
	{
		$pounds = '<strong>£' . $sale['pounds'] . '</strong> ' . $savings_pounds . '<br />';
	}

	$dollars_original = '';	
	if ($sale['dollars_original'] > 0)
	{
		$dollars_original = ' <strike>$' . $sale['dollars_original'] . '</strike> ';
	}
	
	$dollars = '';
	if ($sale['dollars'] == 0 && $sale['dollars_original'] > 0)
	{
		$dollars = '<strong>$0.00</strong> ' . $savings_dollars . '<br />';
	}
	
	if ($sale['dollars'] > 0)
	{
		$dollars = '<strong>$' . $sale['dollars'] . '</strong> ' . $savings_dollars . '<br />';
	}
	
	$euros_original = '';
	if ($sale['euros_original'] > 0)
	{
		$euros_original = ' <strike>' . $sale['euros_original'] . '€</strike> ';
	}

	$euros = '';
	if ($sale['euros'] == 0 && $sale['euros_original'] > 0)
	{
		$euros = '<strong>0.00€</strong>' . ' ' . $savings_euros;
	}
	
	if ($sale['euros'] > 0)
	{
		$euros = '<strong>' . $sale['euros'] . '€</strong>' . ' ' . $savings_euros;
	}
	
	// sort out the pricing for each row, correct for missing signs
	if ($sale['pwyw'] == 1)
	{
		$pounds = '<span class="label label-info">Pay What You Want</span>';
		
		$pounds_original = '';

		$dollars = '';
		
		$dollars_original = '';

		$euros = '';
		
		$euros_original = '';
	}
	
	else if ($sale['beat_average'] == 1)
	{
		$pounds = '<span class="label label-info">Beat The Average</span>';
		
		$pounds_original = '';

		$dollars = '';
		
		$dollars_original = '';

		$euros = '';
		
		$euros_original = '';
	}

	$image_include = '';
	// sort out the image
	if (!empty($sale['imported_image_link']))
	{
		$image_include = "<br /><img src=\"{$sale['imported_image_link']}\" alt=\"game sale image\" class=\"img-responsive\" />";
	}
	$adsense = '';
	// if they are not in the group 6 (ad free) show the bottom ad
	if ($user->check_group(6) == false)
	{
		$adsense = '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
		<!-- single sale modal -->
		<ins class="adsbygoogle"
		style="display:inline-block;width:250px;height:250px"
		data-ad-client="ca-pub-7221863530030989"
		data-ad-slot="1977678741"></ins>
		<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
		</script><br />';
	}

	$this->info = $adsense . $preorder . $drmfree . $pr_key . $steam . $desura . $game_assets . $bundle . '<br />' .  $provider . $info . '<br />' . $expires . '<br />' . $pounds_original . $pounds . $dollars_original .  $dollars . $euros_original . $euros . '<br />' . $screenshot . $image_include;
    }
}
?>

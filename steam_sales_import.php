<?php
// Based on this https://gist.github.com/Half-Shot/60c4ec620d719ae4ef91#file-steamsalegrabber
// Rest of it by liamdawe

ini_set('display_errors',1);
error_reporting(-1);

date_default_timezone_set("Europe/London");

echo "Steam importer started on " .date('d-m-Y H:m:s'). "\n";

include('/home/prxa/public_html/includes/config.php');

include('/home/prxa/public_html/includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('/home/prxa/public_html/includes/class_core.php');
$core = new core();

$date = strtotime(gmdate("d-n-Y H:i:s"));

// get config
$db->sqlquery("SELECT `data_key`, `data_value` FROM `config`");
$fetch_config = $db->fetch_all_rows();

$config = array();
foreach ($fetch_config as $config_set)
{
	$config[$config_set['data_key']] = $config_set['data_value'];
}

$games = '';
$email = 0;

// get the list of steam linux supported titles and store them so we only query the database once
$db->sqlquery("SELECT `steam_appid`, `name`, `dlc`, `bundle` FROM `steam_list`");
$Chunks = $db->fetch_all_rows();

function GrabSaleData($countryCode)
{
	global $db, $Chunks, $config;
    
	$total = count($Chunks)-1;
	$newChunks = array();
	$GamesOnSale = array();
	$on_sale_check = array();
	
	$games_to_delete = '';
	$email = 0;
	
	foreach($Chunks as $index => $gameid){
		$newChunks[] = $gameid;
	       
		if (count($newChunks) == 50 || ( $total <= $index ) )
		{ //Are there 50 games in $newChunks
		        $AllGames = GetAllApps('GB', $newChunks);
			
			foreach($AllGames as $i => $game)
			{
				if((isset($game["data"]["price_overview"]) && $game["data"]["price_overview"]["discount_percent"] > 0) || (isset($game["data"]["price"]) && $game["data"]["price"]["discount_percent"] > 0))
				{
					$GamesOnSale[] = $game["data"]; #On Sale
					$on_sale_check[] = $game['data']['name'];
				}
			}
				
		        $newChunks = array(); //Reset chunks for new loop
		}
	}
	
	print_r($on_sale_check);
	
	$db->sqlquery("SELECT `info` FROM `game_sales` WHERE `provider_id` = 1");
	$currently_in_database = $db->fetch_all_rows();
	
	foreach($currently_in_database as $in_database)
	{
		if (!in_array($in_database['info'], $on_sale_check))
		{
			$email = 1;
			$db->sqlquery("DELETE FROM `game_sales` WHERE `info` = ? AND `provider_id` = 1", array($in_database['info']));
			
			echo "{$in_database['info']} Deleted from Steam Sales<br />";
			$games_to_delete = "{$in_database['info']}<br />";
		}
	}
	
	if ($email == 1)
	{
		// multiple recipients
		$to  = $config['contact_email']; // note the comma
		$//to .= 'muntdefems@yahoo.es';
		$subject = 'GOL Contact Us - Steam sales removed';
				    
		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= "From: GOL Contact Us <noreply@gamingonlinux.com>\r\n";

		mail($to, $subject, "<a href=\"http://www.gamingonlinux.com/sales/\">Sales Page</a> - The sales expiry cron has removed<br />$games_to_delete<br /> from Steam", $headers);
	
		echo "Mail sent!";
	}

	return $GamesOnSale;
}

function New_CC($countryCode)
{
	global $Chunks;
    
	$total = count($Chunks)-1;
	$newChunks = array();
	$Prices = array();
	foreach($Chunks as $index => $gameid)
	{
		$newChunks[] = $gameid;
	       
		if (count($newChunks) == 50 || ( $total <= $index ) )
		{
			//Are there 50 games in $newChunks
			$new_prices = Get_New_prices($countryCode, $newChunks);
			
			foreach($new_prices as $Game)
			{
				//echo '<pre>';
			    	//print_r($Game);
				if (isset($Game['data']["price_overview"]))
			    	{
					$Prices[$Game['data']['steam_appid']] = $Game['data']["price_overview"];
			       	}
			       	
			       	else if (isset($Game['data']["price"]))
			       	{
					$Prices[$Game['data']['steam_appid']] = $Game['data']["price"];
			       	}
			}
		        
		        $newChunks = array(); //Reset chunks for new loop
		}
	}
	
	return $Prices;
}

function Get_New_Prices($cc, $Chunks)
{
	$GameList = array();
	
	$GamesToUpdate = "";
	$GameSubsToUpdate = '';
	
	foreach($Chunks as $Game)
	{
		if ($Game['bundle'] == 0)
		{
			$GamesToUpdate .= $Game["steam_appid"];
			$GamesToUpdate .= ",";
		}
		
		else
		{
			$GameSubsToUpdate .= $Game["steam_appid"];
			$GameSubsToUpdate .= ",";
		}
	}
    
    	$GamesToUpdate = rtrim($GamesToUpdate, ",");
    	$GameSubsToUpdate = rtrim($GameSubsToUpdate, ",");

	$Subs_URL = 
	'http://store.steampowered.com/api/packagedetails/?packageids=' . $GameSubsToUpdate . 
	'&cc=' . $cc . '&filters=basic,price_overview';

	$Games_URL = 
	'http://store.steampowered.com/api/appdetails/?appids=' . $GamesToUpdate . 
	'&cc=' . $cc . '&filters=basic,price_overview';	
	
	if ($JSON = file_get_contents($Games_URL))
	{
		$DecodedJson = json_decode($JSON,true);
		// print_r($DecodedJson); // debug
		foreach($Chunks as $Chunk)
		{
			if (!isset($DecodedJson[$Chunk['steam_appid']]['data']['steam_appid']))
			{
				$DecodedJson[$Chunk['steam_appid']]['data']['steam_appid'] = $Chunk['steam_appid'];
			}
		}
		
		//echo '<pre>'; // debug
		//print_r($DecodedJson); // debug
		
		//print_r(array_keys($DecodedJson)); // debug
		$GameList = array_merge($GameList,$DecodedJson);
			
	}
		
	else
	{
		die("Steam API timed out!");
	}
		
	if ($JSON = file_get_contents($Subs_URL))
	{
		$DecodedJson = json_decode($JSON,true);
		foreach($Chunks as $Chunk)
		{
			if (!isset($DecodedJson[$Chunk['steam_appid']]['data']['steam_appid']))
			{
				$DecodedJson[$Chunk['steam_appid']]['data']['steam_appid'] = $Chunk['steam_appid'];
			}
		}
		//echo '<pre>';
		//print_r($DecodedJson);
		
		//print_r(array_keys($DecodedJson));
		$GameList = array_merge($GameList,$DecodedJson);
			
	}
		
	else
	{
		die("Steam API timed out!");
	}
	
	return $GameList;	
	
	//return $Prices;
}
 
function GetAllApps($cc, $Chunks)
{
	global $db;
    
	$GameList = array();
	
	$GamesToUpdate = "";
	$GameSubsToUpdate = '';
	
	foreach($Chunks as $Game)
	{
		if ($Game['bundle'] == 0)
		{
			$GamesToUpdate .= $Game["steam_appid"];
			$GamesToUpdate .= ",";
		}
		
		else
		{
			$GameSubsToUpdate .= $Game["steam_appid"];
			$GameSubsToUpdate .= ",";
		}
	}
	
    	$GamesToUpdate = rtrim($GamesToUpdate, ",");
    	$GameSubsToUpdate = rtrim($GameSubsToUpdate, ",");

	$Subs_URL = 
	'http://store.steampowered.com/api/packagedetails/?packageids=' . $GameSubsToUpdate . 
	'&cc=' . $cc . '&filters=basic,price_overview';

	$Games_URL = 
	'http://store.steampowered.com/api/appdetails/?appids=' . $GamesToUpdate . 
	'&cc=' . $cc . '&filters=basic,price_overview';		
		
	if ($JSON = file_get_contents($Games_URL))
	{
		$DecodedJson = json_decode($JSON,true);
		//print_r($DecodedJson);
		foreach($Chunks as $Chunk)
		{
			if (!isset($DecodedJson[$Chunk['steam_appid']]['data']['steam_appid']))
			{
				$DecodedJson[$Chunk['steam_appid']]['data']['steam_appid'] = $Chunk['steam_appid'];
			}
		}
		
		//echo '<pre>';
		//print_r($DecodedJson);
		
		//print_r(array_keys($DecodedJson));
		$GameList = array_merge($GameList,$DecodedJson);
			
	}
		
	else
	{
		die("Steam API timed out!<br />$Games_URL");
	}
		
	if ($JSON = file_get_contents($Subs_URL))
	{
		$DecodedJson = json_decode($JSON,true);
		foreach($Chunks as $Chunk)
		{
			if (!isset($DecodedJson[$Chunk['steam_appid']]['data']['steam_appid']))
			{
				$DecodedJson[$Chunk['steam_appid']]['data']['steam_appid'] = $Chunk['steam_appid'];
			}
		}
		//echo '<pre>';
		//print_r($DecodedJson);
		
		//print_r(array_keys($DecodedJson));
		$GameList = array_merge($GameList,$DecodedJson);
			
	}
		
	else
	{
		die("Steam API timed out!");
	}
	
	return $GameList;
}
$Games = GrabSaleData("GB");
$USPrices = New_CC("US");
$EUPrices = New_CC("ES");

 echo '<pre>'; // debug
var_dump($USPrices); // debug
//var_dump($EUPrices); // debug

foreach ($Games as $game)
{
	echo $game['name'] . '<br />';
	
	if (isset($game['price_overview']))
	{
		echo $game['price_overview']['currency'] . '<br />';
		echo $game['price_overview']['initial'] . '<br />';
	
		// get usd
		echo $USPrices[$game['steam_appid']]['currency'] . '$' . $USPrices[$game['steam_appid']]['initial'] / 100 . '<br />';
		echo $EUPrices[$game['steam_appid']]['currency'] . '';
	
		// get euro prices
	
		// turn pound prices into correct decimals from whole numbers
		$pounds_original = $game['price_overview']['initial'] / 100;
		$pounds_sale = $game['price_overview']['final'] / 100;
	
		$dollars_original = $USPrices[$game['steam_appid']]['initial'] / 100;
		$dollars_sale = $USPrices[$game['steam_appid']]['final'] / 100;
	
		$euros_original = $EUPrices[$game['steam_appid']]['initial'] / 100;
		$euros_sale = $EUPrices[$game['steam_appid']]['final'] / 100;
	}
	
	if (isset($game['price']))
	{
		echo $game['price']['currency'] . '<br />';
		echo $game['price']['initial'] . '<br />';
	
		// get usd
		echo $USPrices[$game['steam_appid']]['currency'] . '$' . $USPrices[$game['steam_appid']]['initial'] / 100 . '<br />';
		echo $EUPrices[$game['steam_appid']]['currency'] . '';
	
		// get euro prices
	
		// turn pound prices into correct decimals from whole numbers
		$pounds_original = $game['price']['initial'] / 100;
		$pounds_sale = $game['price']['final'] / 100;
	
		$dollars_original = $USPrices[$game['steam_appid']]['initial'] / 100;
		$dollars_sale = $USPrices[$game['steam_appid']]['final'] / 100;
	
		$euros_original = $EUPrices[$game['steam_appid']]['initial'] / 100;
		$euros_sale = $EUPrices[$game['steam_appid']]['final'] / 100;
	}
		
	// search if that title exists
	$db->sqlquery("SELECT `info`, `provider_id` FROM `game_sales` WHERE `info` = ?", array($game['name']));
		
	// if it does exist, make sure it's not from Steam already
	$check = 1;
	if ($db->num_rows() >= 1)
	{
		while ($test = $db->fetch())
		{
			// set the check to 0 as it already exists from this website
			if ($test['provider_id'] == 1)
			{
				$check = 0;
				
			} 
		}
			
			
		// tell the outcome
		if ($check == 0)
		{
			echo "\tI already know about this game, and Steam told me about it\n";
		}
			
		else
		{
			echo "\tI already know about this game, however Steam wasn't the one who told me about it\n";
		}
	} 
		
	else 
	{
		echo "\tI didn't know about this game before.\n";
	}
		
	// we need to add it as we didn't find it from Steam
	if ($check == 1)
	{
		$bundle = 0;
		if (isset($game['apps']))
		{
			$bundle = 1;
		}
		
		$db->sqlquery("INSERT INTO `game_sales` SET `info` = ?, `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = 1, `pounds_original` = ?, `pounds` = ?, `dollars_original` = ?, `dollars` = ?, `euros_original` = ?, `euros` = ?, `steam` = 1, `bundle` = ?", array($game['name'], "http://store.steampowered.com/app/{$game['steam_appid']}/", $core->date, $pounds_original, $pounds_sale, $dollars_original, $dollars_sale, $euros_original, $euros_sale, $bundle));

		$sale_id = $db->grab_id();
			
		echo "\tAdded this game to the sales DB with id: " . $sale_id . ".\n";
			
		$games .= $game['name'] . '<br />';
		
		$email = 1;
	} 
		
	// if we already have it, just set the price and % off to the current amount (in-case it's different) or if they now have steam/desura keys
	else 
	{
		$db->sqlquery("UPDATE `game_sales` SET `pounds_original` = ?, `pounds` = ?, `dollars_original` = ?, `dollars` = ?, `euros_original` = ?, `euros` = ?, `steam` = 1 WHERE `info` = ? AND `provider_id` = 1", array($pounds_original, $pounds_sale, $dollars_original, $dollars_sale, $euros_original, $euros_sale, $game['name']));
			
		echo "  Updated " .$game['name'] . " with current information.\n";
	}
}
echo "End of Steam import @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";
?>

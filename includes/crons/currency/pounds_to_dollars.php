<?php
// taken from an answer on http://stackoverflow.com/questions/19838049/google-currency-converter-has-changed-its-url-but-not-getting-same-result#comment39452263_23426411

echo "Currency conversion started on " .date('d-m-Y H:m:s'). "\n";

include('/home/gamingonlinux/public_html/includes/config.php');

include('/home/gamingonlinux/public_html/includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('/home/gamingonlinux/public_html/includes/class_core.php');
$core = new core();

$date = strtotime(gmdate("d-n-Y H:i:s"));

function currency($from, $to, $amount)
{
   $content = file_get_contents('https://www.google.com/finance/converter?a='.$amount.'&from='.$from.'&to='.$to);

   $doc = new DOMDocument;
   @$doc->loadHTML($content);
   $xpath = new DOMXpath($doc);

   $result = $xpath->query('//*[@id="currency_converter_result"]/span')->item(0)->nodeValue;

   return str_replace(' '.$to, '', $result);
}

// convert pounds to dollars
$db->sqlquery("SELECT `pounds`, `id` FROM `game_sales` WHERE `pounds` > 0 and `dollars` = 0");
$get = $db->fetch_all_rows();
foreach ($get as $price)
{
	$dollars = currency('GBP', 'USD', $price['pounds']);
	$dollars = round($dollars, 2);
	$db->sqlquery("UPDATE `game_sales` SET `dollars` = ? WHERE `id` = ?", array($dollars, $price['id']));
}

// convert pounds_original to dollars_original
$db->sqlquery("SELECT `pounds_original`, `id` FROM `game_sales` WHERE `pounds_original` > 0 and `dollars_original` = 0");
$get = $db->fetch_all_rows();
foreach ($get as $price)
{
	$dollars = currency('GBP', 'USD', $price['pounds_original']);
	$dollars = round($dollars, 2);
	$db->sqlquery("UPDATE `game_sales` SET `dollars_original` = ? WHERE `id` = ?", array($dollars, $price['id']));
}

echo "End of currency conversion @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";
?>

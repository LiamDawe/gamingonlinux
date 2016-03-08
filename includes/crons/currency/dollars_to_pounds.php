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

// convert dollars to pounds
$db->sqlquery("SELECT `dollars`, `id` FROM `game_sales` WHERE `dollars` > 0 and `pounds` = 0");
$get = $db->fetch_all_rows();
foreach ($get as $price)
{
	$pounds = currency('USD', 'GBP', $price['dollars']);
	$pounds = round($pounds, 2);
	$db->sqlquery("UPDATE `game_sales` SET `pounds` = ? WHERE `id` = ?", array($pounds, $price['id']));
}

// convert dollars_original to pounds_original
$db->sqlquery("SELECT `dollars_original`, `id` FROM `game_sales` WHERE `dollars_original` > 0 and `pounds_original` = 0");
$get = $db->fetch_all_rows();
foreach ($get as $price)
{
	$pounds = currency('USD', 'GBP', $price['dollars_original']);
	$pounds = round($pounds, 2);
	$db->sqlquery("UPDATE `game_sales` SET `pounds_original` = ? WHERE `id` = ?", array($pounds, $price['id']));
}

echo "End of currency conversion @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";
?>

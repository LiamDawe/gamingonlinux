<?php
ini_set("memory_limit", "-1");

define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/public_html');
define("THIS_ROOT", dirname( dirname( dirname(__FILE__) ) ) . '/crons');

// http://simplehtmldom.sourceforge.net/
include(THIS_ROOT . '/simple_html_dom.php');

require APP_ROOT . '/includes/cron_bootstrap.php';

$game_sales = new game_sales($dbl, $templating = NULL, $user = NULL, $core);

echo "Steam top 250 parser started " .date('d-m-Y H:m:s'). "\n";

$url = "https://steam250.com/hidden_gems";

$html = new simple_html_dom();
$html->load_file($url);


$item_list = $html->find('div.main div[id] a[class=nix]');
$total_linux = count($item_list);

echo 'Total Linux: ' . $total_linux . PHP_EOL;

echo "End of Steam top 250 parser @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";

<?php
/*
TODO
- Insert into database
*/
define("APP_ROOT", dirname( dirname( dirname( dirname(__FILE__) ) ) ));

// http://simplehtmldom.sourceforge.net/
include(APP_ROOT . '/includes/crons/sales/simple_html_dom.php');

require APP_ROOT . '/includes/bootstrap.php';

$game_sales = new game_sales($dbl, $templating, $user, $core);

echo "Wine Games importer started on " .date('d-m-Y H:m:s'). "\n";

$updated_list = [];
$new_list = [];

$page = 1;
$stop = 0;

$url = "https://appdb.winehq.org/objectManager.php?sClass=application&sTitle=Browse%20Applications&sOrderBy=appName&bAscending=true&iItems&PerPage=25&iPage=";

$html = core::file_get_contents_curl($url . $page);

	libxml_use_internal_errors(true);

	$main_list = new DOMDocument;
	$main_list->loadHTML($html);

	$xpath = new DOMXPath($main_list);
	$values = $xpath->query("//table[contains(@class, 'whq-table') and contains(@class, 'whq-table-full')]/tr/td[1]/a");

	foreach ($values as $value)
	{
		$name = $game_sales->clean_title($value->nodeValue);
		echo '<strong>Name</strong><br />';
		echo $name . '<br />';
		$appdb_link = $value->getAttribute("href");
		echo $appdb_link . '<br />';

		$version_html = core::file_get_contents_curl($appdb_link);
		
		$version_list = new DOMDocument;
		$version_list->loadHtml($version_html);

		$xpath_r = new DOMXPath($version_list);
		$versions = $xpath_r->query("//table[contains(@class, 'whq-table')]/tr");

		foreach ($versions as $version)
		{
			$children = $xpath_r->query('*', $version);
			$app_version = trim($children->item(0)->nodeValue);
			$app_status = $children->item(2)->nodeValue;
			$wine_version = $children->item(3)->nodeValue;
			echo '<strong>Version</strong>: ' . $app_version . ' <strong>Status</strong>: ' . $app_status . ' <strong>Wine Version</strong>: ' . $wine_version . '<br />';
		}
	}

	libxml_clear_errors();

	die();

$total_updated = count($updated_list);
$total_added = count($new_list);

echo 'Total updated: ' . $total_updated . ". Total new: ".$total_added.". Last page: ". $page . "\n";

//$dbl->run("UPDATE `crons` SET `last_ran` = ?, `data` = ? WHERE `name` = 'steam_sales'", [core::$sql_date_now, $total_on_sale]);

echo "End of Wine Games importer @ " . date('d-m-Y H:m:s') . ".\nHave a nice day.\n";
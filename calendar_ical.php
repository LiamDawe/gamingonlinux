<?php
header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=calendar.ical');

define("APP_ROOT", dirname(__FILE__));

require APP_ROOT . "/includes/bootstrap.php";

// the iCal date format. Note the Z on the end indicates a UTC timestamp.
define('DATE_ICAL', 'Ymd\THis\Z');

$year = date("Y");
if (isset($_GET['year']) && is_numeric($_GET['year']))
{
	$year = $_GET['year'];
}

// Escapes a string of characters
function escapeString($string) {
  return preg_replace('/([\,;])/','\\\$1', $string);
}

$output = "BEGIN:VCALENDAR\r\nMETHOD:PUBLISH\r\nVERSION:2.0\r\nPRODID:-//Gaming On Linux//Release Calendar//EN\r\n";

$items = $dbl->run("SELECT `id`, `date`, `name`, `link`, `best_guess` FROM `calendar` WHERE YEAR(date) = $year AND `approved` = 1 ORDER BY `date` ASC")->fetch_all();

// loop over events
foreach ($items as $item)
{
	$url = '';
	if (!empty($item['link']))
	{
		$url = 'URL:' . escapeString($item['link']) . "\r\nDESCRIPTION:" . escapeString($item['link']) . "\r\n";
	}

	// make the name nice
	$name = htmlspecialchars($item['name']);
	$name = preg_replace("/(™|®|©|&trade;|&reg;|&copy;|&#8482;|&#174;|&#169;)/", "", $name);

	if ($item['best_guess'] == 1)
	{
		$name = $name . ' (Best Guess)';
	}

	$output .="BEGIN:VEVENT\r\nSUMMARY:GOL > " . $name . "\r\nUID:{$item['id']}\r\n" . $url . "DTSTART;VALUE=DATE:" . date('Ymd', strtotime($item['date'])) . "\r\n" . 'DTEND;VALUE=DATE:' . date('Ymd', strtotime($item['date'] . '+ 1 day')) . "\r\nLAST-MODIFIED:" . date(DATE_ICAL, strtotime(date("Y-m-d"))) . "\r\nEND:VEVENT\r\n";
}

// close calendar
$output .= "END:VCALENDAR";

echo $output;
?>
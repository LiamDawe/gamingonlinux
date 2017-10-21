<?php
session_start();

header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=calendar.ical');

// we dont need the whole bootstrap
require dirname(__FILE__) . "/includes/loader.php";
include dirname(__FILE__) . '/includes/config.php';
$dbl = new db_mysql();

// the iCal date format. Note the Z on the end indicates a UTC timestamp.
define('DATE_ICAL', 'Ymd\THis\Z');

// Escapes a string of characters
function escapeString($string) {
  return preg_replace('/([\,;])/','\\\$1', $string);
}

$output = "BEGIN:VCALENDAR\r\nMETHOD:PUBLISH\r\nVERSION:2.0\r\nPRODID:-//Gaming On Linux//Livestream Calendar//EN\r\n";

$items = $dbl->run("SELECT `row_id`, `title`, `date`, `end_date`, `date_created`, `community_stream` FROM `livestreams` ORDER BY `date` ASC")->fetch_all();

// loop over events
foreach ($items as $item)
{
	$url = '';
	if (!empty($item['link']))
	{
		$url = 'URL:' . escapeString($item['link']) . "\r\nDESCRIPTION:" . escapeString($item['link']) . "\r\n";
	}

	$streamer = "GOL livestream > ";
	if ($item['community_stream'] == 1)
	{
		$streamer = 'Community livestream > ';
	}

	$output .="BEGIN:VEVENT\r\nUID:{$item['row_id']}@gamingonlinux.com\r\nDTSTAMP:" . date("Ymd\THis", strtotime($item['date_created'])) . "Z\r\n" . "DTSTART:" . date("Ymd\THis", strtotime($item['date'])) . "Z\r\n" . 'DTEND:' . date("Ymd\THis", strtotime($item['end_date'])) . "Z\r\nSUMMARY:" . $streamer . $item['title'] . "\r\nEND:VEVENT\r\n";
}

// close calendar
$output .= "\r\nEND:VCALENDAR";

echo $output;
?>

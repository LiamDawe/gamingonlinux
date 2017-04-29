<?php
header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=calendar.ical');

$file_dir = dirname(__FILE__);

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

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

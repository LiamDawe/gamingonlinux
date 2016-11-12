<?php
header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=calendar.ical');

include('includes/config.php');

include('includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('includes/class_core.php');
$core = new core();

// the iCal date format. Note the Z on the end indicates a UTC timestamp.
define('DATE_ICAL', 'Ymd\THis\Z');

if (!isset($_GET['year']))
{
	$year = date("Y");
}
else if (isset($_GET['year']) && empty($_GET['year'])) // stupid google bot!
{
	$year = $_GET['year'];
}
else if (isset($_GET['year']) && is_numeric($_GET['year']))
{
	$year = $_GET['year'];
}

// Escapes a string of characters
function escapeString($string) {
  return preg_replace('/([\,;])/','\\\$1', $string);
}

$output = "BEGIN:VCALENDAR\r\nMETHOD:PUBLISH\r\nVERSION:2.0\r\nPRODID:-//Gaming On Linux//Livestream Calendar//EN\r\n";

$db->sqlquery("SELECT `row_id`, `title`, `date`, `end_date`, `date_created` FROM `livestreams` ORDER BY `date` ASC");

// loop over events
while ($item = $db->fetch())
{
	$url = '';
	if (!empty($item['link']))
	{
		$url = 'URL:' . escapeString($item['link']) . "\r\nDESCRIPTION:" . escapeString($item['link']) . "\r\n";
	}

	$output .="BEGIN:VEVENT\r\nUID:{$item['row_id']}@gamingonlinux.com\r\nDTSTAMP:" . date("Ymd\THis", strtotime($item['date_created'])) . "Z\r\n" . "DTSTART:" . date("Ymd\THis", strtotime($item['date'])) . "Z\r\n" . 'DTEND:' . date("Ymd\THis", strtotime($item['end_date'])) . "Z\r\nSUMMARY:GOL Livestream > " . $item['title'] . "\r\nEND:VEVENT\r\n";
}

// close calendar
$output .= "\r\nEND:VCALENDAR";

echo $output;
?>

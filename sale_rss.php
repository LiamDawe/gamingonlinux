<?php
include('includes/config.php');

include('includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('includes/class_core.php');
$core = new core();

$db->sqlquery("SELECT `date`
FROM `game_sales`
WHERE `accepted` = 1
ORDER BY `id` DESC
LIMIT 1");

$last_time = $db->fetch();

$last_date = gmdate("D, d M Y H:i:s O", $last_time['date']);

$last_date = gmdate("D, d M Y H:i:s O", $last_time['date']);

$output = "<?xml version=\"1.0\"?>
<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
	<channel>
		<title>GamingOnLinux.com Latest Sale List</title>
		<link>http://www.gamingonlinux.com/sales/</link>
		<atom:link href=\"http://www.gamingonlinux.com/sale_rss.php\" rel=\"self\" type=\"application/rss+xml\" />
		<language>en-us</language>
		<description></description>
		<pubDate>$last_date</pubDate>
		<lastBuildDate>$last_date</lastBuildDate>";

$db->sqlquery("SELECT `id`, `info`, `website`, `date` FROM `game_sales` WHERE `accepted` = 1 ORDER BY `id` DESC LIMIT 15");
while ($line = $db->fetch())
{
	$date = date("D, d M Y H:i:s O", $line['date']);

	$name = html_entity_decode($line['info'], ENT_QUOTES, "UTF-8");
	$name = str_replace("&", "&amp;", $name);

	$output .= "
		<item>
			<title>{$name}</title>
			<link>http://www.gamingonlinux.com/sales/{$line['id']}/</link>
			<pubDate>{$date}</pubDate>
			<guid>http://www.gamingonlinux.com/sales/{$line['id']}/</guid>
		</item>";
}

$output .= "
	</channel>
</rss>";

header("Content-Type: application/rss+xml");
header("Cache-Control: no-cache, must-revalidate");
echo $output;
?>

<?php
$file_dir = dirname(__FILE__);

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir. '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/bbcode.php');

header("Content-Type: application/rss+xml");
header("Cache-Control: max-age=3600");

$output = '<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom" xml:lang="en-GB">

<channel>
<title>Gaming On Linux Podcast</title>
<link>https://www.gamingonlinux.com/podcast_rss_mp3.php</link>
<atom:link href="https://www.gamingonlinux.com/podcast_rss_mp3.php" rel="self" type="application/rss+xml"/>
<language>en</language>
<description>Linux gaming chat and general banter</description>
<itunes:author>gamingonlinux.com</itunes:author>
<itunes:summary>Talking about the latest games for Linux and general chat about Linux and gaming</itunes:summary>
<itunes:owner>
<itunes:name>Liam Dawe</itunes:name>
<itunes:email>contact@gamingonlinux.com</itunes:email>
</itunes:owner>
<itunes:explicit>no</itunes:explicit>
<itunes:category text="Technology">
</itunes:category>';

$db->sqlquery("SELECT a.* FROM `articles` a LEFT JOIN `article_category_reference` c ON c.article_id = a.article_id WHERE a.`active` = 1 AND c.`category_id` = 97 ORDER BY a.`date` DESC LIMIT 15");

$articles = $db->fetch_all_rows();

foreach ($articles as $line)
{
	// make date human readable
	$date = date("D, d M Y H:i:s O", $line['date']);
	$nice_title = $core->nice_title($line['title']); // ~~ Piratelv @ 28/08/13

	$title = str_replace("&#039;", '\'', $line['title']);
	$title = str_replace("&", "&amp;", $title);
	$title = $title;


	preg_match("/\[mp3](.+?)\[\/mp3\]/is", $line['text'], $matches);
	if (isset($matches[1]))
	{
		$output .= "
		<item>
		<title>$title</title>
		<description>{$line['tagline']}</description>
		<enclosure url=\"{$matches[1]}\" type=\"audio/mpeg\" />
		<pubDate>$date</pubDate>
		<guid>https://www.gamingonlinux.com/$nice_title.{$line['article_id']}/</guid>
		</item>";
	}
}

$output .= "
</channel>
</rss>";

echo $output;
?>

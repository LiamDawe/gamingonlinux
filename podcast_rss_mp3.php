<?php
$file_dir = dirname(__FILE__);

define('golapp', TRUE);

// we dont need the whole bootstrap
require dirname(__FILE__) . "/includes/loader.php";
include dirname(__FILE__) . '/includes/config.php';
$dbl = new db_mysql();
$core = new core($dbl);
$bbcode = new bbcode($dbl, $core, $user);

header("Content-Type: application/rss+xml");
header("Cache-Control: max-age=3600");

$output = '<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom" xml:lang="en-GB">

<channel>
<title>Gaming On Linux Podcasts</title>
<link>https://www.gamingonlinux.com/podcast_rss_mp3.php</link>
<atom:link href="https://www.gamingonlinux.com/podcast_rss_mp3.php" rel="self" type="application/rss+xml"/>
<language>en</language>
<description>Linux gaming chat and general banter</description>
<image>
<url>https://www.gamingonlinux.com/templates/default/images/favicons/apple-touch-icon-144x144.png</url>
<title>Gaming On Linux Podcasts</title>
<link>https://www.gamingonlinux.com/</link>
</image>
<itunes:author>gamingonlinux.com</itunes:author>
<itunes:summary>Talking about the latest games for Linux and general chat about Linux and gaming</itunes:summary>
<itunes:owner>
<itunes:name>Liam Dawe</itunes:name>
<itunes:email>contact@gamingonlinux.com</itunes:email>
</itunes:owner>
<itunes:explicit>no</itunes:explicit>
<itunes:category text="Technology">
</itunes:category>';

$articles = $dbl->run("SELECT a.* FROM `articles` a LEFT JOIN `article_category_reference` c ON c.article_id = a.article_id WHERE a.`active` = 1 AND c.`category_id` = 97 ORDER BY a.`date` DESC LIMIT 15")->fetch_all();

foreach ($articles as $line)
{
	// make date human readable
	$date = date("D, d M Y H:i:s O", $line['date']);
	$nice_title = core::nice_title($line['title']);

	$title = str_replace("&#039;", '\'', $line['title']);
	$title = str_replace("&", "&amp;", $title);
	$title = $title;

	preg_match("/<a href=\"(.+\.mp3)/m", $line['text'], $matches);

	$size = filesize($file_dir . str_replace("https://www.gamingonlinux.com",'',$matches[1]));

	if (isset($matches[1]))
	{
		$output .= "
		<item>
		<title>$title</title>
		<description>{$line['tagline']}</description>
		<enclosure url=\"{$matches[1]}\" type=\"audio/mpeg\" length=\"$size\" />
		<pubDate>$date</pubDate>
		<guid>https://www.gamingonlinux.com/articles/$nice_title.{$line['article_id']}/</guid>
		</item>";
	}
}

$output .= "
</channel>
</rss>";

echo $output;
?>

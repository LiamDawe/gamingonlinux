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
header("Cache-Control: no-cache, must-revalidate");

$format = '?format=mp3';
if (isset($_GET['format']) && $_GET['format'] == 'ogg')
{
	$format = '?format=ogg';
}

$output = '<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom" xml:lang="en-GB">

<channel>
<title>Gaming On Linux Podcasts</title>
<link>http://www.gamingonlinux.com/</link>
<atom:link href="http://www.gamingonlinux.com/podcast_rss.php'.$format.'" rel="self" type="application/rss+xml"/>
<language>en</language>
<description>Linux gaming chat and general banter</description>
<image>
	<url>http://www.gamingonlinux.com/templates/default/images/favicons/podcast.png</url>
	<title>Gaming On Linux Podcasts</title>
	<link>http://www.gamingonlinux.com/</link>
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

$articles = $dbl->run("SELECT a.`date`, a.`title`, a.`slug`, a.`tagline`, a.`text` FROM `articles` a LEFT JOIN `article_category_reference` c ON c.article_id = a.article_id WHERE a.`active` = 1 AND c.`category_id` = 97 ORDER BY a.`date` DESC LIMIT 30")->fetch_all();

function retrieve_remote_file_size($url){
	$ch = curl_init($url);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, TRUE);
	curl_setopt($ch, CURLOPT_NOBODY, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	$data = curl_exec($ch);
	$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

	curl_close($ch);
	return $size;
}

foreach ($articles as $line)
{
	// make date human readable
	$date = date("D, d M Y H:i:s O", $line['date']);
	$nice_title = core::nice_title($line['title']);

	$title = str_replace("&#039;", '\'', $line['title']);
	$title = str_replace("&", "&amp;", $title);
	$title = $title;

	if ($format == '?format=mp3')
	{
		preg_match("/<a href=\"(.+\.mp3)/m", $line['text'], $matches);
	}
	else if ($format == '?format=ogg')
	{
		preg_match("/<a href=\"(.+\.ogg)/m", $line['text'], $matches);
	}

	$item_url = str_replace("https",'http',$matches[1]);
	$size = retrieve_remote_file_size($item_url);

	$article_link = $core->config('website_url') . date('Y', $line['date']) . '/' . date('m', $line['date']) . '/' . $line['slug'];

	if (isset($matches[1]))
	{
		$output .= "
		<item>
		<title>$title</title>
		<description>{$line['tagline']}</description>
		<enclosure url=\"$item_url\" type=\"audio/mpeg\" length=\"$size\" />
		<pubDate>$date</pubDate>
		<guid>".$article_link."</guid>
		</item>";
	}
}

$output .= "
</channel>
</rss>";

echo $output;
?>

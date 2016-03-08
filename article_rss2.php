<?php
include('includes/config.php');

include('includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('includes/class_core.php');
$core = new core();

include('includes/bbcode.php');
$sql_join = '';
$sql_addition = '';
if (isset($_GET['section']) && $_GET['section'] == 'overviews')
{
	$sql_join = ' LEFT JOIN `article_category_reference` c ON c.article_id = a.article_id';
	$sql_addition = ' AND c.`category_id` = 63';
}

$db->sqlquery("SELECT a.`date`
FROM `articles` a $sql_join
WHERE a.`active` = 1 $sql_addition
ORDER BY a.`date` DESC
LIMIT 1");

$last_time = $db->fetch();

header("Content-Type: application/rss+xml");
header("Cache-Control: max-age=3600");

$last_date = gmdate("D, d M Y H:i:s O", $last_time['date']);

$output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
	<channel>
		<title>GamingOnLinux.com Latest Articles</title>
		<link>http://www.gamingonlinux.com/</link>
		<atom:link href=\"//www.gamingonlinux.com/article_rss.php\" rel=\"self\" type=\"application/rss+xml\" />
		<language>en-us</language>
		<description></description>
		<pubDate>$last_date</pubDate>
		<lastBuildDate>$last_date</lastBuildDate>";

$db->sqlquery("SELECT a.*
FROM `articles` a $sql_join
WHERE a.`active` = 1 $sql_addition
ORDER BY a.`date` DESC
LIMIT 15");

while ($line = $db->fetch())
{
	// make date human readable
	$date = date("D, d M Y H:i:s O", $line['date']);
	$nice_title = preg_replace('/<[^>]+>/', '', $core->nice_title($line['title']) ); // ~~ Piratelv @ 28/08/13
	$description = $line['tagline'];
	$find = array(
		"/\[url\=(.+?)\](.+?)\[\/url\]/is",
		"/\[url\](.+?)\[\/url\]/is",
		"/\[b\](.+?)\[\/b\]/is",
		"/\[i\](.+?)\[\/i\]/is",
		"/\[u\](.+?)\[\/u\]/is",
		"/\[img\](.+?)\[\/img\]/is",
		"/\[img=([0-9]+)x([0-9]+)\](.+?)\[\/img\]/is",
		"/\[youtube\](.+?)\[\/youtube\]/is",
        	"/\[color\=(.+?)\](.+?)\[\/color\]/is",
        	"/\[font\=(.+?)\](.+?)\[\/font\]/is",
        	"/\[size\=(.+?)\](.+?)\[\/size\]/is"
	);

	$replace = array(
		"$2",
		"$1",
		"$1",
		"$1",
		"$1",
		"",
		"",
		"",
		"$2",
		"$2",
		"$2"
	);

	$description = preg_replace($find, $replace, $description);

	$description = htmlentities($description, ENT_QUOTES, "UTF-8");

	$top_image = '';
	if ($line['article_top_image'] == 1)
	{
		$top_image = "<img src=\"//www.gamingonlinux.com/uploads/articles/topimages/{$line['article_top_image_filename']}\"><br />";
	}
	if (!empty($line['tagline_image']))
	{
		$top_image = "<img src=\"//www.gamingonlinux.com/uploads/articles/tagline_images/thumbnails/{$line['tagline_image']}\"><br />";
	}

	$description = $description . '<br />' . $top_image;

	$title = str_replace("&#039;", '\'', $line['title']);
	$title = str_replace("&", "&amp;", $title);
	$title = ucwords($title);

	$output .= "
		<item>
			<title>{$title}</title>
			<link>//www.gamingonlinux.com/articles/$nice_title.{$line['article_id']}</link>
			<description><![CDATA[{$description}]]></description>
			<pubDate>{$date}</pubDate>
			<guid>//www.gamingonlinux.com/articles/$nice_title.{$line['article_id']}</guid>
		</item>";
}

$output .= "
	</channel>
</rss>";

echo $output;
?>

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
		<atom:link href=\"https://www.gamingonlinux.com/article_rss.php\" rel=\"self\" type=\"application/rss+xml\" />
		<language>en-us</language>
		<description></description>
		<pubDate>$last_date</pubDate>
		<lastBuildDate>$last_date</lastBuildDate>";

$db->sqlquery("SELECT a.*, u.username
FROM `articles` a LEFT JOIN `users` u ON a.author_id = u.user_id $sql_join
WHERE a.`active` = 1 $sql_addition
ORDER BY a.`date` DESC
LIMIT 15");

$articles = $db->fetch_all_rows();

foreach ($articles as $line)
{
	// make date human readable
	$date = date("D, d M Y H:i:s O", $line['date']);
	$nice_title = preg_replace('/<[^>]+>/', '', $core->nice_title($line['title']) ); // ~~ Piratelv @ 28/08/13

	if ($line['article_top_image'] == 1)
	{
		$tagline_bbcode = $line['article_top_image_filename'];
	}

	if (!empty($line['tagline_image']))
	{
		$tagline_bbcode  = $line['tagline_image'];
	}
	else
	{
		$tagline_bbcode = ""; //Piratelv @ 05/06/14 -- Some older articles didn't have this
	}

	$line['text'] = bbcode($line['text'], 1, 1, $tagline_bbcode, 1);

	$title = str_replace("&#039;", '\'', $line['title']);
	$title = str_replace("&", "&amp;", $title);
	$title = $title;

	$categories_list = array();
	// sort out the categories (tags)
	$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ?", array($line['article_id']));
	while ($get_categories = $db->fetch())
	{
		$categories_list[] = $get_categories['category_name'];
	}

	$cats = implode(',', $categories_list);

	if ($line['author_id'] == 0)
	{
		if (empty($line['guest_username']))
		{
			$username = 'Guest';
		}

		else
		{
			$username = $line['guest_username'];
		}
	}

	else
	{
		$username = $line['username'];
	}

	$output .= "
		<item>
			<title>{$title}</title>
			<author>$username</author>
			<link>http://www.gamingonlinux.com/articles/$nice_title.{$line['article_id']}</link>
			<description><![CDATA[Tags:$cats<br />{$line['text']}<br /><br />Content from <a href=\"https://www.gamingonlinux.com\">GamingOnLinux.com</a>]]></description>
			<pubDate>{$date}</pubDate>
			<guid>http://www.gamingonlinux.com/articles/$nice_title.{$line['article_id']}</guid>
		</item>";
}

$output .= "
	</channel>
</rss>";

echo $output;
?>

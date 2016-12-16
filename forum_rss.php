<?php
include('includes/config.php');

include('includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('includes/class_core.php');
$core = new core();

include('includes/class_template.php');
$templating = new template(core::config('template'));

if (core::config('articles_rss') == 1)
{
	$now = date("D, d M Y H:i:s O");

	$output = "<?xml version=\"1.0\"?>
	<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
		<channel>
			<title>Latest forum posts</title>
			<link>https://www.gamingonlinux.com/forum_rss.php</link>
			<atom:link href=\"". core::config('website_url') . "forum_rss.php\" rel=\"self\" type=\"application/rss+xml\" />
			<language>en-us</language>
			<description></description>
			<pubDate>$now</pubDate>
			<lastBuildDate>$now</lastBuildDate>";

	$db->sqlquery("SELECT `topic_id`, `topic_title`, `last_post_date` FROM `forum_topics` WHERE `approved` = 1 ORDER BY `last_post_date` DESC LIMIT 15");

	while ($line = $db->fetch())
	{
		// make date human readable
		$date = date("D, d M Y H:i:s O", $line['last_post_date']);

		$title = htmlspecialchars($line['topic_title'], ENT_QUOTES, 'UTF-8');

		$output .= "
			<item>
				<title>{$title}</title>
				<link>". core::config('website_url') . "forum/topic/{$line['topic_id']}/</link>
				<pubDate>{$date}</pubDate>
				<guid>". core::config('website_url') . "forum/topic/{$line['topic_id']}/</guid>
			</item>";
	}

	$output .= "
		</channel>
	</rss>";

	header("Content-Type: application/rss+xml");
	echo $output;
}

else
{
	echo 'RSS feed is not active!';
}
?>

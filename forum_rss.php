<?php
include('includes/config.php');

include('includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('includes/class_core.php');
$core = new core();

// get config
$db->sqlquery("SELECT * FROM `config`");
$fetch_config = $db->fetch_all_rows();

$config = array();
foreach ($fetch_config as $config_set)
{
	$config[$config_set['data_key']] = $config_set['data_value'];
}

include('includes/class_template.php');
$templating = new template($config['template']);

if ($config['articles_rss'] == 1)
{
	$limit = $config['rss_article_limit'];
	if (isset($_GET['limit']) && is_numeric($_GET['limit']))
	{
		$limit = $_GET['limit'];
		
		if ($limit > 50)
		{
			$limit = 50;
		}
	}
		
	$now = date("D, d M Y H:i:s O");
		
	$output = "<?xml version=\"1.0\"?>
	<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
		<channel>
			<title>Latest forum posts</title>
			<link>http://www.gamingonlinux.com/articles_rss.php</link>
			<atom:link href=\"{$config['path']}/forum_rss.php\" rel=\"self\" type=\"application/rss+xml\" />
			<language>en-us</language>
			<description></description>
			<pubDate>$now</pubDate>
			<lastBuildDate>$now</lastBuildDate>";
	
	$db->sqlquery("SELECT `topic_id`, `topic_title`, `last_post_date` FROM `forum_topics` ORDER BY `last_post_date` DESC limit ?", array($limit));

	while ($line = $db->fetch())
	{
		// make date human readable
		$date = date("D, d M Y H:i:s O", $line['last_post_date']);
			
		$output .= "
			<item>
				<title>{$line['topic_title']}</title>
				<link>{$config['path']}/forum/topic/{$line['topic_id']}/</link>
				<pubDate>{$date}</pubDate>
				<guid>{$config['path']}/forum/topic/{$line['topic_id']}/</guid>
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

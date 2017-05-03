<?php
$file_dir = dirname(__FILE__);

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], $db_conf['table_prefix']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

include($file_dir . '/includes/class_user.php');
$user = new user($dbl, $core);

if ($core->config('forum_rss') == 1)
{
	header('Content-Type: text/xml; charset=utf-8', true);
	header("Cache-Control: max-age=3600");
	
	$now = date("D, d M Y H:i:s O");
	
	$xml = new XMLWriter();
	$xml->openMemory();
	$xml->startDocument( '1.0', 'UTF-8' );
	
	$xml->startElement( 'rss' );
	$xml->writeAttribute( 'version', '2.0' );
	$xml->writeAttribute( 'xmlns:atom', 'http://www.w3.org/2005/Atom' );

	$xml->startElement('channel');
	$xml->writeElement('title', $core->config('site_title') . ' Latest forum posts');
	$xml->writeElement('link', $core->config('website_url'));
	$xml->writeElement('description', 'The latest forum posts from ' . $core->config('site_title'));
	$xml->writeElement('pubDate', $now);
	$xml->writeElement('language', 'en-us');
	$xml->writeElement('lastBuildDate', $now);

	$xml->startElement('atom:link');
	$xml->writeAttribute('href', $core->config('website_url') . 'forum_rss.php');
	$xml->writeAttribute('rel', 'self');
	$xml->writeAttribute('type', 'application/rss+xml');
	$xml->endElement();

	$fetch_topics = $dbl->run("SELECT `topic_id`, `topic_title`, `last_post_date` FROM `forum_topics` WHERE `approved` = 1 ORDER BY `last_post_date` DESC LIMIT 15")->fetch_all();

	foreach ($fetch_topics as $line)
	{
		$xml->startElement('item');
		
		$xml->startElement('title');
		$xml->writeCData($line['topic_title']);
		$xml->endElement();
		
		// make date human readable
		$date = date("D, d M Y H:i:s O", $line['last_post_date']);
		$xml->writeElement('pubDate', $date);
		
		$link = $core->config('website_url') . "forum/topic/{$line['topic_id']}/";
		$xml->writeElement('link', $link);
		$xml->writeElement('guid', $link);
		
		// close this item
		$xml->endElement();
	}

	// close the channel and then the rss tags and then output the RSS feed
	$xml->endElement();
	$xml->endElement();

	echo $xml->outputMemory();
}

else
{
	echo 'RSS feed is not active!';
}
?>

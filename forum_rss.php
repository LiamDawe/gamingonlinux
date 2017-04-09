<?php
$file_dir = dirname(__FILE__);

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir. '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_template.php');
$templating = new template(core::config('template'));

if (core::config('articles_rss') == 1)
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
	$xml->writeElement('title', core::config('site_title') . ' Latest forum posts');
	$xml->writeElement('link', core::config('website_url'));
	$xml->writeElement('description', 'The latest news from ' . core::config('site_title'));
	$xml->writeElement('pubDate', $now);
	$xml->writeElement('language', 'en-us');
	$xml->writeElement('lastBuildDate', $now);

	$xml->startElement('atom:link');
	$xml->writeAttribute('href', core::config('website_url') . 'forum_rss.php');
	$xml->writeAttribute('rel', 'self');
	$xml->writeAttribute('type', 'application/rss+xml');
	$xml->endElement();

	$db->sqlquery("SELECT `topic_id`, `topic_title`, `last_post_date` FROM `forum_topics` WHERE `approved` = 1 ORDER BY `last_post_date` DESC LIMIT 15");

	while ($line = $db->fetch())
	{
		$xml->startElement('item');
		
		$xml->startElement('title');
		$xml->writeCData($line['topic_title']);
		$xml->endElement();
		
		// make date human readable
		$date = date("D, d M Y H:i:s O", $line['last_post_date']);
		$xml->writeElement('pubDate', $date);
		
		$link = core::config('website_url') . "forum/topic/{$line['topic_id']}/";
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

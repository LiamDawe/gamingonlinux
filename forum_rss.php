<?php
define("APP_ROOT", dirname(__FILE__));

require APP_ROOT . "/includes/bootstrap.php";

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
	$xml->writeElement('title', 'GamingOnLinux Latest forum posts');
	$xml->writeElement('link', $core->config('website_url'));
	$xml->writeElement('description', 'The latest forum posts from GamingOnLinux');
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

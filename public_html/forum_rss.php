<?php
session_start();
define('golapp', TRUE);

define("APP_ROOT", dirname(__FILE__));

require APP_ROOT . "/includes/bootstrap.php";

if ($core->config('forum_rss') == 1)
{
	// because firefox is fucking dumb and tries to download RSS instead of displaying, other browsers are fine
	if (isset($_SERVER['HTTP_USER_AGENT']))
	{
		$agent = $_SERVER['HTTP_USER_AGENT'];
		if (strlen(strstr($agent, 'Firefox')) > 0)
		{
			header('Content-Type: text/xml; charset=utf-8', true);
		}
		else
		{
			header('Content-Type: application/rss+xml; charset=utf-8', true);
		}
	}
	else
	{
		header('Content-Type: application/rss+xml; charset=utf-8', true);
	}
	header("Cache-Control: max-age=3600");
	
	$now = date("D, d M Y H:i:s O");

	// only show topics where the Guest group (4) can view that forum
	$forum_name = NULL;
	$self_add = NULL;
	if (!isset($_GET['fid']))
	{
		$fetch_topics = $dbl->run("SELECT t.`topic_id`, t.`topic_title`, t.`last_post_date` FROM `forum_topics` t INNER JOIN `forum_permissions` p ON t.forum_id = p.forum_id WHERE t.`approved` = 1 AND p.`group_id` = 4 AND p.`can_view` = 1 ORDER BY t.`last_post_date` DESC LIMIT 30")->fetch_all();
	}
	if (isset($_GET['fid']) && is_numeric($_GET['fid']))
	{
		$rss_password = NULL;
		if (isset($_GET['rss_pass']))
		{
			$rss_password = $_GET['rss_pass'];
		}
		$fetch_topics = $dbl->run("SELECT DISTINCT t.`topic_id`, t.`topic_title`, t.`last_post_date` FROM `forum_topics` t INNER JOIN `forum_permissions` p ON t.forum_id = p.forum_id INNER JOIN `forums` f ON f.forum_id = t.forum_id WHERE t.`approved` = 1 AND (p.`group_id` = 4 AND p.`can_view` = 1 OR f.rss_password = ?) AND t.forum_id = ? ORDER BY t.`last_post_date` DESC LIMIT 30", array($rss_password, $_GET['fid']))->fetch_all();

		$forum_name = ' | ' . $dbl->run("SELECT `name` FROM `forums` WHERE `forum_id` = ?", array($_GET['fid']))->fetchOne();
		$self_add = '?fid=' . $_GET['fid'];
	}
	
	$xml = new XMLWriter();
	$xml->openMemory();
	$xml->startDocument( '1.0', 'UTF-8' );
	
	$xml->startElement( 'rss' );
	$xml->writeAttribute( 'version', '2.0' );
	$xml->writeAttribute( 'xmlns:atom', 'http://www.w3.org/2005/Atom' );

	$xml->startElement('channel');
	$xml->writeElement('title', 'GamingOnLinux Latest forum posts' . $forum_name);
	$xml->writeElement('link', $core->config('website_url'));
	$xml->writeElement('description', 'The latest forum posts from GamingOnLinux' . $forum_name);
	$xml->writeElement('pubDate', $now);
	$xml->writeElement('language', 'en-us');
	$xml->writeElement('lastBuildDate', $now);

	$xml->startElement('atom:link');
	$xml->writeAttribute('href', $core->config('website_url') . 'forum_rss.php' . $self_add);
	$xml->writeAttribute('rel', 'self');
	$xml->writeAttribute('type', 'application/rss+xml');
	$xml->endElement();



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

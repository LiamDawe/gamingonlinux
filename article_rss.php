<?php
$file_dir = dirname(__FILE__);

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir. '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_charts.php');

include($file_dir . '/includes/class_article.php');
$article_class = new article_class();

if (core::config('articles_rss') == 1)
{
	include($file_dir . '/includes/bbcode.php');
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

	header('Content-Type: text/xml; charset=utf-8', true);
	header("Cache-Control: max-age=3600");

	$last_date = gmdate("D, d M Y H:i:s O", $last_time['date']);

	$xml = new XMLWriter();
	$xml->openMemory();
	$xml->startDocument( '1.0', 'UTF-8' );
	
	$xml->startElement( 'rss' );
	$xml->writeAttribute( 'version', '2.0' );
	$xml->writeAttribute( 'xmlns:atom', 'http://www.w3.org/2005/Atom' );

	$xml->startElement('channel');
	$xml->writeElement('title', 'GamingOnLinux.com Latest Articles');
	$xml->writeElement('link', 'https://www.gamingonlinux.com');
	$xml->writeElement('description', 'The latest news from GamingOnLinux.com');
	$xml->writeElement('pubDate', $last_date);
	$xml->writeElement('language', 'en-us');
	$xml->writeElement('lastBuildDate', $last_date);

	$xml->startElement('atom:link');
	$xml->writeAttribute('href', 'https://www.gamingonlinux.com/article_rss.php');
	$xml->writeAttribute('rel', 'self');
	$xml->writeAttribute('type', 'application/rss+xml');
	$xml->endElement();

	$db->sqlquery("SELECT a.*, u.username
	FROM `articles` a LEFT JOIN `users` u ON a.author_id = u.user_id $sql_join
	WHERE a.`active` = 1 $sql_addition
	ORDER BY a.`date` DESC
	LIMIT 15");

	$articles = $db->fetch_all_rows();

	foreach ($articles as $line)
	{
		$xml->startElement('item');
		
		$xml->startElement('title');
		$xml->writeCData($line['title']);
		$xml->endElement();

		$username = '';
		if (isset($line['username']))
		{
			$username = $line['username'];
		}
		else if (isset($line['guest_username']))
		{
			$username = $line['username'];
		}
		$xml->writeElement('author', "contact@gamingonlinux.com ($username)");
		
		$article_link = article_class::get_link($line['article_id'], $line['title']);
		$xml->writeElement('link', "https://www.gamingonlinux.com".$article_link);
		$xml->writeElement('guid', "https://www.gamingonlinux.com".$article_link);
		
		// sort out the categories (tags)
		$tag_list = [];
		$db->sqlquery("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ?", array($line['article_id']));
		while ($get_categories = $db->fetch())
		{
			$xml->startElement('category');
			$xml->writeCData($get_categories['category_name']);
			$xml->endElement();
			$tag_list[] = $get_categories['category_name'];
		}
		
		$tagline_bbcode = '';
		$bbcode_tagline_gallery = 0;
		if (!empty($line['tagline_image']))
		{
			$tagline_bbcode  = $line['tagline_image'];
		}
		if (!empty($article['gallery_tagline']))
		{
			$tagline_bbcode = $article['gallery_tagline_filename'];
			$bbcode_tagline_gallery = 1;
		}

		// for viewing the tagline, not the whole article
		if (isset($_GET['tagline']) && $_GET['tagline'] == 1)
		{
			$text = $line['tagline'];
		}
		else
		{
			$text = rss_stripping($line['text'], $tagline_bbcode, $bbcode_tagline_gallery);

			$text = bbcode($text, 1, 1);
		}
		
		$xml->startElement('description');
		$xml->writeCData('<p>Tags: ' . implode(', ', $tag_list) . '</p><p>' . $text . '</p>');
		$xml->endElement();
		
		$date = date("D, d M Y H:i:s O", $line['date']);
		$xml->writeElement('pubDate', $date);
		
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

<?php
// we dont need the whole bootstrap
require dirname(__FILE__) . "/includes/loader.php";
include dirname(__FILE__) . '/includes/config.php';
$dbl = new db_mysql();
$core = new core($dbl);
$bbcode = new bbcode($dbl, $core);

if ($core->config('articles_rss') == 1)
{
	$sql_join = '';
	$sql_addition = '';
	if (isset($_GET['section']) && $_GET['section'] == 'overviews')
	{
		$sql_join = ' LEFT JOIN `article_category_reference` c ON c.article_id = a.article_id';
		$sql_addition = ' AND c.`category_id` = 63';
	}

	$last_time = $dbl->run("SELECT a.`date`
	FROM `articles` a $sql_join
	WHERE a.`active` = 1 $sql_addition
	ORDER BY a.`date` DESC
	LIMIT 1")->fetchOne();

	header('Content-Type: application/rss+xml; charset=utf-8');
	header("Cache-Control: max-age=600");

	$last_date = gmdate("D, d M Y H:i:s O", $last_time);

	$xml = new XMLWriter();
	$xml->openMemory();
	$xml->startDocument('1.0', 'UTF-8' );
	
	$xml->startElement( 'rss' );
	$xml->writeAttribute( 'version', '2.0' );
	$xml->writeAttribute( 'xmlns:atom', 'http://www.w3.org/2005/Atom' );

	$xml->startElement('channel');
	$xml->writeElement('title', 'GamingOnLinux Latest Articles');
	$xml->writeElement('link', $core->config('website_url'));
	$xml->writeElement('description', 'The latest articles from GamingOnLinux');
	$xml->writeElement('pubDate', $last_date);
	$xml->writeElement('language', 'en-us');
	$xml->writeElement('lastBuildDate', $last_date);

	$xml->startElement('atom:link');
	$xml->writeAttribute('href', $core->config('website_url') . 'article_rss.php');
	$xml->writeAttribute('rel', 'self');
	$xml->writeAttribute('type', 'application/rss+xml');
	$xml->endElement();

	$articles = $dbl->run("SELECT a.*, t.`filename` as `gallery_tagline_filename`, u.username
	FROM `articles` a 
	LEFT JOIN
		`articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline` 
	LEFT JOIN 
		`users` u ON a.author_id = u.user_id $sql_join
	WHERE a.`active` = 1 $sql_addition
	ORDER BY a.`date` DESC
	LIMIT 15")->fetch_all();

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
		$xml->writeElement('author', $core->config('contact_email') . " ($username)");

		// smaller function as found in class_article.php get_link (dont need the entire article class for this!)
		$nice_title = core::nice_title($line['title']);
		
		if ($core->config('pretty_urls') == 1)
		{
			$link = 'articles/'.$nice_title.'.'.$line['article_id'];
		}
		else
		{
			$link = 'index.php?module=articles_full&aid='.$line['article_id'].'&title='.$nice_title;
		}
		$article_link = $core->config('website_url') . $link;

		$xml->writeElement('link', $article_link);
		$xml->writeElement('guid', $article_link);
		
		// sort out the categories (tags)
		$tag_list = [];
		$select_cats = $dbl->run("SELECT c.`category_name`, c.`category_id` FROM `articles_categorys` c INNER JOIN `article_category_reference` r ON c.category_id = r.category_id WHERE r.article_id = ?", [$line['article_id']]);
		foreach ($select_cats->fetch_all() as $get_categories)
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
		if (!empty($line['gallery_tagline']))
		{
			$tagline_bbcode = $line['gallery_tagline_filename'];
			$bbcode_tagline_gallery = 1;
		}

		// for viewing the tagline, not the whole article
		if (isset($_GET['tagline']) && $_GET['tagline'] == 1)
		{
			$text = $line['tagline'];
		}
		else
		{
			$text = $bbcode->rss_stripping($line['text'], $tagline_bbcode, $bbcode_tagline_gallery);
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
$dbl = NULL;
?>

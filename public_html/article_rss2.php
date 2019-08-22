<?php
define('golapp', TRUE);

// we dont need the whole bootstrap
require dirname(__FILE__) . "/includes/loader.php";
include dirname(__FILE__) . '/includes/config.php';
$dbl = new db_mysql();
$core = new core($dbl);
$bbcode = new bbcode($dbl, $core, $user);

if ($core->config('articles_rss') == 1)
{
	$sql_addition = '';
	$include_tags = [];
	// viewing specific tags only
	if (isset($_GET['tags']))
	{
		if (!is_array($_GET['tags']))
		{
			die("Tags list needs to be an array if tags!");
		}
		if (!core::is_number($_GET['tags']))
		{
			die("Tags list needs to be an array if tags!");
		}
		$include_tags = $_GET['tags'];
		$in  = str_repeat('?,', count($_GET['tags']) - 1) . '?';
		$sql_addition .= ' AND EXISTS (SELECT 1 FROM article_category_reference c
		WHERE a.article_id = c.article_id AND c.`category_id` IN ( ' . $in . ' ))';
	}

	$remove_tags = [];
	if (isset($_GET['notags']))
	{
		if (!is_array($_GET['notags']))
		{
			die("Tags list needs to be an array if tags!");
		}
		if (!core::is_number($_GET['notags']))
		{
			die("Tags list needs to be an array if tags!");
		}
		$remove_tags = $_GET['notags'];
		$in  = str_repeat('?,', count($_GET['notags']) - 1) . '?';
		$sql_addition .= ' AND NOT EXISTS (SELECT 1 FROM article_category_reference c
		WHERE a.article_id = c.article_id AND c.`category_id` IN ( ' . $in . ' ))';
	}

	// the total they can display at a time *not used yet*
	$max_allowed = 50;

	$last_time_sql = "SELECT a.article_id, a.`date`
	FROM `articles` a 
	WHERE a.`active` = 1 $sql_addition
	ORDER BY a.`date` DESC
	LIMIT 1";
	$last_time = $dbl->run($last_time_sql, array_merge($include_tags, $remove_tags))->fetchOne();

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
	if (!isset($_GET['mini']))
	{
		$xml->writeAttribute('href', $core->config('website_url') . 'article_rss.php');
	}
	else
	{
		$xml->writeAttribute('href', $core->config('website_url') . 'article_rss.php?mini');
	}
	
	$xml->writeAttribute('rel', 'self');
	$xml->writeAttribute('type', 'application/rss+xml');
	$xml->endElement();

	$articles_sql = "SELECT 
	a.`article_id`, 
	a.`title`,
	a.`text`,
	a.`date`, 
	t.`filename` as `gallery_tagline_filename`, u.username
	FROM `articles` a 
	LEFT JOIN
		`articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline` 
	LEFT JOIN 
		`users` u ON a.author_id = u.user_id
	WHERE a.`active` = 1 $sql_addition
	ORDER BY a.`date` DESC
	LIMIT 15";

	$articles = $dbl->run($articles_sql, array_merge($include_tags, $remove_tags))->fetch_all();

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
		$article_link = $core->config('website_url') . 'articles/'.$nice_title.'.'.$line['article_id'];

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
		// if they want the full text
		if (!isset($_GET['mini']))
		{
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
		}
		
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

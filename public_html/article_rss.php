<?php
define('golapp', TRUE);

// we dont need the whole bootstrap
require dirname(__FILE__) . "/includes/loader.php";
include dirname(__FILE__) . '/includes/config.php';
$dbl = new db_mysql();
$core = new core($dbl);
$bbcode = new bbcode($dbl, $core, NULL);

if ($core->config('articles_rss') == 1)
{
	$rss_title = 'GamingOnLinux Latest Articles';
	$tags = NULL;

	$sql_join = '';
	$sql_addition = '';
	if (isset($_GET['section']) && $_GET['section'] == 'overviews')
	{
		$sql_join = ' LEFT JOIN `article_category_reference` c ON c.article_id = a.article_id';
		$sql_addition = ' AND c.`category_id` = 63';
	}

	// viewing specific tags only
	if (isset($_GET['tags']))
	{
		$tags = $_GET['tags'];

		if (!is_array($tags))
		{
			die("Tags list needs to be an array of tags!");
		}
		if (!core::is_number($tags))
		{
			die("Tags have to be an ID number.");
		}

		if (count($tags) == 1)
		{
			$name = $dbl->run("SELECT `category_name` FROM `articles_categorys` WHERE `category_id` = ?", $tags)->fetchOne();
			$rss_title = 'GamingOnLinux Article RSS For: ' . $name;
		}
		$sql_join .= " INNER JOIN `article_category_reference` c ON a.article_id = c.article_id ";
		$in  = str_repeat('?,', count($tags) - 1) . '?';
		$sql_addition .= ' AND c.`category_id` IN ( ' . $in . ' ) ';
	}

	$last_time = $dbl->run("SELECT a.`date`
	FROM `articles` a $sql_join
	WHERE a.`active` = 1 $sql_addition
	ORDER BY a.`date` DESC
	LIMIT 1", $tags)->fetchOne();

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
	
	header("Cache-Control: max-age=600");

	$last_date = gmdate("D, d M Y H:i:s O", $last_time);

	$xml = new XMLWriter();
	$xml->openMemory();
	$xml->startDocument('1.0', 'UTF-8' );
	
	$xml->startElement( 'rss' );
	$xml->writeAttribute( 'version', '2.0' );
	$xml->writeAttribute( 'xmlns:atom', 'http://www.w3.org/2005/Atom' );
	$xml->writeAttribute( 'xmlns:dc', 'http://purl.org/dc/elements/1.1/' );

	$xml->startElement('channel');
	$xml->writeElement('title', $rss_title);
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

	$articles = $dbl->run("SELECT 
	a.title,
	a.article_id,
	a.tagline_image,
	a.gallery_tagline,
	a.tagline,
	a.date,
	a.text,
	t.`filename` as `gallery_tagline_filename`, 
	u.username
	FROM `articles` a 
	LEFT JOIN
		`articles_tagline_gallery` t ON t.`id` = a.`gallery_tagline` 
	LEFT JOIN 
		`users` u ON a.author_id = u.user_id $sql_join
	WHERE a.`active` = 1 $sql_addition
	ORDER BY a.`date` DESC
	LIMIT 50", $tags)->fetch_all();

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
		$xml->writeElement('dc:creator', $username);

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
				$random_id = core::random_id(15);
				$text = $bbcode->rss_stripping($line['text'] . "<div id=\"{$random_id}\">Article from <a href=\"https://www.gamingonlinux.com/\">GamingOnLinux.com</a> - do not reproduce this article without permission.</div>", $tagline_bbcode, $bbcode_tagline_gallery);
			}
			
			$xml->startElement('description');
			$xml->writeCData('<p>' . $text . '</p>');
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

<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');

require APP_ROOT . "/includes/bootstrap.php";

/* article list */
$sitemap_text = '<?xml version="1.0" encoding="UTF-8"?>
<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
			http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
			
$file = fopen(APP_ROOT . "/articles-sitemap.xml", 'w');

$articles = $dbl->run("SELECT `article_id`, `date` FROM `articles` WHERE `active` = 1 ORDER BY `article_id` DESC")->fetch_all();

foreach ($articles as $article)
{
	$parsed_date = date('c', $article['date']);

	$sitemap_text .= PHP_EOL . "<url>
	<loc>https://www.gamingonlinux.com/articles/".$article['article_id']."</loc>
	<lastmod>".$parsed_date."</lastmod>
	<priority>1.00</priority>
  </url>" . PHP_EOL;
}

$sitemap_text .= PHP_EOL . '</urlset>';

fwrite($file, $sitemap_text);
fclose($file);

/* main sections */

$sections_file = fopen(APP_ROOT . "/sections.xml", 'w');

$newest_article = date('c', $articles[0]['date']);

$sections_sitemap = '<?xml version="1.0" encoding="UTF-8"?>
<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
			http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
			
			<url>
			<loc>https://www.gamingonlinux.com/all-articles/</loc>
			<lastmod>'.$newest_article.'</lastmod>
			<priority>1.00</priority>
		  </url>

		  <url>
		  <loc>https://www.gamingonlinux.com/forum/</loc>
		  <priority>0.80</priority>
		</url>

		<url>
		<loc>https://www.gamingonlinux.com/about-us/</loc>
		<priority>0.80</priority>
	  </url>

	  <url>
	  <loc>https://www.gamingonlinux.com/contact-us/</loc>
	  <priority>0.80</priority>
	</url>

</urlset>';

fwrite($sections_file, $sections_sitemap);
fclose($sections_file);

echo 'Sitemap generation done.';
?>
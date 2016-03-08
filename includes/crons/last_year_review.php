<?php
error_reporting(E_ALL);

include('/home/gamingonlinux/public_html/includes/config.php');

include('/home/gamingonlinux/public_html/includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('/home/gamingonlinux/public_html/includes/class_core.php');
$core = new core();

include('/home/gamingonlinux/public_html/includes/bbcode.php');

// setup the templating, if not logged in default theme, if logged in use selected theme
include('/home/gamingonlinux/public_html/includes/class_template.php');

$templating = new template();

$db->sqlquery("SELECT a.*
FROM (SELECT a.*
      FROM articles a
      WHERE date >= UNIX_TIMESTAMP(DATE(NOW() - INTERVAL 362 DAY))
      ORDER BY views DESC
      LIMIT 30
     ) a
ORDER BY views ASC");

$year = date('Y');

$title = "The most popular Linux gaming articles from $year";

$tagline = "Here is a look back at the most popular articles on GamingOnLinux for $year. It was a complete knockout year with surprise after surprise, but what did people read the most?";

$text = "Here is a look back at the most popular articles on GamingOnLinux for $year, it was a seriously crazy year for us here, and for everyone in Linux gaming, so let's see what happened! We had a few downers, but most of it was great stuff that I am still proud to be a part of.\r\n";

$counter = 0;

while ($articles = $db->fetch())
{
	$counter++;
	$nice_link =  $core->nice_title($articles['title']) . '.' . $articles['article_id'];
	$views = number_format($articles['views'], 0, '.', ',');

	$tagline_image = '';
	if (!empty($articles['tagline_image']))
	{
		$tagline_image = "http://www.gamingonlinux.com/uploads/articles/tagline_images/thumbnails/{$articles['tagline_image']}";
	}
	else if (!empty($articles['article_top_image_filename']))
	{
		$tagline_image = "http://www.gamingonlinux.com/uploads/articles/topimages/{$articles['article_top_image_filename']}";
	}
	$text .= "\r\n[url=http://www.gamingonlinux.com/articles/$nice_link][img]{$tagline_image}[/img][/url]";
	$text .= "\r\n[url=http://www.gamingonlinux.com/articles/$nice_link]{$articles['title']}[/url] - Views: {$views}";
	$text .= "\r\n[i]{$articles['tagline']}[/i]\r\n\r\n";
}

$text .= "\r\nAlso remember to check out our [url=/sales/]Game Sales[/url] page where we syndicate game sales from Humble Store, Itch.io, IndieGameStand, GOG and many more stores!\r\n";

$text .= "\r\nWhat was your favourite Linux Gaming news for the past year? So much happened, and so much still to come!";

// DEBUG
//echo bbcode($text);
//echo bbcode($page2);

$db->sqlquery("INSERT INTO `articles` SET `author_id` = 1, `date` = ?, `title` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = 0, `tagline_image` = '2015.png', `active` = 0, `admin_review` = 1", array($core->date, $title, $tagline, $text));

$article_id = $db->grab_id();

$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 63", array($article_id));

$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 0, `action` = ?, `created` = ?, `article_id` = ?", array("TheBoss sent a new article to the admin review queue.", $core->date, $article_id));
?>

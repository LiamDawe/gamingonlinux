<?php
include('/home/gamingonlinux/public_html/includes/config.php');

include('/home/gamingonlinux/public_html/includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('/home/gamingonlinux/public_html/includes/class_core.php');
$core = new core();

include('/home/gamingonlinux/public_html/includes/bbcode.php');

// setup the templating, if not logged in default theme, if logged in use selected theme
include('/home/gamingonlinux/public_html/includes/class_template.php');

$templating = new template();

$db->sqlquery("SELECT count(DISTINCT a.article_id) as `counter` FROM `articles` a LEFT JOIN `article_category_reference` c ON a.article_id = c.article_id WHERE MONTH(FROM_UNIXTIME(a.`date`)) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(FROM_UNIXTIME(`date`)) = YEAR(CURRENT_DATE) AND c.category_id NOT IN (63) AND a.active = 1");

$counter = $db->fetch();

$prevmonth = date('F Y', strtotime('-1 months'));

$title = "The most popular Linux & SteamOS gaming articles for $prevmonth, {$counter['counter']} in total";

$tagline = "Here is a look back at the 15 most popular articles on GamingOnLinux for $prevmonth, an easy way to for you to keep up to date on what has happened in the past month for Linux & SteamOS Gaming!";

$text = "Here is a look back at the 15 most popular articles on GamingOnLinux for $prevmonth, an easy way to for you to keep up to date on what has happened in the past month for Linux & SteamOS Gaming! If you wish to keep track of these overview posts you can with our <a href=\"http://www.gamingonlinux.com/article_rss.php?section=overviews\">Overview RSS</a>.<br /><br />We published a total of <strong>{$counter['counter']} articles last month</strong>!<br />";

// sub query = grab the highest ones, then the outer query sorts them in ascending order, so we get the highest viewed articles, and then sorted from lowest to highest
$db->sqlquery("SELECT a.*
FROM (SELECT a.article_id, a.date, a.tagline_image, a.article_top_image_filename, a.title, a.tagline, a.views, c.category_id
	FROM
		articles a
	LEFT JOIN
		`article_category_reference` c ON a.article_id = c.article_id
	WHERE
		MONTH(FROM_UNIXTIME(a.`date`)) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(FROM_UNIXTIME(`date`)) = YEAR(CURRENT_DATE) AND c.category_id NOT IN (63, 92) AND a.active = 1 group by `a`.`article_id`
	ORDER BY
		a.views DESC
	LIMIT 15
     ) a
ORDER BY views DESC");

while ($articles = $db->fetch())
{
	$nice_link =  $core->nice_title($articles['title']) . '.' . $articles['article_id'];
	$views = number_format($articles['views'], 0, '.', ',');

	$date = $core->format_date($articles['date']);

	$tagline_image = '';
	if (!empty($articles['tagline_image']))
	{
		$tagline_image = "http://www.gamingonlinux.com/uploads/articles/tagline_images/thumbnails/{$articles['tagline_image']}";
	}
	else if (!empty($articles['article_top_image_filename']))
	{
		$tagline_image = "http://www.gamingonlinux.com/uploads/articles/topimages/{$articles['article_top_image_filename']}";
	}
	$text .= "<br /><a href=\"http://www.gamingonlinux.com/articles/$nice_link\"><img src=\"{$tagline_image}\" /></a>";
	$text .= "<br /><a href=\"http://www.gamingonlinux.com/articles/$nice_link\">{$articles['title']}</a> - $date - Views: {$views}";
	$text .= "<br /><em>{$articles['tagline']}</em><br /><br />";
}


$text .= "<br />All of this is possible thanks to <a href=\"http://patreon.com/liamdawe\">my Patreon campaign</a>, and our Supporters!<br />";

$text .= "<br />What was your favourite Linux Gaming news from $prevmonth?";

// DEBUG
//echo $text;

$slug = $core->nice_title($title);

$db->sqlquery("INSERT INTO `articles` SET `author_id` = 1844, `date` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = 0, `tagline_image` = 'monthlyoverview.png'", array(core::$date, $title, $slug, $tagline, $text));

$article_id = $db->grab_id();

$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 63", array($article_id));

include(core::config('path') . 'includes/telegram_poster.php');
telegram($title . ' ' . core::config('website_url') . "articles/" . $slug . '.' . $article_id);
?>

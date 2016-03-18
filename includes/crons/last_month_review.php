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

$title = "The most popular Linux gaming articles for $prevmonth, {$counter['counter']} in total";

$tagline = "Here is a look back at the 15 most popular articles on GamingOnLinux for $prevmonth, an easy way to for you to keep up to date on what has happened in the past month for Linux Gaming!";

$text = "Here is a look back at the 15 most popular articles on GamingOnLinux for $prevmonth, an easy way to for you to keep up to date on what has happened in the past month for Linux Gaming! Sorted from lowest to highest to make sure you don't miss the smaller news stories. Also if you wish to keep track of these overview posts you can with our <a href=\"http://www.gamingonlinux.com/article_rss.php?section=overviews\">Overview RSS</a>.<br /><br />We published a total of <strong>{$counter['counter']} articles last month</strong>, wow!<br />";

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
ORDER BY views ASC");

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

// Latest sales box on the main page
$sales = '<ul>';
$sale_counter = 0;
$db->sqlquery("SELECT s.`id`,s.`info`, s.`website`, s.`provider_id`, p.`name` FROM `game_sales` s LEFT JOIN `game_sales_provider` p ON s.provider_id = p.provider_id WHERE s.`accepted` = 1 ORDER BY s.`id` DESC LIMIT 10");
while ($home_list = $db->fetch())
{
	$sale_counter++;
	$sale_name = $home_list['info'];

	// check to see if we need to put in the category name or not
	$provider = '';
	if ($home_list['provider_id'] != 0)
	{
		$provider = "<strong>{$home_list['name']}</strong>";
	}

	$sales .= "<li><a href=\"/sales/{$home_list['id']}\">{$provider} - {$sale_name}</a></li>";
}
$sales .= "</ul><br /><br />";

$text .= "<br />Also remember to check out our [url=/sales/]Game Sales[/url] page where we syndicate game sales from GOG, Humble Store, Itch.io, IndieGameStand and many more stores!<br /><br />See the latest few sales here:<br />$sales";

$text .= "No need to tip us, as we do it because we love what we do!<br />";

$text .= "<br />What was your favourite Linux Gaming news for the past month?";

// DEBUG
//echo $text;

$db->sqlquery("INSERT INTO `articles` SET `author_id` = 1844, `date` = ?, `title` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = 0, `tagline_image` = 'monthlyoverview.png'", array(core::$date, $title, $tagline, $text));

$article_id = $db->grab_id();

$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 63", array($article_id));
?>

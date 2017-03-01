<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

$prev_month = date('n', strtotime('-1 months'));
$year_selector = date('Y');
if ($prev_month == 12)
{
	$time = strtotime("-1 year", time());
	$year_selector = date("Y", $time);
}
$first_minute = mktime(0, 0, 0, $prev_month, 1, $year_selector);
$last_minute = mktime(23, 59, 59, $prev_month, date("t"), $year_selector);

$db->sqlquery("SELECT count(DISTINCT a.article_id) as `counter` FROM `articles` a LEFT JOIN `article_category_reference` c ON a.article_id = c.article_id WHERE a.`date` >= $first_minute AND a.`date` <= $last_minute AND c.category_id NOT IN (63) AND a.active = 1");

$counter = $db->fetch();

$prevdate = date('F Y', strtotime('-1 months'));

$title = "The most popular Linux & SteamOS gaming articles for $prevdate, {$counter['counter']} in total";

$tagline = "Here is a look back at the 10 most popular articles on GamingOnLinux for $prevdate, an easy way to for you to keep up to date on what has happened in the past month for Linux & SteamOS Gaming!";

$text = "Here is a look back at the 10 most popular articles on GamingOnLinux for $prevdate, an easy way to for you to keep up to date on what has happened in the past month for Linux & SteamOS Gaming! If you wish to keep track of these overview posts you can with our <a href=\"http://www.gamingonlinux.com/article_rss.php?section=overviews\">Overview RSS</a>.<br /><br />We published a total of <strong>{$counter['counter']} articles last month</strong>! You can see who <a href=\"https://www.gamingonlinux.com/index.php?module=website_stats\">contributed articles on this page.</a><br />
[b]Here's what was most popular with our readers:[/b]<br />";

// sub query = grab the highest ones, then the outer query sorts them in ascending order, so we get the highest viewed articles, and then sorted from lowest to highest
$db->sqlquery("SELECT a.`article_id` FROM articles a LEFT JOIN `article_category_reference` c ON a.`article_id` = c.`article_id` WHERE a.`date` >= $first_minute AND a.`date` <= $last_minute AND c.`category_id` NOT IN (63, 92) AND a.`active` = 1 group by `a`.`article_id` ORDER BY a.`views` DESC LIMIT 10");

while ($articles = $db->fetch())
{
	$text .= '[article]' . $articles['article_id'] . '[/article]';
}

$text .= "<br />All of this is possible thanks to <a href=\"http://patreon.com/liamdawe\">my Patreon campaign</a>, and our Supporters!<br />";

$text .= "<br />What was your favourite Linux Gaming news from $prevdate?";

$slug = core::nice_title($title);

$db->sqlquery("INSERT INTO `articles` SET `author_id` = 1844, `date` = ?, `title` = ?, `slug` = ?, `tagline` = ?, `text` = ?, `show_in_menu` = 0, `tagline_image` = 'monthlyoverview.png', `active` = 0, `admin_review` = 1", array(core::$date, $title, $slug, $tagline, $text));

$article_id = $db->grab_id();

$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 63", array($article_id));

// update admin notifications
$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = 1844, `completed` = 0, `type` = ?, `created_date` = ?, `data` = ?", array('article_admin_queue', core::$date, $article_id));
?>

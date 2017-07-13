<?php
$templating->set_previous('title', 'Website stats', 1);
$templating->set_previous('meta_description', 'Statistics from the GamingOnLinux website', 1);

$templating->load('website_stats');

$templating->block('top');
$templating->set('site_title', $core->config('site_title'));

$templating->block('users');
$templating->set('site_title', $core->config('site_title'));
$templating->set('total_users', number_format($core->config('total_users')));

$count_monthly_users = $dbl->run("SELECT COUNT( DISTINCT user_id ) AS `counter` FROM `users` WHERE MONTH(FROM_UNIXTIME(`register_date`)) >= MONTH(NOW()) AND YEAR(FROM_UNIXTIME(`register_date`)) = YEAR(CURRENT_DATE)")->fetchOne();

$templating->set('users_month', number_format($count_monthly_users));

$total_articles = $dbl->run("SELECT COUNT(article_id) as `total` FROM `articles` WHERE `active` = 1")->fetchOne();

$templating->set('total_articles', number_format($total_articles));

// count comments posted in the last 24 hours
$last_24_hours = time() - 86400;

$comments_24 = $dbl->run("SELECT COUNT(comment_id) as total FROM `articles_comments` WHERE `time_posted` > ?", array($last_24_hours))->fetchOne();

$templating->set('total_comments', number_format($comments_24));

// list who wrote articles for GOL since the start of last month
$prev_month = date('n', strtotime('-1 months'));
$year_selector = date('Y');
if ($prev_month == 12)
{
	$time = strtotime("-1 year", time());
	$year_selector = date("Y", $time);
}
$last_month_start = mktime(0, 0, 0, $prev_month, 1, $year_selector);
$now = time();

$article_list = $dbl->run("SELECT
    COUNT(DISTINCT a.article_id) AS `counter`,
    u.`username`,
    u.`user_id`,
    MAX(a.date) AS `last_date`
FROM
    `articles` a
LEFT JOIN
    `users` u
ON
    u.`user_id` = a.`author_id`
LEFT JOIN
    `article_category_reference` c
ON
    a.`article_id` = c.`article_id`
WHERE
    a.`date` >= $last_month_start AND a.`date` <= $now AND a.`active` = 1 AND c.`category_id` NOT IN(63) AND a.author_id != 1844
GROUP BY
    u.`username`,
    u.`user_id`
ORDER BY
    `counter`
DESC,
    `last_date`
DESC")->fetch_all();

$templating->block('articles', 'website_stats');
$templating->set('site_title', $core->config('site_title'));
$author_list = '';

$last_month = date("j M Y", strtotime("first day of previous month"));
$now_date = date("j M Y");

$templating->set('last_month', $last_month);
$templating->set('now_date', $now_date);

if ($core->config('pretty_urls') == 1)
{
	$profile_url = '/profiles/';
}
else
{
	$profile_url = '/index.php?module=profile&user_id=';
}

$counter = 0;

foreach ($article_list as $fetch_authors)
{
	if ($counter == 0)
	{
		$article_count = '<strong>' . $fetch_authors['counter'] . '</strong>';
	}
	else
	{
		$article_count = $fetch_authors['counter'];
	}

	if ($fetch_authors['user_id'] == 0)
	{
		$username = 'Guest';
	}
	else
	{
		$username = '<a href="'. $profile_url . $fetch_authors['user_id'] . '">' . $fetch_authors['username'] . '</a>';
	}

	$author_list .= '<li>' . $username . ' (' . $article_count . ') <em>Last article: ' . $core->format_date($fetch_authors['last_date']) . '</em></li>';
	$counter++;
}
$templating->set('author_list', $author_list);

// top articles this week
$templating->block('hot_articles');

$timestamp = strtotime("-3 months");

$hot_articles = '';
$query_hot = $dbl->run("SELECT `article_id`, `title`, `views` FROM `articles` WHERE `active` = 1 AND `date` > ? ORDER BY `views` DESC LIMIT 5", array($timestamp))->fetch_all();
foreach ($query_hot as $get_hot)
{
	$hot_articles .= '<li><a href="'.$article_class->get_link($get_hot['article_id'], $get_hot['title']).'">'.$get_hot['title'].'</a> ('.number_format($get_hot['views']).')</li>';
}

$templating->set('hot_articles', $hot_articles);

// top articles of all time
$templating->block('top_articles');

$article_list = '';
// top 10 articles of all time by views
$query_top = $dbl->run("SELECT `article_id`, `title`, `views` FROM `articles` WHERE `active` = 1 ORDER BY `views` DESC LIMIT 5")->fetch_all();
foreach ($query_top as $top_articles)
{
	$article_list .= '<li><a href="'.$article_class->get_link($top_articles['article_id'], $top_articles['title']).'">'.$top_articles['title'].'</a> ('.number_format($top_articles['views']).')</li>';
}
$templating->set('article_list', $article_list);
?>

<?php
$templating->set_previous('title', 'Website stats', 1);
$templating->set_previous('meta_description', 'Statistics from the GamingOnLinux website', 1);

$templating->merge('website_stats');

$templating->block('top');

$templating->block('users');
$templating->set('total_users', number_format(core::config('total_users')));

$count_new_users = $db->sqlquery("SELECT COUNT( DISTINCT user_id ) AS `counter` FROM `users` WHERE MONTH(FROM_UNIXTIME(`register_date`)) >= MONTH(NOW()) AND YEAR(FROM_UNIXTIME(`register_date`)) = YEAR(CURRENT_DATE)");
$count_monthly_users = $count_new_users->fetch();

$templating->set('users_month', number_format($count_monthly_users['counter']));

$db->sqlquery("SELECT COUNT(article_id) as `total` FROM `articles` WHERE `active` = 1");
$total_articles = $db->fetch();

$templating->set('total_articles', number_format($total_articles['total']));

// count comments posted in the last 24 hours
$last_24_hours = time() - 86400;

$db->sqlquery("SELECT COUNT(comment_id) as total FROM `articles_comments` WHERE `time_posted` > ?", array($last_24_hours));
$comments_24 = $db->fetch();

$templating->set('total_comments', number_format($comments_24['total']));

// list who wrote articles for GOL since the start of last month
$article_list = $db->sqlquery("SELECT COUNT( DISTINCT a.article_id ) AS `counter`, u.username, u.user_id, (SELECT `date` FROM `articles` WHERE author_id = a.author_id ORDER BY `article_id` DESC LIMIT 1) as last_date FROM `articles` a LEFT JOIN `users` u ON u.user_id = a.author_id LEFT JOIN `article_category_reference` c ON a.`article_id` = c.`article_id`
WHERE MONTH(FROM_UNIXTIME(a.`date`)) >= MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(FROM_UNIXTIME(a.`date`)) = YEAR(CURRENT_DATE) AND a.`active` = 1 AND c.`category_id` NOT IN (63) GROUP BY u.`username` ORDER BY `counter` DESC, a.date DESC");

$templating->block('articles', 'website_stats');
$author_list = '';

$last_month = date("j M Y", strtotime("first day of previous month"));
$now_date = date("j M Y");

$templating->set('last_month', $last_month);
$templating->set('now_date', $now_date);

if (core::config('pretty_urls') == 1)
{
	$profile_url = '/profiles/';
}
else
{
	$profile_url = '/index.php?module=profile&user_id=';
}

$counter = 0;

while ($fetch_authors = $article_list->fetch())
{
	if ($counter == 0)
	{
		$article_count = '<strong>' . $fetch_authors['counter'] . '</strong>';
	}
	else
	{
		$article_count = $fetch_authors['counter'];
	}
	$author_list .= '<li><a href="'. $profile_url . $fetch_authors['user_id'] . '">' . $fetch_authors['username'] . '</a>: ' . $article_count . '<br />
	Last article: ' . $core->format_date($fetch_authors['last_date']) . '</li>';
	$counter++;
}
$templating->set('author_list', $author_list);

?>

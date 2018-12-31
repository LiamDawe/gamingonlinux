<?php
$templating->set_previous('title', 'Website stats', 1);
$templating->set_previous('meta_description', 'Statistics from the GamingOnLinux website', 1);

if ($filecache->check_cache('website_stats', 3600))
{
	$templating->get_cache('website_stats');
}
else // otherwise generate the page and make a cache from it
{
	echo $templating->output();

	$filecache->init();

	$templating->load('website_stats');

	$templating->block('top');

	$templating->block('users');
	$templating->set('total_users', number_format($core->config('total_users')));

	$count_monthly_users = $dbl->run("SELECT COUNT( DISTINCT user_id ) AS `counter` FROM `users` WHERE MONTH(FROM_UNIXTIME(`register_date`)) >= MONTH(NOW()) AND YEAR(FROM_UNIXTIME(`register_date`)) = YEAR(CURRENT_DATE)")->fetchOne();

	$templating->set('users_month', number_format($count_monthly_users));

	// active users
	$monthly_active_users = $dbl->run("SELECT COUNT(`user_id`) FROM `users` WHERE `last_login` >= unix_timestamp(CURDATE() - INTERVAL 30 DAY) ORDER BY `users`.`last_login` ASC")->fetchOne();
	$templating->set('monthly_active', number_format($monthly_active_users));

	$total_articles = $dbl->run("SELECT COUNT(article_id) as `total` FROM `articles` WHERE `active` = 1")->fetchOne();

	$templating->set('total_articles', number_format($total_articles));

	// count comments posted in the last 24 hours
	$last_24_hours = time() - 86400;

	$comments_24 = $dbl->run("SELECT COUNT(comment_id) as total FROM `articles_comments` WHERE `time_posted` > ?", array($last_24_hours))->fetchOne();

	$templating->set('total_comments', number_format($comments_24));

	// list who wrote articles for GOL since the start of last month
	$prev_month = date("n", strtotime("first day of previous month"));
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
	$author_list = '';

	$last_month = date("j M Y", strtotime("first day of previous month"));
	$now_date = date("j M Y");

	$templating->set('last_month', $last_month);
	$templating->set('now_date', $now_date);

	$profile_url = '/profiles/';

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

		$author_list .= '<li>' . $username . ' (' . $article_count . ') <em>Last article: ' . $core->human_date($fetch_authors['last_date']) . '</em></li>';
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
	$query_top = $dbl->run("SELECT `article_id`, `title`, `views` FROM `articles` WHERE `active` = 1 ORDER BY `views` DESC LIMIT 5")->fetch_all();
	foreach ($query_top as $top_articles)
	{
		$article_list .= '<li><a href="'.$article_class->get_link($top_articles['article_id'], $top_articles['title']).'">'.$top_articles['title'].'</a> ('.number_format($top_articles['views']).')</li>';
	}
	$templating->set('article_list', $article_list);

	// most "liked" articles
	$templating->block('most_likes');

	$liked_list = '';
	$query_liked = $dbl->run("SELECT COUNT(l.like_id) as `total_likes`, a.`article_id`, a.`title` FROM `article_likes` l INNER JOIN `articles` a ON a.`article_id` = l.`article_id` GROUP BY l.`article_id` ORDER BY `total_likes` DESC LIMIT 5")->fetch_all();
	foreach ($query_liked as $top_liked)
	{
		$liked_list .= '<li><a href="'.$article_class->get_link($top_liked['article_id'], $top_liked['title']).'">'.$top_liked['title'].'</a> ('.number_format($top_liked['total_likes']).')</li>';
	}
	$templating->set('article_list', $liked_list);

	// a yearly total of articles, not including the bot account
	$templating->block('yearly_articles');

	$charts = new charts($dbl);

	$year_list = '';
	$preview_data = [];
	$yearly = $dbl->run("SELECT YEAR(FROM_UNIXTIME(`date`)) as `name`, COUNT(*) as `data` FROM `articles` WHERE `active` = 1 AND `author_id` != 1844 GROUP BY YEAR(FROM_UNIXTIME(`date`)) ORDER BY `name` DESC LIMIT 7")->fetch_all();
	$yearly_chart = $charts->render(['filetype' => 'png'], ['name' => 'Articles per year', 'grouped' => 0, 'data' => $yearly, 'h_label' => 'Total Posted']);
	$templating->set('year_chart', $yearly_chart);

	$templating->block('monthly_users');

	$charts = new charts($dbl);

	$monthly_registrations = $dbl->run("SELECT date_format(FROM_UNIXTIME(register_date), '%Y-%m') as `name`, Count(*) as `data` FROM users WHERE year(FROM_UNIXTIME(register_date)) = $year_selector GROUP BY date_format(FROM_UNIXTIME(register_date), '%Y-%m')")->fetch_all();
	$monthly_reg_chart = $charts->render(['filetype' => 'png'], ['name' => 'User registrations per month', 'grouped' => 0, 'data' => $monthly_registrations, 'h_label' => 'Total Users']);
	$templating->set('monthly_registrations', $monthly_reg_chart);

	echo $templating->output();

	$filecache->write('website_stats');
}
?>

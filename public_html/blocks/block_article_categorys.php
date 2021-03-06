<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->load('blocks/block_article_categorys');
$templating->block('menu');

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	if (!$user->check_group([1,2,5]))
	{
		$submit_link = '<li><a href="/submit-article/">Submit Article</a></li>';
	}

	else if ($user->check_group([1,2,5]))
	{
		$submit_link = '<li><a href="' . $core->config('website_url') . 'admin.php?module=add_article">Submit Article</a></li>';
	}
}
else
{
	$submit_link = '';
}

$templating->set('submit_article_link', $submit_link);

/*
// top articles this week
*/
$top_article_query = "SELECT `article_id`, `title`, `date`, `slug` FROM `articles` WHERE `date` > UNIX_TIMESTAMP(NOW() - INTERVAL 7 DAY) AND `views` > ? AND `show_in_menu` = 0 ORDER BY `views` DESC LIMIT 5";

// setup a cache
$querykey = "KEY" . md5($top_article_query . serialize($core->config('hot-article-viewcount')));
$fetch_top = unserialize($core->get_dbcache($querykey)); // check cache

if ($fetch_top === false || $fetch_top === null) // there's no cache
{
	$fetch_top = $dbl->run($top_article_query, array($core->config('hot-article-viewcount')))->fetch_all();
	$core->set_dbcache($querykey, serialize($fetch_top), 21600); // cache for six hours
}

$hot_articles = '';
foreach ($fetch_top as $top_articles)
{
	$hot_articles .= '<li class="list-group-item"><a href="'.$article_class->article_link(array('date' => $top_articles['date'], 'slug' => $top_articles['slug'])).'">'.$top_articles['title'].'</a></li>';
}

$templating->set('top_articles', $hot_articles);

$email_link = "/email-us/";
$templating->set('email_us_link', $email_link);

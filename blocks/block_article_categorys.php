<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->load('blocks/block_article_categorys');
$templating->block('menu');

if (!$user->check_group([1,2,5]))
{
	$submit_link = '/submit-article/';
}

else if ($user->check_group([1,2,5]))
{
	$submit_link = $core->config('website_url') . 'admin.php?module=add_article';
}

$templating->set('submit_article_link', $submit_link);

// Get the categorys, for the jump list, also used in "block_article_categorys.php"
$articles_categorys = '';
$fetch_cats = $dbl->run("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC")->fetch_all();
foreach ($fetch_cats as $categorys)
{
	$articles_categorys .= '<option value="'.$article_class->tag_link($categorys['category_name']).'">'.$categorys['category_name'].'</option>';
}
$templating->set('category_links', $articles_categorys);

/*
// top articles this week
*/
$top_article_query = "SELECT `article_id`, `title` FROM `articles` WHERE `date` > UNIX_TIMESTAMP(NOW() - INTERVAL 7 DAY) AND `views` > ? AND `show_in_menu` = 0 ORDER BY `views` DESC LIMIT 4";

// setup a cache
$querykey = "KEY" . md5($top_article_query . serialize($core->config('hot-article-viewcount')));
$fetch_top = unserialize(core::$redis->get($querykey)); // check cache

if ($fetch_top === false || $fetch_top === null) // there's no cache
{
	$fetch_top = $dbl->run($top_article_query, array($core->config('hot-article-viewcount')))->fetch_all();
	core::$redis->set($querykey, serialize($fetch_top), 21600); // cache for six hours
}

$hot_articles = '';
foreach ($fetch_top as $top_articles)
{
	$hot_articles .= '<li class="list-group-item"><a href="'.$article_class->get_link($top_articles['article_id'], $top_articles['title']).'">'.$top_articles['title'].'</a></li>';
}

$templating->set('top_articles', $hot_articles);

$email_link = "/email-us/";
$templating->set('email_us_link', $email_link);

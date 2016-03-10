<?php
// Article categorys block
$templating->merge('blocks/block_article_categorys');
$templating->block('menu');

if (!isset($_SESSION['user_group']) || ($_SESSION['user_group'] != 1 && $_SESSION['user_group'] != 2 && $_SESSION['user_group'] != 5))
{
	if ($config['pretty_urls'] == 1)
	{
		$submit_link = '/submit-article/';
	}
	else {
		$submit_link = core::config('website_url') . 'index.php?module=articles&amp;view=Submit';
	}
}

else if (isset($_SESSION['user_group']) && $_SESSION['user_group'] == 1 || $_SESSION['user_group'] == 2 || $_SESSION['user_group'] == 5)
{
	$submit_link = core::config('website_url') . 'admin.php?module=articles&amp;view=add';
}

$templating->set('submit_article_link', $submit_link);

// set them
$templating->set('category_links', $articles_categorys);

/*
// top articles this week
*/
$timestamp = strtotime("-7 days");

$hot_articles = '';
$db->sqlquery("SELECT `article_id`, `title`, `views`,`date` FROM `articles` WHERE `date` > ? AND `views` > 1000 AND `show_in_menu` = 0 ORDER BY `views` DESC LIMIT 4", array($timestamp));
while ($top_articles = $db->fetch())
{
	$top_title = ucwords($top_articles['title']);

	if ($config['pretty_urls'] == 1)
	{
		$hot_articles .= "<li class=\"list-group-item\"><a href=\"/articles/{$core->nice_title($top_articles['title'])}.{$top_articles['article_id']}\">{$top_title}</a></li>";
	}
	else {
		$hot_articles .= '<li class="list-group-item"><a href="' . core::config('website_url') . 'index.php?module=articles_full&amp;aid=' . $top_articles['article_id'] . '">' . $top_title . '</a></li>';
	}
}

$templating->set('top_articles', $hot_articles);

<?php
$templating->load('blocks/block_article_categorys');
$templating->block('menu');

if (!$user->check_group([1,2,5]))
{
	if ($core->config('pretty_urls') == 1)
	{
		$submit_link = '/submit-article/';
	}
	else 
	{
		$submit_link = $core->config('website_url') . 'index.php?module=articles&amp;view=Submit';
	}
}

else if ($user->check_group([1,2,5]))
{
	$submit_link = $core->config('website_url') . 'admin.php?module=add_article';
}

$templating->set('submit_article_link', $submit_link);

// Get the categorys, for the jump list, also used in "block_article_categorys.php"
$articles_categorys = '';
$db->sqlquery("SELECT `category_id`, `category_name` FROM `articles_categorys` ORDER BY `category_name` ASC");
while ($categorys = $db->fetch())
{
	$articles_categorys .= '<option value="'.$article_class->tag_link($categorys['category_name']).'">'.$categorys['category_name'].'</option>';
}
$templating->set('category_links', $articles_categorys);

/*
// top articles this week
*/
$timestamp = strtotime("-7 days");

$hot_articles = '';
$db->sqlquery("SELECT `article_id`, `title` FROM `articles` WHERE `date` > ? AND `views` > ".$core->config('hot-article-viewcount')." AND `show_in_menu` = 0 ORDER BY `views` DESC LIMIT 4", array($timestamp));
while ($top_articles = $db->fetch())
{
	$hot_articles .= '<li class="list-group-item"><a href="'.$article_class->get_link($top_articles['article_id'], $top_articles['title']).'">'.$top_articles['title'].'</a></li>';
}

$templating->set('top_articles', $hot_articles);

if ($core->config('pretty_urls') == 1)
{
	$email_link = "/email-us/";
}
else
{
	$email_link = $core->config('website_url') . 'index.php?module=email_us';
}

$templating->set('email_us_link', $email_link);

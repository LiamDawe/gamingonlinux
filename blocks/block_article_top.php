<?php
// Article categorys block
$templating->merge('blocks/block_article_top');
$templating->block('menu');

// top articles this week
$hot_articles = '';
$db->sqlquery("SELECT `article_id`, `title`, `views`,`date` FROM `articles` WHERE `date` > UNIX_TIMESTAMP(CURDATE() - INTERVAL 7 day) AND `views` > 1500 ORDER BY `views` DESC LIMIT 3");
while ($articles = $db->fetch())
{
	$hot_articles .= "<li><a href=\"/articles/{$core->nice_title($articles['title'])}.{$articles['article_id']}\"><i class=\"icon-exclamation-sign\"></i>{$articles['title']}</a></li>";
}

$templating->set('top_articles', $hot_articles);

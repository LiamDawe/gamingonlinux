<?php
define("TITLE_MAX_LENGTH", 55);

$templating->load('blocks/block_comments_latest');
$templating->block('list');

if ($core->config('pretty_urls') == 1)
{
	$latest_link = '/latest-comments/';
}
else {
	$latest_link = url . 'index.php?module=comments_latest';
}
$templating->set('latest_link', $latest_link);

$comments_per_page = $core->config('default-comments-per-page');
if (isset($_SESSION['per-page']))
{
	$comments_per_page = $_SESSION['per-page'];
}

$comment_posts = '';
$db->sqlquery("SELECT c.`comment_id`, c.`article_id`, c.`time_posted`, a.`title`, a.`slug`, a.`comment_count`, a.`active` FROM `articles_comments` c INNER JOIN `articles` a ON c.`article_id` = a.`article_id` WHERE a.`active` = 1 AND c.`approved` = 1 ORDER BY `comment_id` DESC limit 5");
while ($comments = $db->fetch())
{
	$date = $core->human_date($comments['time_posted']);

	$title_length = strlen($comments['title']);
	if ($title_length >= TITLE_MAX_LENGTH)
	{
		$title = substr($comments['title'], 0, TITLE_MAX_LENGTH);
		$title = $title . '&hellip;';
	}
	else
	{
		$title = $comments['title'];
	}

	$page = 1;
	if ($comments['comment_count'] > $comments_per_page)
	{
		$page = ceil($comments['comment_count'] / $comments_per_page);
	}
	
	$article_link = $article_class->get_link($comments['article_id'], $comments['slug'], 'page=' . $page . '#' . $comments['comment_id']);

	$comment_posts .= "<li class=\"list-group-item\">
	<a href=\"{$article_link}\">{$title}</a><br />
	<small>{$date}</small>
</li>";
}

$templating->set('comment_posts', $comment_posts);

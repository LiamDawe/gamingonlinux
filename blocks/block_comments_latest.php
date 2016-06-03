<?php
// main menu block
$templating->merge('blocks/block_comments_latest');
$templating->block('list');

if (core::config('pretty_urls') == 1)
{
	$latest_link = '/latest-comments/';
}
else {
	$latest_link = url . 'index.php?module=comments_latest';
}
$templating->set('latest_link', $latest_link);

$comment_posts = '';
$db->sqlquery("SELECT comment_id, c.`article_id`, c.`time_posted`, a.`title`, a.comment_count, a.active FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE a.active = 1 ORDER BY `comment_id` DESC limit 5");
while ($comments = $db->fetch())
{
	$date = $core->format_date($comments['time_posted']);
	$title = substr($comments['title'], 0, 55);
	$title = ucwords($title);
	$title = $title . '...';

	$page = 1;
	if ($comments['comment_count'] > $_SESSION['per-page'])
	{
		$page = ceil($comments['comment_count']/$_SESSION['per-page']);
	}

	$comment_posts .= "<li class=\"list-group-item\">
	<a href=\"/articles/{$core->nice_title($comments['title'])}.{$comments['article_id']}/page={$page}#{$comments['comment_id']}\">{$title}</a>
	<small>{$date}</small>
</li>";


}

$templating->set('comment_posts', $comment_posts);

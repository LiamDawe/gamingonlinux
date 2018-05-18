<?php
define("TITLE_MAX_LENGTH", 55);

$templating->load('blocks/block_comments_latest');
$templating->block('list');

$comments_per_page = $core->config('default-comments-per-page');
if (isset($_SESSION['per-page']))
{
	$comments_per_page = $_SESSION['per-page'];
}

$comment_posts = '';
$fetch_comments = $dbl->run("SELECT c.`comment_id`, c.`article_id`, c.`time_posted`, a.`title`, a.`slug`, u.username FROM `articles_comments` c INNER JOIN `articles` a ON c.`article_id` = a.`article_id` INNER JOIN `users` u ON u.user_id = c.author_id WHERE a.`active` = 1 AND c.`approved` = 1 ORDER BY `comment_id` DESC limit 5")->fetch_all();
foreach ($fetch_comments as $comments)
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

	$article_link = $article_class->get_link($comments['article_id'], $comments['slug'], 'comment_id=' . $comments['comment_id']);

	$machine_time = date("Y-m-d\TH:i:s", $comments['time_posted']) . 'Z';

	$comment_posts .= "<li class=\"list-group-item\">
	<a href=\"{$article_link}\">{$title}</a><br />
	<small><time class=\"timeago\" datetime=\"$machine_time\">{$date}</time> - {$comments['username']}</small>
</li>";
}

$templating->set('comment_posts', $comment_posts);

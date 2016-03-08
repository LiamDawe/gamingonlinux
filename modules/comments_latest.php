<?php
$templating->set_previous('title', 'Latest Comments', 1);
$templating->set_previous('meta_description', 'The latest article comments on GamingOnLinux.com', 1);

// main menu block
$templating->merge('comments_latest');
$templating->block('list');

$comment_posts = '';
$db->sqlquery("SELECT comment_id, c.`article_id`, c.`time_posted`, c.`comment_text`, c.guest_username, a.`title`, a.comment_count, a.active, u.username, u.user_id FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id LEFT JOIN users u ON u.user_id = c.author_id WHERE a.active = 1 ORDER BY `comment_id` DESC limit 20");
while ($comments = $db->fetch())
{
	$date = $core->format_date($comments['time_posted']);
	$text = substr($comments['comment_text'], 0, 150);
	$title = $comments['title'];
	$text = $text . '...';

	$page = 1;
	if ($comments['comment_count'] > 10)
	{
		$page = ceil($comments['comment_count']/10);
	}

	if (isset($comments['guest_username']) && !empty($comments['guest_username']))
	{
		$username = $comments['guest_username'];
	}
	else
	{
		$username = "<a href=\"/profiles/{$comments['user_id']}\">{$comments['username']}</a>";
	}

	$comment_posts .= "<li class=\"list-group-item\">
	<a href=\"/articles/{$core->nice_title($comments['title'])}.{$comments['article_id']}/page={$page}#comments\">{$title}</a><br />
	$text<br />
	<small>by {$username} {$date}</small>
</li>";


}

$templating->set('comment_posts', $comment_posts);

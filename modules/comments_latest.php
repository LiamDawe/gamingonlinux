<?php
$templating->set_previous('title', 'Latest Comments', 1);
$templating->set_previous('meta_description', 'The latest article comments on GamingOnLinux', 1);

// main menu block
$templating->load('comments_latest');
$templating->block('list');

$comment_posts = '';
$res = $dbl->run("SELECT 
	comment_id, 
	c.`article_id`, 
	c.`time_posted`, 
	c.`comment_text`, 
	c.guest_username, 
	a.`title`, 
	a.`slug`,
	a.comment_count, 
	a.active, 
	u.username, 
	u.user_id 
FROM 
	`articles_comments` c 
INNER JOIN 
	`articles` a ON c.article_id = a.article_id 
LEFT JOIN 
	`users` u ON u.user_id = c.author_id 
WHERE 
	a.active = 1 AND c.`approved` = 1 ORDER BY `comment_id` DESC limit 20")->fetch_all();
foreach ($res as $comments)
{
	$date = $core->human_date($comments['time_posted']);

	// remove quotes, it's not their actual comment, and can leave half-open quotes laying around
	$text = preg_replace('/\[quote\=(.+?)\](.+?)\[\/quote\]/is', "", $comments['comment_text']);
	$text = preg_replace('/\[quote\](.+?)\[\/quote\]/is', "", $text);

	//Warp sentenses at 150 char-ish. So it only get's cut at a whole word
	//Now don't go and use the keyword "<!WRAP!>" in any comment please. It will break this thing
	$text = wordwrap($bbcode->remove_bbcode($text), 150, "<!WRAP!>", true);
	if (strpos($text, "<!WRAP!>") !== FALSE) // Sometimes it's possible the comment was shorter then 150 char, it doesn't include the keyword then
	{
		$text = substr($text, 0, strpos($text, "<!WRAP!>"));
	}
	$text = $text . '&hellip;'; //Use actual ellipsis char
	$title = $comments['title'];

	$page = 1;
	if ($comments['comment_count'] > $_SESSION['per-page'])
	{
		$page = ceil($comments['comment_count']/$_SESSION['per-page']);
	}

	if (isset($comments['guest_username']) && !empty($comments['guest_username']))
	{
		$username = $comments['guest_username'];
	}
	else
	{
		$username = "<a href=\"/profiles/{$comments['user_id']}\">{$comments['username']}</a>";
	}
	
	$article_link = $article_class->get_link($comments['article_id'], $comments['slug'], 'page=' . $page . '#r' . $comments['comment_id']);

	$machine_time = date("Y-m-d\TH:i:s", $comments['time_posted']) . 'Z';	

	$comment_posts .= '<li class="list-group-item">
	<a href="'.$article_link.'">'.$title.'</a><br />
	'.$text.'<br />
	<small>by '.$username.' <time class="timeago" datetime="'.$machine_time.'">'.$date.'</time></small>
	</li>';
}

$templating->set('comment_posts', $comment_posts);

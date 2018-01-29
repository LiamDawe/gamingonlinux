<?php
$templating->set_previous('title', 'Latest Comments', 1);
$templating->set_previous('meta_description', 'The latest article comments on GamingOnLinux', 1);

$templating->load('comments_latest');

// count how many there is in total
$total = $dbl->run("SELECT COUNT(c.`comment_id`) FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`approved` = 1 AND a.active = 1")->fetchOne();

$page = core::give_page();

if ($core->config('pretty_urls') == 1)
{
	$pagination_linky = "/latest-comments/";
}
else
{
	$pagination_linky = "index.php?module=comments_latest&amp;";
}

// sort out the pagination link
$pagination = $core->pagination_link(30, $total, $core->config('website_url') . $pagination_linky, $page);

// get top of comments section
$templating->block('more_comments');

$comment_posts = '';
$all_comments = $dbl->run("SELECT comment_id, c.author_id, c.`comment_text`, c.`article_id`, c.`time_posted`, a.`title`, a.`slug`, a.comment_count, a.active FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE c.`approved` = 1 AND a.active = 1 ORDER BY c.`comment_id` DESC LIMIT ?, 30", array($core->start))->fetch_all();
					
// make an array of all comment ids to search for likes (instead of one query per comment for likes)
$like_array = [];
$sql_replacers = [];
foreach ($all_comments as $id_loop)
{
	$like_array[] = $id_loop['comment_id'];
	$sql_replacers[] = '?';
}

if (!empty($sql_replacers))
{
	// Total number of likes for the comments
	$get_likes = $dbl->run("SELECT data_id, COUNT(*) FROM likes WHERE data_id IN ( ".implode(',', $sql_replacers)." ) AND `type` = 'comment' GROUP BY data_id", $like_array)->fetch_all(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
}
					
foreach ($all_comments as $comments)
{
	$date = $core->human_date($comments['time_posted']);
	$title = $comments['title'];

	if ($core->config('pretty_urls') == 1)
	{
		$profile_link = "/profiles/" . $_GET['user_id'];
	}
	else 
	{
		$profile_link = "/index.php?module=profile&amp;user_id=" . $comments['author_id'];
	}
	
	$templating->set('profile_link', $profile_link);
						
	// sort out the likes
	$likes = NULL;
	if (isset($get_likes[$comments['comment_id']]))
	{
		$likes = ' <span class="profile-comments-heart icon like"></span> Likes: ' . $get_likes[$comments['comment_id']][0];
	}
						
	$view_comment_link = $article_class->get_link($comments['article_id'], $comments['slug'], 'comment_id=' . $comments['comment_id']);
	$view_article_link = $article_class->get_link($comments['article_id'], $comments['slug']);
	$view_comments_full_link = $article_class->get_link($comments['article_id'], $comments['slug'], '#comments');

	$comment_text = $bbcode->parse_bbcode($comments['comment_text']);

	$comment_posts .= "<div class=\"box\"><div class=\"body group\">
	<strong><a href=\"".$view_comment_link."\">{$title}</a></strong><br />
	<small>{$date}" . $likes ."</small><br />
	<hr />
	<div>".$comment_text."</div>
	<hr />
	<div><a href=\"".$view_comment_link."\">View this comment</a> - <a href=\"".$view_article_link."\">View article</a> - <a href=\"".$view_comments_full_link."\">View full comments</a></div>
	</div></div>";
}

$templating->set('comment_posts', $comment_posts);
$templating->set('pagination', $pagination);
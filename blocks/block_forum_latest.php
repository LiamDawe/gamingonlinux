<?php
// main menu block
$templating->merge('blocks/block_forum_latest');
$templating->block('list');

$forum_posts = '';
$db->sqlquery("SELECT `topic_id`, `topic_title`, `last_post_date`, `replys` FROM `forum_topics` WHERE `approved` = 1 ORDER BY `last_post_date` DESC limit 5");
while ($topics = $db->fetch())
{
	$date = $core->format_date($topics['last_post_date']);

	$post_count = $topics['replys'];
	// if we have already 9 or under replys its simple, as this reply makes 9, we show 9 per page, so it's still the first page
	if ($post_count <= 9)
	{
		// it will be the first page
		$postPage = 1;
		$postNumber = 1;
	}

	// now if the reply count is bigger than or equal to 10 then we have more than one page, a little more tricky
	if ($post_count >= 10)
	{
		$rows_per_page = 9;

		// page we are going to
		$postPage = ceil($post_count / $rows_per_page);

		// the post we are going to
		$postNumber = (($post_count - 1) % $rows_per_page) + 1;
	}


	$title = substr($topics['topic_title'], 0, 55);
	$title = $title . '...';

	if (core::config('pretty_urls') == 1)
	{
		$forum_posts .= "<li class=\"list-group-item\"><a href=\"/forum/topic/{$topics['topic_id']}?page={$postPage}\">{$title}</a><small>{$date}</small></li>";
	}
	else {
		$forum_posts .= '<li><a href="' . url . 'index.php?module=viewtopic&amp;topic_id=' . $topics['topic_id'] . '&amp;page=' . $postPage . '">' . $title . '</a><small>' . $date .'</small></li>';
	}

}

$templating->set('forum_posts', $forum_posts);

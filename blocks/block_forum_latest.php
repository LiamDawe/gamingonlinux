<?php
// main menu block
$templating->load('blocks/block_forum_latest');
$templating->block('list');

$comments_per_page = $core->config('default-comments-per-page');
if (isset($_SESSION['per-page']))
{
	$comments_per_page = $_SESSION['per-page'];
}

$groups_in = str_repeat('?,', count($user->user_groups) - 1) . '?';

$forum_sql = "SELECT p.`forum_id` FROM `forum_permissions` p INNER JOIN `forums` f ON f.forum_id = p.forum_id WHERE `is_category` = 0 AND `can_view` = 1 AND `group_id` IN ($groups_in) GROUP BY forum_id ORDER BY f.name ASC";

// setup a cache
$querykey = "KEY" . md5($forum_sql . serialize($user->user_groups));

$forum_ids = core::$mem->get($querykey); // check cache

if (!$forum_ids) // there's no cache
{
	// get the forum ids this user is actually allowed to view
	$forum_ids = $dbl->run($forum_sql, $user->user_groups)->fetch_all(PDO::FETCH_COLUMN);
	core::$mem->set($querykey, $forum_ids, 21600); // cache for six hours
}

if ($forum_ids)
{
	$forum_id_in  = str_repeat('?,', count($forum_ids) - 1) . '?';

	$forum_posts = '';
	$fetch_topics = $dbl->run("SELECT `topic_id`, `topic_title`, `last_post_date`, `replys` FROM `forum_topics` WHERE `approved` = 1 AND `forum_id` IN ($forum_id_in) ORDER BY `last_post_date` DESC limit 5", $forum_ids)->fetch_all();
	foreach ($fetch_topics as $topics)
	{
		$date = $core->human_date($topics['last_post_date']);

		$post_count = $topics['replys'];
		// if we have already 9 or under replys its simple, as this reply makes 9, we show 9 per page, so it's still the first page
		if ($post_count <= $comments_per_page)
		{
			// it will be the first page
			$postPage = 1;
			$postNumber = 1;
		}

		// now if the reply count is bigger than or equal to 10 then we have more than one page, a little more tricky
		if ($post_count >= $comments_per_page)
		{
			// page we are going to
			$postPage = ceil($post_count / $comments_per_page);

			// the post we are going to
			$postNumber = (($post_count - 1) % $comments_per_page) + 1;
		}

		$title_length = strlen($topics['topic_title']);
		if ($title_length >= 55)
		{
			$title = substr($topics['topic_title'], 0, 65);
			$title = $title . '&hellip;';
		}
		else
		{
			$title = $topics['topic_title'];
		}

		$machine_time = date("Y-m-d\TH:i:s", $topics['last_post_date']) . 'Z';

		if ($postPage > 1)
		{
			$link_page = 'page=' . $postPage;
		}
		else if ($postPage <= 1)
		{
			$link_page = '';
		}

		$forum_posts .= '<li class="list-group-item"><a href="'. $forum_class->get_link($topics['topic_id'], $link_page) . '">' . $title . '</a><br />
		<small><time class="timeago" datetime="'.$machine_time.'">' . $date .'</time></small></li>';
	}
}
else
{
	$forum_posts = '<li class="list-group-item">You do not have permission to view any forums!</li>';
}

$templating->set('forum_posts', $forum_posts);

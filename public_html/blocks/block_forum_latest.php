<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
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
$forum_ids = unserialize($core->get_dbcache($querykey)); // check cache

if (!$forum_ids) // there's no cache
{
	// get the forum ids this user is actually allowed to view
	$forum_ids = $dbl->run($forum_sql, $user->user_groups)->fetch_all(PDO::FETCH_COLUMN);
	$core->set_dbcache($querykey, serialize($forum_ids), 21600); // cache for six hours
}

if ($forum_ids)
{
	$forum_id_in  = str_repeat('?,', count($forum_ids) - 1) . '?';

	$forum_posts = '';
	$fetch_topics = $dbl->run("SELECT p.`post_id`, t.`topic_id`, t.`topic_title`, p.`creation_date`, u.`username` FROM `forum_replies` p INNER JOIN `forum_topics` t ON p.topic_id = t.topic_id INNER JOIN `users` u ON u.user_id = p.author_id WHERE t.`approved` = 1 AND t.`forum_id` IN ($forum_id_in) ORDER BY p.`post_id` DESC limit 5", $forum_ids)->fetch_all();
	foreach ($fetch_topics as $topics)
	{
		$date = $core->time_ago($topics['creation_date']);

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

		$machine_time = date("Y-m-d\TH:i:s", $topics['creation_date']);

		$forum_posts .= '<li class="list-group-item"><a href="'. $forum_class->get_link($topics['topic_id'], 'post_id=' . $topics['post_id']) . '">' . $title . '</a><br />
		<small><time datetime="'.$machine_time.'">' . $date .'</time> - ' . $topics['username'] . '</small></li>';
	}
}
else
{
	$forum_posts = '<li class="list-group-item">You do not have permission to view any forums!</li>';
}

$templating->set('forum_posts', $forum_posts);

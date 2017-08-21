<?php
$templating->set_previous('title', 'Forum', 1);
$templating->set_previous('meta_description', 'Forum', 1);

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'toomany')
	{
		$core->message('Spam prevention: You have made too many forum topics in a really short time, please wait a few minutes.', 1);
	}
}

$templating->load('flat_forum');
$templating->block('top');

$templating->load('forum_search');
$templating->block('small');

// paging for pagination
$page = core::give_page();

$groups_in = str_repeat('?,', count($user->user_groups) - 1) . '?';

// get the forum ids this user is actually allowed to view
$forum_ids = $dbl->run("SELECT p.`forum_id`, f.`name` FROM `forum_permissions` p INNER JOIN `forums` f ON f.forum_id = p.forum_id WHERE `is_category` = 0 AND `can_view` = 1 AND `group_id` IN ($groups_in) GROUP BY forum_id ORDER BY f.name ASC", $user->user_groups)->fetch_all();

$templating->block('options', 'flat_forum');
$new_topic = '';
if (isset($_SESSION['activated']))
{
	if ($_SESSION['activated'] == 1)
	{
		$new_topic = '<a href="/index.php?module=newtopic">New Topic</a>';
	}
}
$templating->set('new_topic', $new_topic);

$forum_list = '';
$forum_address = '';
if ($core->config('pretty_urls') == 1)
{
	$forum_address = '/forum/';
}
else
{
	$forum_address = '/index.php?module=viewforum&forum_id=';
}
foreach ($forum_ids as $forum)
{
	$forum_list .= '<option value="' . $forum_address . $forum['forum_id'] . '">' . $forum['name'] . '</option>';
	$forum_id_list[] = $forum['forum_id'];
}
$templating->set('forum_list', $forum_list);

$forum_id_in  = str_repeat('?,', count($forum_id_list) - 1) . '?';

// get blocked id's
$blocked_sql = '';
$blocked_ids = [];
$blocked_usernames = [];
if (count($user->blocked_users) > 0)
{
	foreach ($user->blocked_users as $username => $blocked_id)
	{
		$blocked_ids[] = $blocked_id[0];
		$blocked_usernames[] = $username;
	}
	
	$blocked_in  = str_repeat('?,', count($blocked_ids) - 1) . '?';

	$blocked_sql = "AND t.`author_id` NOT IN ($blocked_in)";
}

$test = array_merge($forum_id_list, $blocked_ids);

// count how many there is in total
$total_topics = $dbl->run('SELECT COUNT(t.`topic_id`) FROM `forum_topics` t WHERE t.`approved` = 1 AND t.`forum_id` IN ('.$forum_id_in.') ' . $blocked_sql, $test)->fetchOne();

$comments_per_page = $core->config('default-comments-per-page');
if (isset($_SESSION['per-page']))
{
	$comments_per_page = $_SESSION['per-page'];
}

$total_pages = round($total_topics/$comments_per_page);

if ($page > $total_pages)
{
	$page = $total_pages;
}

$this_template = $core->config('website_url') . 'templates/' . $core->config('template');

// sort out the pagination link
$pagination = $core->pagination_link($comments_per_page, $total_topics, "/forum/", $page);

$sql = '
SELECT
	t.`topic_id`,
	t.`forum_id`,
	t.`topic_title`,
	t.`author_id`,
	t.`creation_date`,
	t.`replys`,
	t.`last_post_date`,
	t.`last_post_id`,
	t.`is_locked`,
	f.name as forum_name,
	u.`username`,
	u2.`username` as username_last
FROM
	`forum_topics` t
INNER JOIN
	`forums` f ON t.forum_id = f.forum_id
INNER JOIN
	`users` u ON t.author_id = u.user_id
LEFT JOIN
	`users` u2 ON t.last_post_id = u2.user_id
WHERE
	t.`approved` = 1
AND
	f.forum_id IN ('.$forum_id_in.') ' . $blocked_sql . '
ORDER BY
	t.`last_post_date`
DESC LIMIT ?, '.$comments_per_page;

$get_topics = $dbl->run($sql, array_merge($forum_id_list, $blocked_ids, [$core->start]))->fetch_all();

foreach ($get_topics as $topics)
{
	$templating->block('topics', 'flat_forum');

	$last_date = $core->human_date($topics['last_post_date']);
	$templating->set('tzdate', date('c',$topics['last_post_date']) );

	$post_count = $topics['replys'];
	// if we have already 9 or under replys its simple, as this reply makes 9, we show 9 per page, so it's still the first page
	if ($post_count <= $_SESSION['per-page'])
	{
		// it will be the first page
		$postPage = 1;
		$postNumber = 1;
	}

	// now if the reply count is bigger than or equal to 10 then we have more than one page, a little more tricky
	if ($post_count >= $_SESSION['per-page'])
	{
		$rows_per_page = $_SESSION['per-page'];

		// page we are going to
		$postPage = ceil($post_count / $rows_per_page);

		// the post we are going to
		$postNumber = (($post_count - 1) % $rows_per_page) + 1;
	}
	
	$avatar = $user->sort_avatar($topics['author_id']);

	if ($core->config('pretty_urls') == 1)
	{
		$link = "/forum/topic/{$topics['topic_id']}";
		$last_link = "/forum/topic/{$topics['topic_id']}?page={$postPage}";
		$profile_link = "/profiles/{$topics['author_id']}";
		$forum_link = '/forum/' . $topics['forum_id'];
	}
	else
	{
		$link = "/index.php?module=viewtopic&topic_id={$topics['topic_id']}";
		$last_link = "/index.php?module=viewtopic&topic_id={$topics['topic_id']}&page={$postPage}";
		$profile_link = "/index.php?module=profile&user_id={$topics['author_id']}";
		$forum_link = '/index.php?module=viewforum&forum_id=' . $topics['forum_id'];
	}
	
	$locked = '';
	if ($topics['is_locked'] == 1)
	{
		$locked = ' <img width="15" height="15" src="'.$this_template.'/images/forum/lock.svg" onerror="'.$this_template.'/images/forum/lock.png" alt=""> ';
	}
	$templating->set('is_locked', $locked);

	$templating->set('title', $topics['topic_title']);
	$templating->set('link', $link);
	$replies = '';
	if ($topics['replys'] > 0)
	{
		$replies = '<img width="15" height="12" src="'.$this_template.'/images/comments/replies.svg" onerror="'.$this_template.'/images/comments/replies.png" alt=""> ' . $topics['replys'];
	}
	$templating->set('replies', $replies);
	$templating->set('avatar', $avatar);
	$templating->set('username', $topics['username']);
	$templating->set('last_link', $last_link);
	$templating->set('last_username', $topics['username_last']);
	$templating->set('last_date', $last_date);
	$templating->set('forum_name', $topics['forum_name']);
	$templating->set('forum_link', $forum_link);
	$templating->set('profile_link', $profile_link);
	$templating->set('this_template', $this_template);
}

$templating->block('options', 'flat_forum');
$templating->set('new_topic', $new_topic);
$templating->set('forum_list', $forum_list);

$templating->block('bottom', 'flat_forum');
$templating->set('pagination', $pagination);
?>

<?php
$templating->set_previous('title', 'Forum', 1);
$templating->set_previous('meta_description', 'Forum', 1);

$templating->load('normal_forum');
$templating->block('top');

$templating->load('forum_search');
$templating->block('small');

$sql = "
SELECT
	category.forum_id as CategoryId,
	category.name as CategoryName,
	forum.forum_id as ForumId,
	forum.name as ForumName,
	forum.parent_id as ForumParent,
	forum.description as ForumDescription,
	forum.posts as ForumPosts,
	forum.last_post_user_id as Forum_last_post_id,
	forum.last_post_time,
	forum.last_post_topic_id,
	users.username,
	topic.topic_title,
	topic.replys
FROM
	`forums` category
LEFT JOIN
	`forums` forum ON forum.parent_id = category.forum_id
LEFT JOIN
	`users` users ON forum.last_post_user_id = users.user_id
LEFT JOIN
	`forum_topics` topic ON topic.topic_id = forum.last_post_topic_id
WHERE
	category.is_category = 1
ORDER BY
	category.order, forum.order";

$forum_rows = $dbl->run($sql)->fetch_all();

// start the ids at 0
$current_category_id = 0;
$current_forum_id = 0;
$category_array = array();
$forum_array = array();

// set the forum array so we can use it later and so we don't have to loop it just yet :)
foreach ( $forum_rows as $row )
{
	// make an array of categorys
	if ($current_category_id != $row['CategoryId'])
	{
		$category_array[$row['CategoryId']]['id'] = $row['CategoryId'];
		$category_array[$row['CategoryId']]['name'] = $row['CategoryName'];
		$current_category_id = $row['CategoryId'];
	}

	// make an array of forums
	if ($current_forum_id != $row['ForumId'])
	{
		$forum_array[$row['ForumId']]['id'] = $row['ForumId'];
		$forum_array[$row['ForumId']]['parent'] = $row['ForumParent'];
		$forum_array[$row['ForumId']]['name'] = $row['ForumName'];
		$forum_array[$row['ForumId']]['description'] = $row['ForumDescription'];
		$forum_array[$row['ForumId']]['posts'] = $row['ForumPosts'];
		$forum_array[$row['ForumId']]['last_post_id'] = $row['Forum_last_post_id'];
		$forum_array[$row['ForumId']]['last_post_username'] = $row['username'];
		$forum_array[$row['ForumId']]['last_post_time'] = $row['last_post_time'];
		$forum_array[$row['ForumId']]['last_post_topic_id'] = $row['last_post_topic_id'];
		$forum_array[$row['ForumId']]['topic_title'] = $row['topic_title'];
		$forum_array[$row['ForumId']]['topic_replies'] = $row['replys'];
	}
}

foreach ($category_array as $category)
{
	$templating->block('category_top', 'normal_forum');
	$templating->set('category_name', $category['name']);

	foreach ($forum_array as $forum)
	{
		// show this categorys forums
		if ($forum['parent'] == $category['id'])
		{
			$templating->block('forum_row', 'normal_forum');
			$templating->set('this_template', $core->config('website_url') . '/templates/' . $core->config('template'));

			if ($core->config('pretty_urls') == 1)
			{
				$forum_link = "/forum/" . $forum['id'] . '/';
			}
			else {
				$forum_link = $core->config('website_url') . 'index.php?module=viewforum&amp;forum_id=' . $forum['id'];
			}
			$templating->set('forum_link', $forum_link);

			$templating->set('forum_name', $forum['name']);
			$templating->set('forum_description', $forum['description']);
			$templating->set('forum_posts', $forum['posts']);

			$last_title = '';
			$last_username = 'No one has posted yet!';
			$last_post_time = 'Never';
			if (!empty($forum['last_post_username']))
			{
				$post_count = $forum['topic_replies'];
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

				if ($core->config('pretty_urls') == 1)
				{
					$last_title = "<a href=\"/forum/topic/{$forum['last_post_topic_id']}?page={$postPage}\">{$forum['topic_title']}</a>";
				}
				else {
					$last_title = "<a href=\"index.php?module=viewtopic&amp;topic_id={$forum['last_post_topic_id']}&amp;page={$postPage}\">{$forum['topic_title']}</a>";
				}


				$last_username = "<a href=\"/profiles/{$forum['last_post_id']}\">{$forum['last_post_username']}</a>";
				$last_post_time = $core->format_date($forum['last_post_time']);
			}
			$templating->set('last_post_title', $last_title);
			$templating->set('last_post_username', $last_username);
			$templating->set('last_post_time', $last_post_time);
		}
	}
	$templating->block('category_bottom', 'normal_forum');
}

$templating->block('latest', 'normal_forum');
$templating->set('this_template', $core->config('website_url') . '/templates/' . $core->config('template'));

// lastest posts block below forums
$forum_posts = '';
$topics = $dbl->run("SELECT `topic_id`, `topic_title`, `last_post_date`, `replys` FROM `forum_topics` WHERE `approved` = 1 ORDER BY `last_post_date` DESC limit 7")->fetch_all();
if ($topics)
{
	$date = $core->format_date($topics['last_post_date']);

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

	$forum_posts .= "<a href=\"/forum/topic/{$topics['topic_id']}?page={$postPage}\"><i class=\"icon-comment\"></i> {$topics['topic_title']}</a> {$date}<br />";
}
else
{
	$forum_posts = 'No one has posted yet!';
}

$templating->set('forum_posts', $forum_posts);
?>

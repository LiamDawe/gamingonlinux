<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Forum', 1);
$templating->set_previous('meta_description', 'Forum', 1);

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'toomany')
	{
		$core->message('Spam prevention: You have made too many forum topics in a really short time, please wait a few minutes.', 1);
	}
}

$templating->load('forum');
$templating->block('top');

$templating->load('forum_search');
$templating->block('small');

// paging for pagination
$page = core::give_page();

$groups_in = str_repeat('?,', count($user->user_groups) - 1) . '?';

// get the forum ids this user is actually allowed to view
$forum_ids = $dbl->run("SELECT p.`forum_id`, f.`name` FROM `forum_permissions` p INNER JOIN `forums` f ON f.forum_id = p.forum_id WHERE `is_category` = 0 AND `can_view` = 1 AND `group_id` IN ($groups_in) GROUP BY forum_id ORDER BY f.name ASC", $user->user_groups)->fetch_all();

if (!$forum_ids)
{
	$core->message('There are no forums you can view currently.');
}
else
{
	$templating->block('options', 'forum');
	$new_topic = '';
	if (isset($_SESSION['activated']))
	{
		if ($_SESSION['activated'] == 1)
		{
			$new_topic = '<li><a class="forum_button blue" href="/index.php?module=newtopic">Create Post</a></li>';
		}
	}
	$templating->set('new_topic', $new_topic);

	$latest_active = '';
	$categories_active = '';
	if (!isset($_GET['type']) || isset($_GET['type']) && $_GET['type'] == 'latest')
	{
		$latest_active = 'active';
	}
	if (isset($_GET['type']) && $_GET['type'] == 'categories')
	{
		$categories_active = 'active';
	}
	$templating->set('latest_active', $latest_active);
	$templating->set('categories_active', $categories_active);

	$forum_list = '';
	foreach ($forum_ids as $forum)
	{
		$forum_list .= '<option value="/forum/' . $forum['forum_id'] . '">' . $forum['name'] . '</option>';
		$forum_id_list[] = $forum['forum_id'];
	}
	$templating->set('forum_list', $forum_list);

	$forum_id_in  = str_repeat('?,', count($forum_id_list) - 1) . '?';

	if (!isset($_GET['type']) || isset($_GET['type']) && $_GET['type'] == 'latest')
	{
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

		$total_pages = ceil($total_topics/$comments_per_page);

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
			t.`is_locked`,
			t.`has_poll`,
			t.`last_post_id`,
			t.last_post_user_id,
			f.name as forum_name,
			u.`username`,
			u.`avatar`, 
			u.`avatar_uploaded`, 
			u.`avatar_gallery`,
			u2.`username` as username_last
		FROM
			`forum_topics` t
		INNER JOIN
			`forums` f ON t.forum_id = f.forum_id
		LEFT JOIN
			`users` u ON t.author_id = u.user_id
		LEFT JOIN
			`users` u2 ON t.last_post_user_id = u2.user_id
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
			$templating->block('topics', 'forum');

			$last_date = $core->human_date($topics['last_post_date']);
			$templating->set('tzdate', date('c',$topics['last_post_date']) );
			
			$avatar = $user->sort_avatar($topics);

			$profile_link = "/profiles/{$topics['author_id']}";
			$profile_last_link = "/profiles/{$topics['last_post_user_id']}";
			$forum_link = '/forum/' . $topics['forum_id'];

			$link = $forum_class->get_link($topics['topic_id']);
			
			$locked = '';
			if ($topics['is_locked'] == 1)
			{
				$locked = ' <img width="15" height="15" src="'.$this_template.'/images/forum/lock.svg" alt=""> ';
			}
			$templating->set('is_locked', $locked);
			$poll_title = '';
			if ($topics['has_poll'] == 1)
			{
				$poll_title = '<strong>POLL:</strong> ';
			}
			$templating->set('title', $poll_title . $topics['topic_title']);
			$templating->set('link', $link);
			$replies = '';
			$last_link = $link;
			$last_link = $forum_class->get_link($topics['topic_id'], 'post_id=' . $topics['last_post_id']);
			$replies = '<img width="15" height="12" src="'.$this_template.'/images/comments/replies.svg" alt="">  ' . $topics['replys'] . ' replies';
			$templating->set('replies', $replies);
			$templating->set('avatar', $avatar);
			if (isset($topics['username']))
			{
				$author_username = $topics['username'];
			}
			else
			{
				$author_username = 'Guest';
			}
			$templating->set('username', $author_username);
			$templating->set('last_link', $last_link);
			$templating->set('last_username', $topics['username_last']);
			$templating->set('profile_last_link', $profile_last_link);
			$templating->set('last_date', $last_date);
			$templating->set('forum_name', $topics['forum_name']);
			$templating->set('forum_link', $forum_link);
			$templating->set('profile_link', $profile_link);
			$templating->set('this_template', $this_template);
		}

		$templating->block('options', 'forum');
		$templating->set('new_topic', $new_topic);
		$templating->set('forum_list', $forum_list);
		$templating->set('latest_active', $latest_active);
		$templating->set('categories_active', $categories_active);

		$templating->block('bottom', 'forum');
		$templating->set('pagination', $pagination);
	}
	else if (isset($_GET['type']) && $_GET['type'] == 'categories')
	{
		// check if they've read it
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
		{
			$last_read = $dbl->run("SELECT `forum_id`, `last_read` FROM `user_forum_read` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch_all(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
		}
		
		$sql = "
		SELECT
			category.forum_id as CategoryId,
			category.name as CategoryName,
			forum.forum_id as ForumId,
			forum.name as ForumName,
			forum.parent_id as ForumParent,
			forum.description as ForumDescription,
			forum.posts as ForumPosts,
			forum.last_post_user_id as Forum_last_post_user_id,
			forum.last_post_time,
			forum.last_post_topic_id,
			users.username,
			topic.topic_title,
			topic.replys,
			topic.last_post_id
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
		AND
			forum.forum_id IN (".$forum_id_in.")
		ORDER BY
			category.order, forum.order";
		
		$forum_rows = $dbl->run($sql, $forum_id_list)->fetch_all();
		
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
				$forum_array[$row['ForumId']]['last_post_user_id'] = $row['Forum_last_post_user_id'];
				$forum_array[$row['ForumId']]['last_post_username'] = $row['username'];
				$forum_array[$row['ForumId']]['last_post_time'] = $row['last_post_time'];
				$forum_array[$row['ForumId']]['last_post_topic_id'] = $row['last_post_topic_id'];
				$forum_array[$row['ForumId']]['topic_title'] = $row['topic_title'];
				$forum_array[$row['ForumId']]['topic_replies'] = $row['replys'];
				$forum_array[$row['ForumId']]['last_post_id'] = $row['last_post_id'];
			}
		}
	
		foreach ($category_array as $category)
		{
			$templating->block('category_top', 'forum');
			$templating->set('category_name', $category['name']);
	
			foreach ($forum_array as $forum)
			{
				// show this categorys forums
				if ($forum['parent'] == $category['id'])
				{
					$templating->block('forum_row', 'forum');
					$templating->set('this_template', $core->config('website_url') . 'templates/' . $core->config('template'));
	
					// get the correct forum icon
					$forum_icon = 'forum_icon.png';
					if (isset($last_read))
					{
						if (isset($last_read[$forum['id']][0]) && $last_read[$forum['id']][0] >= $forum['last_post_time'])
						{
							$forum_icon = 'forum_icon_read.png';
						}
						else
						{
							$forum_icon = 'forum_icon.png';
						}
					}
					$templating->set('forum_icon', $forum_icon);
					$templating->set('forum_link', '/forum/' . $forum['id'] . '/');
					$templating->set('forum_name', $forum['name']);
					$templating->set('forum_description', $forum['description']);
					$templating->set('forum_posts', $forum['posts']);
	
					$last_title = '';
					$last_username = 'No one has posted yet!';
					$last_post_time = 'Never';
					if (!empty($forum['last_post_username']))
					{
						$last_post_link = '';
						if ($forum['topic_replies'] == 0)
						{
							$last_post_link = $forum_class->get_link($forum['last_post_topic_id']);
						}
						else if ($forum['topic_replies'] > 0)
						{
							$last_post_link = $forum_class->get_link($forum['last_post_topic_id'], 'post_id=' . $forum['last_post_id']);
							
						}
						$last_title = '<a href="'.$last_post_link.'">'.$forum['topic_title'].'</a>';
	
						$last_username = "<a href=\"/profiles/{$forum['last_post_user_id']}\">{$forum['last_post_username']}</a>";
						$last_post_time = $core->human_date($forum['last_post_time']);
					}
					$templating->set('last_post_title', $last_title);
					$templating->set('last_post_username', $last_username);
					$templating->set('last_post_time', $last_post_time);
				}
			}
			$templating->block('category_bottom', 'forum');
		}
	
		$templating->block('latest', 'forum');
		$templating->set('this_template', $core->config('website_url') . '/templates/' . $core->config('template'));
	
		// lastest posts block below forums
		$forum_posts = '';
		$grab_topics = $dbl->run("SELECT `topic_id`, `topic_title`, `last_post_date`, `last_post_id` FROM `forum_topics` WHERE `approved` = 1 AND forum_id IN (".$forum_id_in.") ORDER BY `last_post_date` DESC limit 7", $forum_id_list)->fetch_all();
		if ($grab_topics)
		{
			foreach ($grab_topics as $topics)
			{
				$date = $core->human_date($topics['last_post_date']);
	
				$last_post = '';
				if ($topics['last_post_id'] != NULL)
				{
					$last_post = 'post_id=' . $topics['last_post_id'];
				}
	
				$forum_posts .= "<a href=\"/forum/topic/{$topics['topic_id']}/$last_post\"><i class=\"icon-comment\"></i> {$topics['topic_title']}</a> {$date}<br />";
			}
		}
		else
		{
			$forum_posts = 'No one has posted yet!';
		}
		$templating->set('forum_posts', $forum_posts);
	}
}
?>

<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}

$forum_id = NULL;
if (isset($_GET['forum_id']) && is_numeric($_GET['forum_id']))
{
	$forum_id = $_GET['forum_id'];
}
else if (isset(core::$url_command[0]) && isset(core::$url_command[1]))
{
	$find_forum = $dbl->run("SELECT `forum_id` FROM `forums` WHERE `pretty_url` = ?", array(core::$url_command[1]))->fetch();
	if ($find_forum)
	{
		$forum_id = $find_forum['forum_id'];
	}
}

if (!core::is_number($forum_id))
{
	$core->message('The forum ID has to be a number!', 1);
	include('includes/footer.php');
	die();
}

$parray = $forum_class->forum_permissions($forum_id);

// permissions for viewforum page
if($parray['can_view'] == 0)
{
	$templating->set_previous('title', 'No access', 1);
	$core->message('You do not have permission to view this forum!', 1);
}

else
{
	$this_template = $core->config('website_url') . 'templates/' . $core->config('template');

	// update the time the last read this forum for forum icons on normal category forum view
	if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
	{
		$check = $dbl->run("SELECT `last_read` FROM `user_forum_read` WHERE `user_id` = ? AND `forum_id` = ?", array($_SESSION['user_id'], $forum_id))->fetchOne();
		if ($check)
		{
			$dbl->run("UPDATE `user_forum_read` SET `last_read` = ? WHERE `user_id` = ? AND `forum_id` = ?", array(core::$date, $_SESSION['user_id'], $forum_id));
		}
		else
		{
			$dbl->run("INSERT INTO `user_forum_read` SET `last_read` = ?, `user_id` = ?, `forum_id` = ?", array(core::$date, $_SESSION['user_id'], $forum_id));
		}
	}

	// paging for pagination
	$page = core::give_page();

	$templating->load('viewforum');

	$details = $dbl->run("SELECT `name`, `rss_password` FROM `forums` WHERE forum_id = ?", array($forum_id))->fetch();

	$templating->set_previous('title', 'Viewing forum ' . $details['name'], 1);
	$templating->set_previous('meta_description', 'GamingOnLinux forum - Viewing forum ' . $details['name'], 1);

	$templating->block('main_top', 'viewforum');
	$templating->set('forum_name', $details['name']);
	$templating->set('forum_id', (int) $forum_id);

	$rss_pass = NULL;
	if ($details['rss_password'] != NULL)
	{
		$rss_pass = '&amp;rss_pass='.$details['rss_password'];
	}
	$templating->set('rss_pass', $rss_pass);

	$templating->load('forum_search');
	$templating->block('small');
	$templating->set('search_text','');

	// get the forum ids this user is actually allowed to view
	$groups_in = str_repeat('?,', count($user->user_groups) - 1) . '?';
	$forum_ids = $dbl->run("SELECT p.`forum_id`, f.`name` FROM `forum_permissions` p INNER JOIN `forums` f ON f.forum_id = p.forum_id WHERE `is_category` = 0 AND `can_view` = 1 AND `group_id` IN ($groups_in) GROUP BY forum_id ORDER BY f.name ASC", $user->user_groups)->fetch_all();
	$templating->block('options', 'viewforum');
	$forum_list = '';
	foreach ($forum_ids as $forum)
	{
		$forum_list .= '<option value="/forum/' . $forum['forum_id'] . '">' . $forum['name'] . '</option>';
		$forum_id_list[] = $forum['forum_id'];
	}
	$templating->set('forum_list', $forum_list);

	$new_topic = '';
	$new_topic_bottom = '';
	if (!isset($_SESSION['activated']))
	{
		$get_active = $dbl->run("SELECT `activated` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']))->fetch();
		$_SESSION['activated'] = $get_active['activated'];
	}

	if ($parray['can_topic'] == 1)
	{
		if (isset($_SESSION['activated']) && $_SESSION['activated'] == 1)
		{
			$new_topic = '<li class="green"><a class="forum_button" href="/index.php?module=newtopic&amp;forum_id='.(int) $forum_id.'">Create Post</a></li>';
			$new_topic_bottom = "<div class=\"fright\"><span class=\"badge blue\"><a class=\"\" href=\"" . $core->config('website_url') . "index.php?module=newtopic&amp;forum_id={$forum_id}\">Create Post</a></span></div>";
		}
	}
	$templating->set('new_topic_link', $new_topic);
	
	// get blocked id's
	$blocked_sql = '';
	$blocked_ids = [];
	if (count($user->blocked_users) > 0)
	{
		foreach ($user->blocked_users as $username => $blocked_id)
		{
			$blocked_ids[] = $blocked_id[0];
		}

		$in  = str_repeat('?,', count($blocked_ids) - 1) . '?';
		$blocked_sql = "AND t.`author_id` NOT IN ($in)";
	}

	// count how many there is in total
	$total_topics = $dbl->run('SELECT COUNT(t.`topic_id`) FROM `forum_topics` t WHERE t.`forum_id` = ? ' . $blocked_sql, array_merge([$forum_id],$blocked_ids))->fetchOne();
	
	$per_page = $core->config('default-comments-per-page');
	if (isset($_SESSION['per-page']) && core::is_number($_SESSION['per-page']))
	{
		$per_page = $_SESSION['per-page'];
	}

	// sort out the pagination link
	$pagination = $core->pagination_link($per_page, $total_topics, "/forum/{$forum_id}/", $page);

	// get the posts for this forum
	$all_posts = $dbl->run('SELECT
		t.*,
		u.`username`,
		u.`avatar`,
		u.`avatar_uploaded`,
		u.`avatar_gallery`,
		u2.`username` as `username_last`,
		u2.`user_id` as `user_id_last`
		FROM `forum_topics` t
		LEFT JOIN `users` u ON t.`author_id` = u.`user_id`
		LEFT JOIN `users` u2 ON t.`last_post_user_id` = u2.`user_id`
		WHERE t.`forum_id`= ? AND t.`approved` = 1 ' . $blocked_sql . '
		ORDER BY t.`is_sticky` DESC, t.`last_post_date` DESC LIMIT ?, ' . $per_page, array_merge([$forum_id], $blocked_ids, [$core->start]))->fetch_all();

	$pinned = 0;
	$normal_test = 0;
	foreach ($all_posts as $post)
	{
		// detect if we have sticky/pinned topics
		if ($post['is_sticky'] == 1)
		{
			$pinned++;
		}
		// if we're on the first sticky topic, show the notice (only once)
		if ($pinned == 1 && $post['is_sticky'] == 1)
		{
			$templating->block('pinned', 'viewforum');
		}

		if ($post['is_sticky'] == 0 && $pinned != 0)
		{
			$normal_test++;
			if ($normal_test == 1)
			{
				$templating->block('normal_posts', 'viewforum');
			}
		}

		$pagination_post = '';
		
		$rows_per_page = $_SESSION['per-page'];
		$lastpage = ceil($post['replys']/$rows_per_page);

		$profile_link = "/profiles/{$post['author_id']}";

		// sort out the per-topic pagination shown beside the post title
		if ($post['replys'] > $rows_per_page)
		{
			// the numbers
			$pages = array();

			// If 7 or less pages show all numbers
			if ($lastpage <= 7)
			{
				for ($i = 1; $i <= $lastpage; $i++)
				{
					$page_link = $forum_class->get_link($post['topic_id'], 'page=' . $i);
					$pages[] = " <li><a class=\"pagination_small\" href=\"$page_link\">$i</a></li>";
				}

				$pagination_post = " <ul class=\"pagination_small pagination\">" . implode(' ', $pages) . "</ul>";
			}

			// if more than 7 pages then put ... in the middle to save space
			else if ($lastpage > 7)
			{
				for ($i = 1; $i <= 3; $i++)
				{
					$page_link = $forum_class->get_link($post['topic_id'], 'page=' . $i);
					$pages[] = "<li><a class=\"pagination_small\" href=\"$page_link\">$i</a></li>";
				}

				$end_page = $forum_class->get_link($post['topic_id'], 'page=' . $lastpage);
				$lastlink = "<li><a class=\"pagination_small\" href=\"$end_page\">$lastpage</a></li>";

				$pagination_post = " <ul class=\"pagination_small pagination\">" . implode(' ', $pages) . "<li class=\"pagination-disabled\"><a href=\"#\">....</a></li>{$lastlink}</ul>";
			}
		}
		$templating->block('post_row', 'viewforum');

		$templating->set('profile_link', $profile_link);

		// sort out user icon
		$avatar = $user->sort_avatar($post);
		$templating->set('avatar', $avatar);

		// Let them know if it's a sticky post or not
		$sticky = '';
		if ($post['is_sticky'] == 1)
		{
			$sticky = '<span class="glyphicon glyphicon-pushpin"></span>';
		}
		$templating->set('is_sticky', $sticky);

		// Let them know if it's locked or not
		$locked = '';
		if ($post['is_locked'] == 1)
		{
			$locked = ' <img width="15" height="15" src="'.$this_template.'/images/forum/lock.svg" onerror="'.$this_template.'/images/forum/lock.png" alt=""> ';
		}
		$templating->set('is_locked', $locked);

		$topic_link = $forum_class->get_link($post['topic_id']);
		$templating->set('link', $topic_link);
		$templating->set('topic_id', $post['topic_id']);
		$poll_title = '';
		if ($post['has_poll'] == 1)
		{
			$poll_title = '<strong>POLL:</strong> ';
		}
		$templating->set('title', $poll_title . $post['topic_title']);
		$templating->set('author_id', $post['author_id']);
		
		$date = $core->time_ago($post['creation_date']);
		$tzdate = date('c',$post['creation_date']);
		$post_date = '<abbr title="'.$tzdate.'" class="timeago">'.$date.'</abbr>';
		$templating->set('post_date', $post_date);
		$templating->set('post_author', $post['username']);

		$replies = '';
		if ($post['replys'] > 0)
		{
			$replies = '<img width="15" height="12" src="'.$this_template.'/images/comments/replies.svg" onerror="'.$this_template.'/images/comments/replies.png" alt=""> ' . $post['replys'];
		}
		$templating->set('replies', $replies);
		
		$templating->set('pagination_post', $pagination_post);

		$last_link = $topic_link;
		if ($post['replys'] > 0)
		{
			$last_link = $forum_class->get_link($post['topic_id'], 'post_id=' . $post['last_post_id']);
		}
		$templating->set('last_link', $last_link);

		$last_date = $core->time_ago($post['last_post_date']);
		$templating->set('last_date', $last_date);
		$templating->set('tzdate', date('c',$post['last_post_date']) );
		$templating->set('last_username', $post['username_last']);
	}

	$templating->block('main_bottom', 'viewforum');
	$templating->set('new_topic_link', $new_topic_bottom);
	$templating->set('pagination', $pagination);

	if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0 || !isset($_SESSION['user_id']))
	{
		$user_session->login_form(core::current_page_url());
	}
}
?>

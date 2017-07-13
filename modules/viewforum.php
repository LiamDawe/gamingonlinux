<?php
if (!core::is_number($_GET['forum_id']))
{
	$core->message('The forum ID has to be a number!', NULL, 1);
	include('includes/footer.php');
	die();
}

$forum_class->forum_permissions($_GET['forum_id']);

// permissions for viewforum page
if($parray['can_view'] == 0)
{
	$core->message('You do not have permission to view this forum!', NULL, 1);
}

else
{
	$this_template = $core->config('website_url') . 'templates/' . $core->config('template');

	// paging for pagination
	$page = core::give_page();

	$templating->load('forum_search');
	$templating->block('small');

	$templating->load('viewforum');

	$db->sqlquery("SELECT `name` FROM `forums` WHERE forum_id = ?", array($_GET['forum_id']));
	$name = $db->fetch();

	$templating->set_previous('title', "Viewing forum {$name['name']}", 1);
	$templating->set_previous('meta_description', "GamingOnLinux forum - Viewing forum {$name['name']}", 1);

	$templating->block('main_top', 'viewforum');
	$templating->set('forum_name', $name['name']);

	$new_topic = '';
	$new_topic_bottom = '';
	if (!isset($_SESSION['activated']))
	{
		$db->sqlquery("SELECT `activated` FROM `users` WHERE `user_id` = ?", array($_SESSION['user_id']));
		$get_active = $db->fetch();
		$_SESSION['activated'] = $get_active['activated'];
	}

	if ($parray['can_topic'] == 1)
	{
		if (isset($_SESSION['activated']) && $_SESSION['activated'] == 1)
		{
			$new_topic = "<a href=\"" . $core->config('website_url') . "index.php?module=newtopic&amp;forum_id={$_GET['forum_id']}\"><i class=\"icon-comment-alt\"></i> Create New Topic</a>";
			$new_topic_bottom = "<span class=\"block3\"><a href=\"" . $core->config('website_url') . "index.php?module=newtopic&amp;forum_id={$_GET['forum_id']}\"><i class=\"icon-comment-alt\"></i> Create New Topic</a></span><br /><br />";
		}
	}
	$templating->set('new_topic_link', $new_topic);

	// count how many there is in total
	$db->sqlquery("SELECT `topic_id` FROM `forum_topics` WHERE `forum_id` = ?", array($_GET['forum_id']));
	$total_pages = $db->num_rows();
	
	$per_page = $core->config('default-comments-per-page');
	if (isset($_SESSION['per-page']) && core::is_number($_SESSION['per-page']))
	{
		$per_page = $_SESSION['per-page'];
	}

	// sort out the pagination link
	$pagination = $core->pagination_link($per_page, $total_pages, "/forum/{$_GET['forum_id']}/", $page);

	// get the posts for this forum
	$db->sqlquery("SELECT
		t.*,
		u.`username`,
		u.`avatar`,
		u.`gravatar_email`,
		u.`avatar_gravatar`,
		u.`avatar_uploaded`,
		u2.`username` as `username_last`,
		u2.`user_id` as `user_id_last`
		FROM `forum_topics` t
		LEFT JOIN `users` u ON t.`author_id` = u.`user_id`
		LEFT JOIN `users` u2 ON t.`last_post_id` = u2.`user_id`
		WHERE t.`forum_id`= ? AND t.`approved` = 1
		ORDER BY t.`is_sticky` DESC, t.`last_post_date` DESC LIMIT ?, {$per_page}", array($_GET['forum_id'], $core->start));
	while ($post = $db->fetch())
	{		
		$pagination_post = '';
		
		$rows_per_page = $_SESSION['per-page'];
		$lastpage = ceil($post['replys']/$rows_per_page);

		if ($core->config('pretty_urls') == 1)
		{
			$profile_link = "/profiles/{$post['author_id']}";
		}
		else
		{
			$profile_link = "/index.php?module=profile&user_id={$post['author_id']}";
		}

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

		// sort out user icon
		$avatar = $user->sort_avatar($post['author_id']);
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
		$templating->set('topic_link', $topic_link);
		$templating->set('topic_id', $post['topic_id']);
		$templating->set('title', $post['topic_title']);
		$templating->set('author_id', $post['author_id']);
		
		$date = $core->format_date($post['creation_date']);
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

		$last_post = 'No replies!';
		if ($post['replys'] > 0)
		{
			$date = $core->format_date($post['last_post_date']);
			$tzdate = date('c',$post['last_post_date']);
			$last_post = 'Latest by <a href="/profiles/'.$post['user_id_last'].'">'.$post['username_last'].'</a><br />
			<abbr title="'.$tzdate.'" class="timeago">'.$date.'</abbr>';
		}

		$templating->set('last_post', $last_post);
	}

	$templating->block('main_bottom', 'viewforum');
	$templating->set('new_topic_link', $new_topic_bottom);
	$templating->set('pagination', $pagination);
}
?>

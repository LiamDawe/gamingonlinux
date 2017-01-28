<?php
if (!core::is_number($_GET['forum_id']))
{
	$core->message('The forum ID has to be a number!', NULL, 1);
	include('includes/footer.php');
	die();
}

$core->forum_permissions($_GET['forum_id']);

// permissions for viewforum page
if($parray['view'] == 0)
{
	$core->message('You do not have permission to view this forum!');
}

else
{
	// paging for pagination
	$page = core::give_page();

	$templating->merge('forum_search');
	$templating->block('small');

	$templating->merge('viewforum');

	$db->sqlquery("SELECT `name` FROM `forums` WHERE forum_id = ?", array($_GET['forum_id']));
	$name = $db->fetch();

	$templating->set_previous('title', "Viewing forum {$name['name']}", 1);
	$templating->set_previous('meta_description', "GamingOnLinux forum - Viewing forum {$name['name']}", 1);

	if (isset($_GET['message']))
	{
		if ($_GET['message'] == 'toomany')
		{
			$core->message('You have made too many forums topics in a really short time, please wait a while.', NULL, 1);
		}

		if ($_GET['message'] == 'queue')
		{
			$core->message('Your message is now in the mod queue to be manually approved due to spam attacks, please be patient while our editors work. This only happens a few times to start with!', NULL, 1);
		}
	}

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

	if ($parray['topic'] == 1)
	{
		if (isset($_SESSION['activated']) && $_SESSION['activated'] == 1)
		{
			$new_topic = "<a href=\"" . core::config('website_url') . "index.php?module=newtopic&amp;forum_id={$_GET['forum_id']}\"><i class=\"icon-comment-alt\"></i> Create New Topic</a>";
			$new_topic_bottom = "<span class=\"block3\"><a href=\"" . core::config('website_url') . "index.php?module=newtopic&amp;forum_id={$_GET['forum_id']}\"><i class=\"icon-comment-alt\"></i> Create New Topic</a></span><br /><br />";
		}
	}
	$templating->set('new_topic_link', $new_topic);

	// count how many there is in total
	$db->sqlquery("SELECT `topic_id` FROM `forum_topics` WHERE `forum_id` = ?", array($_GET['forum_id']));
	$total_pages = $db->num_rows();
	
	$per_page = core::config('default-comments-per-page');
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

		// sort out the per-topic pagination shown beside the post title
		if ($post['replys'] > $_SESSION['per-page'])
		{

			// This code uses the values in $rows_per_page and $numrows in order to identify the number of the last page.
			$rows_per_page = $_SESSION['per-page'];
			$lastpage = ceil($post['replys']/$rows_per_page);

			// the numbers
			$pages = array();

			// If 7 or less pages show all numbers
			if ($lastpage <= 7)
			{
				for ($i = 1; $i <= $lastpage; $i++)
				{
					if (core::config('pretty_urls') == 1)
					{
						$pages[] = " <li><a href=\"/forum/topic/{$post['topic_id']}/page={$i}\">$i</a></li>";
					}
					else
					{
						$pages[] = " <li><a href=\"/index.php?module=viewtopic&amp;topic_id={$post['topic_id']}&amp;page={$i}\">$i</a></li>";
					}
				}

				$pagination_post = "<div class=\"fleft\"><ul class=\"pagination\">" . implode(' ', $pages) . "</ul></div><div class=\"clearer\"></div>";
			}

			// if more than 7 pages then put ... in the middle to save space
			else if ($lastpage > 7)
			{
				for ($i = 1; $i <= 7; $i++)
				{
					$pages[] = "<li><a href=\"/forum/topic/{$post['topic_id']}/page={$i}\">$i</a></li>";
				}

				$lastlink = "<li><a href=\"/forum/topic/{$post['topic_id']}/page={$lastpage}\">$lastpage</a></li>";

				$pagination_post = "<div class=\"fleft\"><ul class=\"pagination\">" . implode(' ', $pages) . "<li class=\"pagination-disabled\"><a href=\"#\">....</a></li>{$lastlink}</ul></div><div class=\"clearer\"></div>";
			}
		}
		$templating->block('post_row', 'viewforum');

		// sort out topic icon
		$topic_pip = '/templates/default/images/topic_icon.png';

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
			$locked = '<strong>Locked</strong> ';
		}
		$templating->set('is_locked', $locked);

		$templating->set('topic_pip', $topic_pip);

		if (core::config('pretty_urls') == 1)
		{
			$topic_link = "/forum/topic/{$post['topic_id']}";
		}
		else
		{
			$topic_link = "/index.php?module=viewtopic&amp;topic_id={$post['topic_id']}";
		}

		$templating->set('topic_link', $topic_link);
		$templating->set('topic_id', $post['topic_id']);
		$templating->set('post_title', $post['topic_title']);
		$templating->set('author_id', $post['author_id']);
		$templating->set('post_date', $core->format_date($post['creation_date']));
		$templating->set('post_author', $post['username']);
		$templating->set('post_replys', $post['replys']);
		$templating->set('post_views', $post['views']);
		$templating->set('pagination_post', $pagination_post);

		$username_last = 'No replies!';
		if (!empty($post['username_last']))
		{
			$date = $core->format_date($post['last_post_date']);
			$username_last = "by <a href=\"/profiles/{$post['user_id_last']}\">{$post['username_last']}</a><br />
			{$date}";
		}

		$templating->set('last_post_name', $username_last);
	}

	$templating->block('main_bottom', 'viewforum');
	$templating->set('new_topic_link', $new_topic_bottom);
	$templating->set('pagination', $pagination);
}
?>

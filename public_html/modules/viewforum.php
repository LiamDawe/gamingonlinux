<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
if (!core::is_number($_GET['forum_id']))
{
	$core->message('The forum ID has to be a number!', 1);
	include('includes/footer.php');
	die();
}

$parray = $forum_class->forum_permissions($_GET['forum_id']);

// permissions for viewforum page
if($parray['can_view'] == 0)
{
	$core->message('You do not have permission to view this forum!', 1);
}

else
{
	$this_template = $core->config('website_url') . 'templates/' . $core->config('template');

	// update the time the last read this forum for forum icons on normal category forum view
	if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
	{
		$check = $dbl->run("SELECT `last_read` FROM `user_forum_read` WHERE `user_id` = ? AND `forum_id` = ?", array($_SESSION['user_id'], $_GET['forum_id']))->fetchOne();
		if ($check)
		{
			$dbl->run("UPDATE `user_forum_read` SET `last_read` = ? WHERE `user_id` = ? AND `forum_id` = ?", array(core::$date, $_SESSION['user_id'], $_GET['forum_id']));
		}
		else
		{
			$dbl->run("INSERT INTO `user_forum_read` SET `last_read` = ?, `user_id` = ?, `forum_id` = ?", array(core::$date, $_SESSION['user_id'], $_GET['forum_id']));
		}
	}

	// paging for pagination
	$page = core::give_page();

	$templating->load('forum_search');
	$templating->block('small');

	$templating->load('viewforum');

	$details = $dbl->run("SELECT `name`, `rss_password` FROM `forums` WHERE forum_id = ?", array($_GET['forum_id']))->fetch();

	$templating->set_previous('title', 'Viewing forum ' . $details['name'], 1);
	$templating->set_previous('meta_description', 'GamingOnLinux forum - Viewing forum ' . $details['name'], 1);

	$templating->block('main_top', 'viewforum');
	$templating->set('forum_name', $details['name']);
	$templating->set('forum_id', (int) $_GET['forum_id']);

	$rss_pass = NULL;
	if ($details['rss_password'] != NULL)
	{
		$rss_pass = '&amp;rss_pass='.$details['rss_password'];
	}
	$templating->set('rss_pass', $rss_pass);

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
			$new_topic = '<li class="green"><a class="forum_button" href="/index.php?module=newtopic">Create Post</a></li>';
			$new_topic_bottom = "<div class=\"fright\"><span class=\"badge blue\"><a class=\"\" href=\"" . $core->config('website_url') . "index.php?module=newtopic&amp;forum_id={$_GET['forum_id']}\">Create Post</a></span></div>";
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
	$total_topics = $dbl->run('SELECT COUNT(t.`topic_id`) FROM `forum_topics` t WHERE t.`forum_id` = ? ' . $blocked_sql, array_merge([$_GET['forum_id']],$blocked_ids))->fetchOne();
	
	$per_page = $core->config('default-comments-per-page');
	if (isset($_SESSION['per-page']) && core::is_number($_SESSION['per-page']))
	{
		$per_page = $_SESSION['per-page'];
	}

	// sort out the pagination link
	$pagination = $core->pagination_link($per_page, $total_topics, "/forum/{$_GET['forum_id']}/", $page);

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
		ORDER BY t.`is_sticky` DESC, t.`last_post_date` DESC LIMIT ?, ' . $per_page, array_merge([$_GET['forum_id']], $blocked_ids, [$core->start]))->fetch_all();

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
			$templating->block('pinned');
		}

		if ($post['is_sticky'] == 0 && $pinned != 0)
		{
			$normal_test++;
			if ($normal_test == 1)
			{
				$templating->block('normal_posts');
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
		$templating->load('login');
		$templating->block('small');
		$templating->set('current_page', core::current_page_url());
		$templating->set('url', $core->config('website_url'));
		
		$twitter_button = '';
		if ($core->config('twitter_login') == 1)
		{	
			$twitter_button = '<a href="'.$core->config('website_url').'index.php?module=login&twitter" class="btn-auth btn-twitter"><span class="btn-icon"><img alt="" src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/network-icons/white/twitter.png" /> </span>Sign in with <b>Twitter</b></a>';
		}
		$templating->set('twitter_button', $twitter_button);
		
		$steam_button = '';
		if ($core->config('steam_login') == 1)
		{
			$steam_button = '<a href="'.$core->config('website_url').'index.php?module=login&steam" class="btn-auth btn-steam"><span class="btn-icon"><img alt="" src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/network-icons/white/steam.png" /> </span>Sign in with <b>Steam</b></a>';
		}
		$templating->set('steam_button', $steam_button);
		
		$google_button = '';
		if ($core->config('google_login') == 1)
		{
			$client_id = $core->config('google_login_public'); 
			$client_secret = $core->config('google_login_secret');
			$redirect_uri = $core->config('website_url') . 'includes/google/login.php';
			require_once ($core->config('path') . 'includes/google/libraries/Google/autoload.php');
			$client = new Google_Client();
			$client->setClientId($client_id);
			$client->setClientSecret($client_secret);
			$client->setRedirectUri($redirect_uri);
			$client->addScope("email");
			$client->addScope("profile");
			$service = new Google_Service_Oauth2($client);
			$authUrl = $client->createAuthUrl();
			
			$google_button = '<a href="'.$authUrl.'" class="btn-auth btn-google"><span class="btn-icon"><img alt="" src="'.$core->config('website_url'). 'templates/' . $core->config('template') .'/images/network-icons/google.svg" /> </span>Sign in with <b>Google</b></a>';
		}
		$templating->set('google_button', $google_button);
	}
}
?>

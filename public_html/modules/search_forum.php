<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Forum Search', 1);
$templating->set_previous('meta_description', 'Search the forum on GamingOnLinux', 1);

// get the forum ids this user is actually allowed to view
$groups_in = str_repeat('?,', count($user->user_groups) - 1) . '?';
$forum_ids = $dbl->run("SELECT p.`forum_id`, f.`name` FROM `forum_permissions` p INNER JOIN `forums` f ON f.forum_id = p.forum_id WHERE `is_category` = 0 AND `can_view` = 1 AND `group_id` IN ($groups_in) GROUP BY forum_id ORDER BY f.name ASC", $user->user_groups)->fetch_all();
if (!$forum_ids || empty($forum_ids))
{
	$_SESSION['message'] = 'no_permission';
	header('Location: /home/');
	die();
}

$templating->load('forum_search');
$templating->block('top');
$templating->set('url', $core->config('website_url'));

$forum_list = '';
foreach ($forum_ids as $forum)
{
	$forum_list .= '<option value="'. $forum['forum_id'] . '">' . $forum['name'] . '</option>';
	$forum_id_list[] = $forum['forum_id'];
}
$templating->set('forum_list_search', $forum_list);

$search_sql = '';
$forum_id = '';
if (isset($_GET['forums']) && $_GET['forums'] != 'all' && in_array($_GET['forums'], $forum_id_list))
{
	if ( (!isset($_GET['forums']) || empty($_GET['forums'])) || (isset($_GET['forums']) && !core::is_number($_GET['forums'])) )
	{
		$_SESSION['message'] = 'no_id';
		$_SESSION['message_extra'] = 'forum';
		header("Location: /index.php?module=search_forum&q=".$_GET['q']);
		die();
	}
	$forum_id = (int) $_GET['forums'];
	$search_sql = ' AND t.`forum_id` IN ('.$forum_id.')';
}
else
{
	$forum_id = 'all';
	$search_sql = ' AND t.`forum_id` IN ('.implode(',',$forum_id_list).')';
}

$user_search = '';
$fsearch_user_id = '';
$user_sql_data = array();
if (isset($_GET['user_id']) && !empty($_GET['user_id']))
{
	if (!core::is_number($_GET['user_id']))
	{
		$_SESSION['message'] = 'no_id';
		$_SESSION['message_extra'] = 'forum';
		header("Location: /index.php?module=search_forum&q=".$_GET['q']);		
	}
	$fsearch_user_id = (int) $_GET['user_id'];
	$user_sql_data[] = (int) $_GET['user_id'];
	$search_sql = ' AND t.`author_id` = ?';

	$get_username = $dbl->run("SELECT `username` FROM `users` WHERE `user_id` = ?", array($fsearch_user_id))->fetchOne();

	$user_search = '<option value="'.$fsearch_user_id.'" selected>'.$get_username.'</option>';
}

$search_text = '';
if (isset($_GET['q']))
{
	$search_text = str_replace("+", ' ', $_GET['q']);
	$search_text = htmlspecialchars($search_text);
}
$templating->set('search_text', $search_text);

$search_array = array(explode(" ", $search_text));
$search_through = '';
foreach ($search_array[0] as $item)
{
	$item = str_replace("%","\%", $item);
	$search_through .= '%'.$item.'%';
}

// pagination
$page = core::give_page();
$per_page = 50;
$page_url = '/index.php?module=search_forum';

if (isset($search_text) && !empty($search_text) && !isset($_GET['single_user_forum_search']))
{
	$templating->set('user_search',$user_search);

	$page_url .= '&amp;q=' . $search_text . '&amp;forums=' . $forum_id . '&amp;';

	// count total
	$total = $dbl->run("SELECT count(*) FROM `forum_topics` t WHERE t.approved = 1 AND t.`topic_title` LIKE ? $search_sql", array_merge([$search_through],$user_sql_data))->fetchOne();

	$last_page = ceil($total/$per_page);
		
	if ($page > $last_page)
	{
		$page = $last_page;
	}

	$pagination = $core->pagination_link($per_page, $total, $page_url, $page);

	// do the search query
	$found_search = $dbl->run("SELECT 
	t.topic_id, 
	t.`topic_title`, 
	t.author_id, 
	t.`creation_date`, 
	u.username,
	f.name,
	f.forum_id
	FROM `forum_topics` t
	INNER JOIN `forums` f on f.forum_id = t.forum_id
	LEFT JOIN `users` u ON t.author_id = u.user_id
	WHERE t.`topic_title` LIKE ? $search_sql AND t.approved = 1
	ORDER BY t.creation_date DESC
	LIMIT $core->start, $per_page", array_merge([$search_through],$user_sql_data))->fetch_all();

	if ($found_search)
	{
		// loop through results
		foreach ($found_search as $found)
		{
			$date = $core->human_date($found['creation_date']);

			$templating->block('row');

			$templating->set('date', $date);
			$templating->set('title', $found['topic_title']);
			$templating->set('topic_id', $found['topic_id']);
			$templating->set('username', "<a href=\"/profiles/{$found['author_id']}\">" . $found['username'] . '</a>');
			$templating->set('forum_name', $found['name']);
			$templating->set('forum_id', $found['forum_id']);
		}
	}
	else
	{
		$core->message('Nothing was found with those search terms.');
	}
}

if (isset($_GET['single_user_forum_search']) && isset($_GET['user_id']))
{
	$templating->set('single_user_search',$user_search);

	$page_url .= '&amp;forums=' . $forum_id . '&amp;user_id=' . $fsearch_user_id . '&amp;single_user_forum_search=1' .'&amp;';

	// count total
	$total = $dbl->run("SELECT count(*) FROM `forum_topics` t WHERE t.approved = 1 $search_sql", $user_sql_data)->fetchOne();
	
	$last_page = ceil($total/$per_page);
		
	if ($page > $last_page)
	{
		$page = $last_page;
	}

	$pagination = $core->pagination_link($per_page, $total, $page_url, $page);

	// do the search query
	$found_search = $dbl->run("SELECT 
	t.topic_id, 
	t.`topic_title`, 
	t.author_id, 
	t.`creation_date`, 
	u.username,
	f.name,
	f.forum_id
	FROM `forum_topics` t
	INNER JOIN `forums` f on f.forum_id = t.forum_id
	LEFT JOIN `users` u ON t.author_id = u.user_id
	WHERE t.approved = 1 $search_sql  
	ORDER BY t.creation_date DESC
	LIMIT $core->start, $per_page", $user_sql_data)->fetch_all();

	if ($found_search)
	{
		// loop through results
		foreach ($found_search as $found)
		{
			$date = $core->human_date($found['creation_date']);

			$templating->block('row');

			$templating->set('date', $date);
			$templating->set('title', $found['topic_title']);
			$templating->set('topic_id', $found['topic_id']);
			$templating->set('username', "<a href=\"/profiles/{$found['author_id']}\">" . $found['username'] . '</a>');
			$templating->set('forum_name', $found['name']);
			$templating->set('forum_id', $found['forum_id']);
		}
	}
	else
	{
		$core->message('Nothing was found with those search terms.');
	}
}

if (isset($found_search) && !empty($found_search))
{
	$templating->block('bottom','forum_search');
	$start_no = $core->start;
	if ($core->start == 0)
	{
		$start_no = 1;
	}
	$templating->set('search_no_start', $start_no);

	$end_no = $core->start + $per_page;
	if ($end_no > $total)
	{
		$end_no = $total;
	}
	$templating->set('end_no', $end_no);

	$templating->set('total', $total);
	$templating->set('pagination', $pagination);
}
?>

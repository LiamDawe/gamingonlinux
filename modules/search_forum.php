<?php
$templating->set_previous('title', 'Forum Search', 1);
$templating->set_previous('meta_description', 'Search the forum on GamingOnLinux', 1);

$templating->load('forum_search');
$templating->block('top');
$templating->set('url', $core->config('website_url'));

$search_sql = '';
if (isset($_GET['forums']) && $_GET['forums'] != 'all')
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

$res = $dbl->run("SELECT `forum_id`, `name` FROM `forums` WHERE `is_category` = 0 ORDER BY `name` ASC")->fetch_all();
$options = '';
foreach ($res as $forum_list)
{
	$options .= '<option value="'.$forum_list['forum_id'].'">'.$forum_list['name'].'</option>';
}
$templating->set('forum_list_search', $options);

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

if (isset($search_text) && !empty($search_text))
{
	// do the search query
	$found_search = $dbl->run("SELECT t.topic_id, t.`topic_title` , t.author_id, t.`creation_date` , u.username
	FROM `forum_topics` t
	LEFT JOIN `users` u ON t.author_id = u.user_id
	WHERE t.`topic_title` LIKE ? $search_sql AND t.approved = 1
	ORDER BY t.creation_date DESC
	LIMIT 0 , 30", array($search_through))->fetch_all();

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
		}
	}
	else
	{
		$core->message('Nothing was found with those search terms.');
	}
}
?>

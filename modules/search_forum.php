<?php
$templating->set_previous('title', 'Forum Search', 1);
$templating->set_previous('meta_description', 'Search for Linux gaming forum topics on GamingOnLinux.com', 1);

$templating->merge('forum_search');
$templating->block('top');
$templating->set('url', core::config('website_url'));

$db->sqlquery("SELECT `forum_id`, `name` FROM `forums` WHERE `is_category` = 0 ORDER BY `name` ASC");
$options = '';
while ($forum_list = $db->fetch())
{
	$options .= '<option value="'.$forum_list['forum_id'].'">'.$forum_list['name'].'</option>';
}
$templating->set('forum_list_search', $options);

$strict_check = '';
if (isset($_GET['strict']))
{
	$strict_check = 'checked';
}

$templating->set('strict_check', $strict_check);

$search_text = '';
if (isset($_GET['q']))
{
	$search_text = str_replace("+", ' ', $_GET['q']);
	$search_text = htmlspecialchars($search_text);
}
$templating->set('search_text', $search_text);

if (isset($search_text) && !empty($search_text))
{
	$search_sql = '';
	if ($_GET['forums'] != 'all')
	{
		if ( (!isset($_GET['forums']) || empty($_GET['forums'])) || (isset($_GET['forums']) && !core::is_number($_GET['forums'])) )
		{
			header("Location: /index.php?module=search_forum&message=no_id&extra=forum");
			die();
		}
		$search_sql = ' AND t.`forum_id` IN ('.$_GET['forums'].')';
	}
	if (!isset($_GET['strict']))
	{
		// do the search query
		$db->sqlquery("SELECT t.topic_id, t.`topic_title` , t.author_id, t.`creation_date` , u.username
		FROM  `forum_topics` t
		LEFT JOIN  `users` u ON t.author_id = u.user_id
		WHERE MATCH (
		t.`topic_title`
		)
		AGAINST (
		? IN BOOLEAN MODE
		) $search_sql
		ORDER BY t.creation_date DESC
		LIMIT 0 , 30", array($search_text));
	}

	else
	{
		$search_text = preg_replace("/\w+/", '+\0*', $search_text);

		// do the search query
		$db->sqlquery("SELECT t.topic_id, t.`topic_title` , t.author_id, t.`creation_date` , u.username
		FROM  `forum_topics` t
		LEFT JOIN  `users` u ON t.author_id = u.user_id
		WHERE MATCH (
		t.`topic_title`
		)
		AGAINST (
		? IN BOOLEAN MODE
		) $search_sql
		ORDER BY t.creation_date DESC
		LIMIT 0 , 30", array($search_text));
	}
	$found_search = $db->fetch_all_rows();
	$total = $db->num_rows();

	if ($total > 0)
	{
		// loop through results
		foreach ($found_search as $found)
		{
			$date = $core->format_date($found['creation_date']);

			$templating->block('row');

			$templating->set('date', $date);
			$templating->set('title', $found['topic_title']);
			$templating->set('topic_id', $found['topic_id']);
			$templating->set('username', "<a href=\"/profiles/{$found['author_id']}\">" . $found['username'] . '</a>');
		}
	}
	else
	{
		header("Location: /index.php?module=search_forum&message=none_found&extra=posts");
		die();
	}
}
?>

<?php
$templating->load('user_search');

if (!isset($_GET['author_id']))
{
	$templating->set_previous('title', 'User Search', 1);
	$templating->set_previous('meta_description', 'Search for users on GamingOnLinux', 1);

	$templating->block('user_search');
	$templating->set('url', $core->config('website_url'));
}

$search_text = '';
if (isset($_GET['username']))
{
	$search_text = str_replace("+", ' ', $_GET['username']);
	$search_text = core::make_safe($search_text);
}
$templating->set('search_text', $search_text);

$search_array = array(explode(" ", $search_text));
$search_through = '';
foreach ($search_array[0] as $item)
{
	$item = str_replace("%","\%", $item);
	$search_through .= '%'.$item.'%';
}

// check there wasn't none found to prevent loops
if (isset($search_text) && !empty($search_text))
{
	// paging for pagination
	$page = core::give_page();
	
	$total = $dbl->run("SELECT COUNT(u.`user_id`) FROM `users` u WHERE u.`username` LIKE ? AND u.`global_search_visible` = 1 AND NOT EXISTS(SELECT `blocked_id` FROM `user_block_list` WHERE `user_id` = u.user_id AND `blocked_id` = 1) ORDER BY u.`username` ASC ", [$search_through])->fetchOne();
	
	// sort out the pagination link
	$pagination = $core->pagination_link(15, $total, "/index.php?module=user_search&username={$_GET['username']}&", $page);
	
	// do the search query
	$user_list = $dbl->run("SELECT u.`user_id`, u.`username` FROM `users` u WHERE u.`username` LIKE ? AND u.`global_search_visible` = 1 AND NOT EXISTS(SELECT `blocked_id` FROM `user_block_list` WHERE `user_id` = u.user_id AND `blocked_id` = 1) ORDER BY u.`username` ASC LIMIT ?, 50", [$search_through, $core->start])->fetch_all();

	if ($user_list)
	{
		// loop through results
		foreach ($user_list as $found_user)
		{
			$templating->block('user_row');
			$templating->set('url', $core->config('website_url'));
			$templating->set('username', $found_user['username']);
			$templating->set('user_id', $found_user['user_id']);
			$templating->set('avatar', $user->sort_avatar($found_user['user_id']));
		}
		$templating->block('bottom');
		$templating->set('pagination', $pagination);
	}
	else
	{
		$core->message('Nothing was found with those search terms.');
	}
}
?>

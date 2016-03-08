<?php
$templating->merge('usercp_modules/usercp_module_topic_subscriptions');

if (!isset($_GET['go']))
{
	// paging for pagination
	if (!isset($_GET['page']))
	{
		$page = 1;
	}

	else if (is_numeric($_GET['page']))
	{
		$page = $_GET['page'];
	}
	
	$templating->block('main_top');

	// count how many there is in total
	$db->sqlquery("SELECT s.user_id, t.`topic_id` FROM `forum_topics` t INNER JOIN `forum_topics_subscriptions` s ON t.topic_id = s.topic_id WHERE s.`user_id` = ?", array($_SESSION['user_id']));
	$total_pages = $db->num_rows();

	// sort out the pagination link
	$pagination = $core->pagination_link(9, $total_pages, "usercp.php?module=topic_subscriptions&", $page);
	
	// get the posts for this forum
	$db->sqlquery("SELECT t.*, s.user_id, u.`username`, u.avatar, u.gravatar_email, u.avatar_gravatar, u.avatar_uploaded, u2.`username` as `username_last`, u2.`user_id` as `user_id_last` FROM `forum_topics` t INNER JOIN `forum_topics_subscriptions` s ON t.topic_id = s.topic_id INNER JOIN `users` u ON t.`author_id` = u.`user_id` LEFT JOIN `users` u2 ON t.`last_post_id` = u2.`user_id` WHERE s.`user_id`= ? ORDER BY t.`last_post_date` DESC LIMIT ?, 9", array($_SESSION['user_id'], $core->start));

	while ($post = $db->fetch())
	{
		$templating->block('post_row');
		// sort out topic icon

		// sort out the avatar
		// either no avatar (gets no avatar from gravatars redirect) or gravatar set
		if (empty($post['avatar']) || $post['avatar_gravatar'] == 1)
		{
			$topic_pip = "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $post['gravatar_email'] ) ) ) . "?d={$config['website_url']}{$config['path']}/templates/default/images/topic_icon.png";
		}
		
		// either uploaded or linked an avatar
		else 
		{
			$topic_pip = $post['avatar'];
			if ($post['avatar_uploaded'] == 1)
			{
				$topic_pip = "/uploads/avatars/{$post['avatar']}";
			}
		}
		
		$templating->set('topic_pip', $topic_pip);
		$templating->set('topic_id', $post['topic_id']);
		$templating->set('post_title', $post['topic_title']);
		$templating->set('author_id', $post['author_id']);
		$templating->set('post_date', $core->format_date($post['creation_date']));
		$templating->set('post_author', $post['username']);
		$templating->set('replies', $post['replys']);
		$templating->set('views', $post['views']);
		
		$username_last = 'No replies!';
		if (!empty($post['username_last']))
		{
			$date = $core->format_date($post['last_post_date']);
			$username_last = "by <a href=\"/profiles/{$post['user_id_last']}\">{$post['username_last']}</a><br />
			on {$date}";
		}
		
		$templating->set('last_post_name', $username_last);
	}

	$templating->block('main_bottom');
	$templating->set('pagination', $pagination);
}

else if (isset($_GET['go']))
{
	if ($_GET['go'] == 'unsubscribe')
	{
		$db->sqlquery("DELETE FROM `forum_topics_subscriptions` WHERE `user_id` = ? AND `topic_id` = ?", array($_SESSION['user_id'], $_GET['topic_id']));

		header("Location: /usercp.php?module=topic_subscriptions");
	}
}
?>

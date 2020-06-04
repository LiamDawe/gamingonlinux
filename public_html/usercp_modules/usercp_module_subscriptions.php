<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Manage subscriptions' . $templating->get('title', 1)  , 1);
$templating->load('usercp_modules/subscriptions');

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

	$templating->block('main_top', 'usercp_modules/subscriptions');

	// count how many there is in total
	$total_subs = $dbl->run("SELECT COUNT(*) FROM ( select article_id from articles_subscriptions WHERE `user_id` = ? union all select topic_id from forum_topics_subscriptions WHERE `user_id` = ? ) x", array($_SESSION['user_id'],$_SESSION['user_id']))->fetchOne();

	// sort out the pagination link
	$pagination = $core->pagination_link(10, $total_subs, "usercp.php?module=subscriptions&", $page);

	// get the subs
	$res_posts = $dbl->run("SELECT 
	`item_id`, 
	`date`,
	`slug`,
	`title`, 
	`type`, 
	`emails`, 
	`author_id`,
	`username`
	FROM ( 
		SELECT 
		s.article_id AS item_id, 
		a.`date`, 
		a.`slug`,
		a.`title`, 
		'Article' AS `type`, 
		s.emails, 
		a.author_id,
		u.username
		FROM `articles_subscriptions` s 
		INNER JOIN `articles` a ON a.article_id = s.article_id
		LEFT JOIN `users` u ON a.author_id = u.user_id
		WHERE s.`user_id` = ?
	UNION ALL 
		SELECT 
		fs.topic_id AS item_id, 
		t.`creation_date` as `date`, 
		NULL as `slug`,
		t.topic_title as `title`, 
		'Forum Topic' AS `type`, 
		fs.emails, 
		t.author_id,
		u.username
		FROM 
		`forum_topics_subscriptions` fs 
		INNER JOIN `forum_topics` t ON fs.`topic_id` = t.`topic_id` 
		LEFT JOIN `users` u ON t.author_id = u.user_id
		WHERE fs.`user_id` = ? ) X 
		ORDER BY `date` 
		DESC LIMIT ?, 10", array($_SESSION['user_id'], $_SESSION['user_id'], $core->start))->fetch_all();

	foreach ($res_posts as $post)
	{
		$templating->block('post_row', 'usercp_modules/subscriptions');

		$link = '';
		if ($post['type'] == 'Article')
		{
			$link = $article_class->article_link(array('date' => $post['date'], 'slug' => $post['slug']));
		}
		if ($post['type'] == 'Forum Topic')
		{
			$link = '/forum/topic/' . $post['item_id'];
		}

		$templating->set('title', '<strong>' . $post['type'] . '</strong>: '. $post['title']);
		$templating->set('item_id', $post['item_id']);
		$templating->set('item_link', $link);
		$templating->set('author_id', $post['author_id']);
		$templating->set('date', $core->human_date($post['date']));
		$templating->set('author', $post['username']);
		$templating->set('type', $post['type']);

		$no_emails = '';
		$get_emails = '';
		if ($post['emails'] == 1)
		{
			$no_emails = '';
			$get_emails = 'selected';
		}
		else if ($post['emails'] == 0)
		{
			$no_emails = 'selected';
			$get_emails = '';
		}
		$templating->set('no_emails', $no_emails);
		$templating->set('get_emails', $get_emails);
	}

	$templating->block('main_bottom', 'usercp_modules/subscriptions');
	$templating->set('pagination', $pagination);
}

else if (isset($_GET['go']))
{
	if ($_GET['go'] == 'unsubscribe')
	{
		if (isset($_GET['all']) && $_GET['all'] == 1)
		{
			if (!isset($_POST['yes']) && !isset($_POST['no']))
			{
				$templating->set_previous('title', 'Unsubscribing from all content', 1);
				$core->yes_no('Are you sure you want to unsubscribe from everything?', url."usercp.php?module=subscriptions&go=unsubscribe&all=1");
			}

			else if (isset($_POST['no']))
			{
				header("Location: /usercp.php?module=subscriptions");
				die();
			}

			else if (isset($_POST['yes']))
			{
				$dbl->run("DELETE FROM `articles_subscriptions` WHERE `user_id` = ?", array($_SESSION['user_id']));
				$dbl->run("DELETE FROM `forum_topics_subscriptions` WHERE `user_id` = ?", array($_SESSION['user_id']));

				$_SESSION['message'] = 'deleted';
				$_SESSION['message_extra'] = 'list of content subscriptions';
				header("Location: /usercp.php?module=subscriptions");
				die();
			}
		}
		else
		{
			if (!isset($_GET['item_id']) || !is_numeric($_GET['item_id']))
			{
				$_SESSION['message'] = 'no_id';
				header("Location: /usercp.php?module=subscriptions");
				die();			
			}

			if ($_POST['type'] == 'Article')
			{
				$dbl->run("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $_POST['item_id']));
			}

			if ($_POST['type'] == 'Forum Topic')
			{
				$dbl->run("DELETE FROM `forum_topics_subscriptions` WHERE `user_id` = ? AND `topic_id` = ?", array($_SESSION['user_id'], $_POST['item_id']));
			}
		
			$_SESSION['message'] = 'deleted';
			$_SESSION['message_extra'] = 'subscription';
			header("Location: /usercp.php?module=subscriptions");
			die();
		}
	}

	if ($_GET['go'] == 'update')
	{
		if (!isset($_POST['item_id']) || !is_numeric($_POST['item_id']))
		{
			$_SESSION['message'] = 'no_id';
			header("Location: /usercp.php?module=subscriptions");
			die();
		}
		else
		{
			$emails = 0;
			if ($_POST['subscribe-type'] == 'sub-only')
			{
				$emails = 0;
			}
			else if ($_POST['subscribe-type'] == 'sub-emails')
			{
				$emails = 1;
			}

			if ($_POST['type'] == 'Article')
			{
				$dbl->run("UPDATE `articles_subscriptions` SET `emails` = $emails WHERE `article_id` = ? AND `user_id` = ?", array($_POST['item_id'], $_SESSION['user_id']));
			}

			if ($_POST['type'] == 'Forum Topic')
			{
				$dbl->run("UPDATE `forum_topics_subscriptions` SET `emails` = $emails WHERE `topic_id` = ? AND `user_id` = ?", array($_POST['item_id'], $_SESSION['user_id']));
			}

			$_SESSION['message'] = 'saved';
			$_SESSION['message_extra'] = 'subscription';
			header("Location: /usercp.php?module=subscriptions");
			die();
		}
	}
}
?>

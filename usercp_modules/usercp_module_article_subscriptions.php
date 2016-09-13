<?php
$templating->merge('usercp_modules/usercp_module_article_subscriptions');

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

	if (isset($_GET['message']))
	{
		if ($_GET['message'] == 'done')
		{
			$core->message('Subscription removed!');
		}
	}

	$templating->block('main_top', 'usercp_modules/usercp_module_article_subscriptions');

	// count how many there is in total
	$db->sqlquery("SELECT s.user_id, a.`article_id` FROM `articles` a INNER JOIN `articles_subscriptions` s ON a.article_id = s.article_id WHERE s.`user_id` = ?", array($_SESSION['user_id']));
	$total_pages = $db->num_rows();

	// sort out the pagination link
	$pagination = $core->pagination_link(9, $total_pages, "usercp.php?module=article_subscriptions&", $page);

	// get the articles
	$db->sqlquery("SELECT a.article_id, a.title, a.date, s.user_id, u.`username` FROM `articles` a INNER JOIN `articles_subscriptions` s ON a.article_id = s.article_id INNER JOIN `users` u ON a.`author_id` = u.`user_id` WHERE s.`user_id`= ? ORDER BY a.`date` DESC LIMIT ?, 9", array($_SESSION['user_id'], $core->start));

	while ($post = $db->fetch())
	{
		$templating->block('post_row', 'usercp_modules/usercp_module_article_subscriptions');

		$templating->set('title', $post['title']);
		$templating->set('article_id', $post['article_id']);
		$templating->set('article_link', $core->nice_title($post['title']) . '.' . $post['article_id']);
		$templating->set('author_id', $post['user_id']);
		$templating->set('date', $core->format_date($post['date']));
		$templating->set('author', $post['username']);
	}

	$templating->block('main_bottom', 'usercp_modules/usercp_module_article_subscriptions');
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
				$templating->set_previous('title', 'Unsubscribing from all article comments', 1);
				$core->yes_no('Are you sure you want to unsubscribe from all article comments?', url."usercp.php?module=article_subscriptions&go=unsubscribe&all=1");
			}

			else if (isset($_POST['no']))
			{
				header("Location: ".url."/usercp.php?module=article_subscriptions");
			}

			else if (isset($_POST['yes']))
			{
				$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `user_id` = ?", array($_SESSION['user_id']));
				header("Location: /usercp.php?module=article_subscriptions&message=done");
			}
		}
		else
		{
			$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $_GET['article_id']));
			header("Location: /usercp.php?module=article_subscriptions&message=done");
		}
	}
}
?>

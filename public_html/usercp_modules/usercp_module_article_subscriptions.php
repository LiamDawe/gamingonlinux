<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Manage article subscriptions' . $templating->get('title', 1)  , 1);
$templating->load('usercp_modules/usercp_module_article_subscriptions');

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
		if ($_GET['message'] == 'updated')
		{
			$core->message('Subscription updated!');
		}
	}

	$templating->block('main_top', 'usercp_modules/usercp_module_article_subscriptions');

	// count how many there is in total
	$total_subs = $dbl->run("SELECT COUNT(a.`article_id`) FROM `articles` a INNER JOIN `articles_subscriptions` s ON a.article_id = s.article_id WHERE s.`user_id` = ?", array($_SESSION['user_id']))->fetchOne();

	// sort out the pagination link
	$pagination = $core->pagination_link(10, $total_subs, "usercp.php?module=article_subscriptions&", $page);

	// get the articles
	$res_posts = $dbl->run("SELECT a.article_id, a.title, a.date, s.user_id, s.emails, u.`username` FROM `articles` a INNER JOIN `articles_subscriptions` s ON a.article_id = s.article_id LEFT JOIN `users` u ON a.`author_id` = u.`user_id` WHERE s.`user_id`= ? ORDER BY a.`date` DESC LIMIT ?, 10", array($_SESSION['user_id'], $core->start))->fetch_all();

	foreach ($res_posts as $post)
	{
		$templating->block('post_row', 'usercp_modules/usercp_module_article_subscriptions');

		$templating->set('title', $post['title']);
		$templating->set('article_id', $post['article_id']);
		$templating->set('article_link', $article_class->get_link($post['article_id'], $post['title']));
		$templating->set('author_id', $post['user_id']);
		$templating->set('date', $core->human_date($post['date']));
		$templating->set('author', $post['username']);

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
				$dbl->run("DELETE FROM `articles_subscriptions` WHERE `user_id` = ?", array($_SESSION['user_id']));
				header("Location: /usercp.php?module=article_subscriptions&message=done");
			}
		}
		else
		{
			if (!isset($_GET['article_id']) || !is_numeric($_GET['article_id']))
			{
				$_SESSION['message'] = 'no_id';
				$_SESSION['message'] = 'article';
				header("Location: /usercp.php?module=article_subscriptions");
				die();			
			}

			$dbl->run("DELETE FROM `articles_subscriptions` WHERE `user_id` = ? AND `article_id` = ?", array($_SESSION['user_id'], $_GET['article_id']));
			header("Location: /usercp.php?module=article_subscriptions&message=done");
		}
	}

	if ($_GET['go'] == 'update')
	{
		if (!isset($_POST['article_id']) || !is_numeric($_POST['article_id']))
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message'] = 'article';
			header("Location: /usercp.php?module=article_subscriptions");
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
			$dbl->run("UPDATE `articles_subscriptions` SET `emails` = $emails WHERE `article_id` = ? AND `user_id` = ?", array($_POST['article_id'], $_SESSION['user_id']));

			header("Location: /usercp.php?module=article_subscriptions&message=updated");
		}
	}
}
?>

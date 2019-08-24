<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: admin comment reports.');
}

$templating->load('admin_modules/comment_reports');

$templating->set_previous('title', 'Article comments' . $templating->get('title', 1)  , 1);
if (!isset($_GET['ip_id']))
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

	$templating->block('comments_top', 'admin_modules/comment_reports');

	// count how many there is in total
	$total_pages = $dbl->run("SELECT COUNT(`comment_id`) FROM `articles_comments` WHERE `spam` = 1")->fetchOne();

	/* get any spam reported comments in a paginated list here */
	$pagination = $core->pagination_link(9, $total_pages, "admin.php?module=comment_reports", $page);

	$comments_res = $dbl->run("SELECT a.*, t.title, u.username, u.user_group, u.`avatar`, u.`avatar_uploaded`, u.`avatar_gallery`, u.register_date, u2.username as reported_by_username FROM `articles_comments` a INNER JOIN `articles` t ON a.article_id = t.article_id LEFT JOIN `users` u ON a.author_id = u.user_id LEFT JOIN `users` u2 on a.spam_report_by = u2.user_id WHERE a.spam = 1 ORDER BY a.`comment_id` ASC LIMIT ?, 9", array($core->start))->fetch_all();
	if ($comments_res)
	{
		foreach ($comments_res as $comments)
		{
			// make date human readable
			$date = $core->human_date($comments['time_posted']);

			if ($comments['author_id'] == 0)
			{
				$username = $comments['guest_username'];
			}
			else
			{
				$username = "<a href=\"/profiles/{$comments['author_id']}\">{$comments['username']}</a>";
			}

			// sort out the avatar
			$comment_avatar = $user->sort_avatar($comments);

			$templating->block('article_comments', 'admin_modules/comment_reports');
			$templating->set('user_id', $comments['author_id']);
			$templating->set('username', $username);
			$templating->set('comment_avatar', $comment_avatar);
			$templating->set('date', $date);
			$templating->set('text', $bbcode->parse_bbcode($comments['comment_text']));
			$templating->set('reported_by', "<a href=\"/profiles/{$comments['spam_report_by']}\">{$comments['reported_by_username']}</a>");
			$templating->set('comment_id', $comments['comment_id']);
			$templating->set('article_title', $comments['title']);
			$templating->set('article_link', core::nice_title($comments['title']) . '.' . $comments['article_id']);
			$badges = user::user_badges($comments, 1);
			$templating->set('badges', implode(' ', $badges));
		}
	}

	$templating->block('comment_reports_bottom', 'admin_modules/comment_reports');
	$templating->set('pagination', $pagination);
}
else
{
	$core->message('Nothing to display! There are no reported comments.');
}

if (isset($_POST['act']) && $_POST['act'] == 'delete_spam_report')
{
	if (!is_numeric($_GET['comment_id']))
	{
		$core->message("Not a correct id!", 1);
	}

	else
	{
		// update existing notification
		$core->update_admin_note(array("type" => 'reported_comment', 'data' => $_GET['comment_id']));

		// note who did it
		$core->new_admin_note(array('completed' => 1, 'content' => ' deleted a comment report.'));

		$dbl->run("UPDATE `articles_comments` SET `spam` = 0 WHERE `comment_id` = ?", array($_GET['comment_id']));

		$_SESSION['message'] = 'deleted';
		$_SESSION['message_extra'] = 'comment report';
		header("Location: /admin.php?module=comment_reports");
	}
}
?>

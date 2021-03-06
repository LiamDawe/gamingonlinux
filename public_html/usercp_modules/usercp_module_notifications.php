<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Notifications manager' . $templating->get('title', 1)  , 1);
$templating->load('usercp_modules/notifications');

$notification_types = [
'quoted' => 			['text' => 'quoted your post on'],
'admin_comment' => 		['text' => 'left a message in'],
'editor_comment' => 	['text' => 'left a message in'],
'editor_plan' => 		['text' => 'added a new article plan in'],
'article_comment' => 	['text' => 'replied to'],
'forum_comment' => 		['text' => 'replied to'],
'liked' =>				['text' => 'liked your comment on'],
'liked_forum_topic' =>  ['text' => 'liked your forum topic titled'],
'liked_forum_reply' =>  ['text' => 'liked your forum post in the topic titled'],
'wishlist_sale' => 		['text' => 'has been detected as going on sale!'],
'update_pc_info' => 	['text' => 'hasn\'t been updated in a few months - be sure to keep it up to date!']
];

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

	$templating->block('top', 'usercp_modules/notifications');

	$pagination = '';

	// count how many there is in total
	$total_notifications = $dbl->run("SELECT COUNT(`id`) FROM `user_notifications` WHERE `owner_id` = ? ORDER BY `seen`, `last_date`", array($_SESSION['user_id']))->fetchOne();

	if ($total_notifications > 0)
	{
		$pagination = $core->pagination_link(15, $total_notifications, "usercp.php?module=notifications&", $page);

		$unread_array = array();
		$read_array = array();
		// show the notifications here
		$res_list = $dbl->run("SELECT
		n.`id`,
		n.`last_date`,
		n.`article_id`,
		n.`comment_id`,
		n.`forum_topic_id`,
		n.`forum_reply_id`,
		n.`sale_game_id`,
		n.`seen`,
		n.`total`,
		n.`type`,
		u.`user_id`,
		u.`username`,
		u.`avatar_gallery`,
		u.`avatar`,
		u.`avatar_uploaded`,
		u.`profile_address`,
		a.`title`,
		ft.`topic_title`,
		c.`name`
		FROM
			`user_notifications` n
		LEFT JOIN `users` u ON u.`user_id` = n.`notifier_id`
		LEFT JOIN `articles` a ON n.`article_id` = a.`article_id`
		LEFT JOIN `forum_topics` ft ON n.`forum_topic_id` = ft.`topic_id`
		LEFT JOIN `calendar` c ON n.`sale_game_id` = c.id
		WHERE n.`owner_id` = ?
		ORDER BY n.`seen`, n.`last_date`
		DESC LIMIT ?, 15", array($_SESSION['user_id'], $core->start))->fetch_all();
		foreach ($res_list as $note_list)
		{
			if ($note_list['seen'] == 0)
			{
				$icon = 'envelope';
			}
			else if ($note_list['seen'] == 1)
			{
				$icon = 'envelope-open';
			}

			if ($note_list['type'] != 'wishlist_sale' && $note_list['type'] != 'update_pc_info')
			{
				if (!empty($note_list['username']))
				{
					$username = $note_list['username'];
				}
				else
				{
					$username = 'Guest';
				}

				$avatar = $user->sort_avatar($note_list);

				$additional_comments = '';
				if ($note_list['total'] > 1)
				{
					$total = $note_list['total'] - 1;
					$additional_comments = ' plus ' . $total . ' more';
				}
				else if ($note_list['total'] == 1 || $note_list['total'] == 0)
				{
					$additional_comments = '';
				}

				// sort the actual link to the content
				$link = '';
				if ($note_list['type'] == 'quoted' || $note_list['type'] == 'article_comment' || $note_list['type'] == 'liked' || $note_list['type'] == 'forum_comment')
				{
					if (isset($note_list['article_id']) && !empty($note_list['article_id']))
					{
						$link = '/index.php?module=articles_full&amp;aid=' . $note_list['article_id'] . '&amp;comment_id=' . $note_list['comment_id'] . '&amp;clear_note=' . $note_list['id'];
						$title = $note_list['title'];
					}						

					if (isset($note_list['forum_topic_id']) && !empty($note_list['forum_topic_id']))
					{
						$link = '/forum/topic/'.$note_list['forum_topic_id'].'/post_id='.$note_list['forum_reply_id'].'/clear_note='.$note_list['id'];
						$title = $note_list['topic_title'];
					}

				}
				if ($note_list['type'] == 'liked_forum_topic')
				{
					$link = '/forum/topic/'.$note_list['forum_topic_id'] . '/clear_note='.$note_list['id'];
					$title = $note_list['topic_title'];
				}
				if ($note_list['type'] == 'liked_forum_reply')
				{
					$link = '/forum/topic/'.$note_list['forum_topic_id'] . '/post_id=' . $note_list['forum_reply_id'] . '/clear_note='.$note_list['id'];
					$title = $note_list['topic_title'];
				}
				if ($note_list['type'] == 'admin_comment' || $note_list['type'] == 'editor_comment' || $note_list['type'] == 'editor_plan')
				{
					$link = '/admin.php?wipe_note=' . $note_list['id'];
					$title = 'the admin area';
				}

				$profile_link = '/profiles/' . $note_list['user_id'];
				if (isset($note_list['profile_address']) && !empty($note_list['profile_address']))
				{
					$profile_link = '/profiles/' . $note_list['profile_address'];
				}

				$note_row = $templating->block_store('plain_row', 'usercp_modules/notifications');

				$note_row = $templating->store_replace($note_row, array(
					'id' => $note_list['id'],
					'icon' => $icon,
					'link' => $link,
					'avatar' => $avatar,
					'username' => $username,
					'profile_link' => $profile_link,
					'action_text' => $notification_types[$note_list['type']]['text'],
					'title' => $title,
					'additional_comments' => $additional_comments,
					'this_template' => $core->config('website_url') . 'templates/' . $core->config('template')));
			}

			else if ($note_list['type'] == 'wishlist_sale')
			{
				$note_row = $templating->block_store('system_row', 'usercp_modules/notifications');
				$link = '/sales.php?game_id='.$note_list['sale_game_id'].'&amp;clear_note=' . $note_list['id'];
				$title = $note_list['name'];

				$note_row = $templating->store_replace($note_row, array(
					'id' => $note_list['id'],
					'icon' => $icon,
					'link' => $link,
					'action_text' => $notification_types[$note_list['type']]['text'],
					'title' => $title,
					'this_template' => $core->config('website_url') . 'templates/' . $core->config('template')));
			}
			else if ($note_list['type'] == 'update_pc_info')
			{
				$note_row = $templating->block_store('system_row', 'usercp_modules/notifications');
				$link = '/usercp.php?module=pcinfo&amp;clear_note=' . $note_list['id'];;
				$title = 'Your PC information';

				$note_row = $templating->store_replace($note_row, array(
					'id' => $note_list['id'],
					'icon' => $icon,
					'link' => $link,
					'action_text' => $notification_types[$note_list['type']]['text'],
					'title' => $title,
					'this_template' => $core->config('website_url') . 'templates/' . $core->config('template')));				
			}

			if ($note_list['seen'] == 0)
			{
				$unread_array[] = $note_row;
			}
			else if ($note_list['seen'] == 1)
			{
				$read_array[] = $note_row;
			}
		}

		if (!empty($unread_array))
		{
			$templating->block('unread', 'usercp_modules/notifications');
			$templating->set('unread_list', implode('', $unread_array));
		}

		if (!empty($read_array))
		{
			$templating->block('read', 'usercp_modules/notifications');
			$templating->set('read_list', implode('', $read_array));
		}
	}
	else
	{
		$templating->block('none', 'usercp_modules/notifications');
	}

	$templating->block('bottom', 'usercp_modules/notifications');

	// sort out the pagination link
	$templating->set('pagination', $pagination);
}

else if (isset($_GET['go']))
{
	if ($_GET['go'] == 'mark_all_read')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$templating->set_previous('title', 'Clear all notifications', 1);
			$core->yes_no('Are you sure you want to clear all notifications? This cannot be undone!', url."usercp.php?module=notifications&go=mark_all_read");
		}

		else if (isset($_POST['no']))
		{
			header("Location: /usercp.php?module=notifications");
		}

		else if (isset($_POST['yes']))
		{
			$dbl->run("UPDATE `user_notifications` SET `seen` = 1, `seen_date` = ? WHERE `owner_id` = ?", array(core::$date, $_SESSION['user_id']));
			$_SESSION['message'] = 'mark_all_read';
			header("Location: /usercp.php?module=notifications");
		}
	}

	if ($_GET['go'] == 'remove_read')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$templating->set_previous('title', 'Remove all read notifications', 1);
			$core->yes_no('Are you sure you want to remove all read notifications? This cannot be undone!', url."usercp.php?module=notifications&go=remove_read");
		}

		else if (isset($_POST['no']))
		{
			header("Location: /usercp.php?module=notifications");
		}

		else if (isset($_POST['yes']))
		{
			$dbl->run("DELETE FROM `user_notifications` WHERE `seen` = 1 AND `owner_id` = ?", array($_SESSION['user_id']));
			$_SESSION['message'] = 'removed_read';
			header("Location: /usercp.php?module=notifications");
		}
	}

	if ($_GET['go'] == 'remove_all')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$templating->set_previous('title', 'Remove all notifications', 1);
			$core->yes_no('Are you sure you want to remove all notifications (unread and read)? This cannot be undone!', url."usercp.php?module=notifications&go=remove_all");
		}

		else if (isset($_POST['no']))
		{
			header("Location: /usercp.php?module=notifications");
		}

		else if (isset($_POST['yes']))
		{
			$dbl->run("DELETE FROM `user_notifications` WHERE `owner_id` = ?", array($_SESSION['user_id']));
			$_SESSION['message'] = 'removed_all';
			header("Location: /usercp.php?module=notifications");
		}
	}
}
?>

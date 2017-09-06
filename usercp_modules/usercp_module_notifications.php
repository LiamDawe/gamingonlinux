<?php
$templating->set_previous('title', 'Notifications manager' . $templating->get('title', 1)  , 1);
$templating->load('usercp_modules/notifications');

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
		if ($_GET['message'] == 'all_clear')
		{
			$core->message('All notifications cleared!');
		}
		if ($_GET['message'] == 'removed_read')
		{
			$core->message('All read notifications removed!');
		}
		if ($_GET['message'] == 'removed_all')
		{
			$core->message('All read notifications removed!');
		}
	}

	$templating->block('top', 'usercp_modules/notifications');

	$pagination = '';
	$user_comment_alerts = $user->get('display_comment_alerts', $_SESSION['user_id']);
	if ($user_comment_alerts == 1)
	{
		// count how many there is in total
		$db->sqlquery("SELECT `id` FROM `user_notifications` WHERE `owner_id` = ? ORDER BY `seen`, `date`", array($_SESSION['user_id']));
		$total_notifications = $db->num_rows();

		if ($total_notifications > 0)
		{
			$pagination = $core->pagination_link(15, $total_notifications, "usercp.php?module=notifications&", $page);

			$unread_array = array();
			$read_array = array();
			// show the notifications here
			$db->sqlquery("SELECT n.`id`, n.`date`, n.`article_id`, n.`comment_id`, n.`seen`, n.is_like, n.is_quote, n.total, n.`type`, u.user_id, u.username, u.avatar_gravatar, u.gravatar_email, u.avatar_gallery, u.avatar, u.avatar_uploaded, a.title FROM `user_notifications` n LEFT JOIN `users` u ON u.user_id = n.notifier_id LEFT JOIN `articles` a ON n.article_id = a.article_id WHERE n.`owner_id` = ? ORDER BY n.seen, n.date DESC LIMIT ?, 15", array($_SESSION['user_id'], $core->start));
			while ($note_list = $db->fetch())
			{
				$additional_comments = '';
				if ($note_list['type'] != NULL)
				{
					if ($note_list['type'] == 'admin_comment' || $note_list['type'] == 'editor_comment')
					{
						$note_row = $templating->block_store('admin_area_comment', 'usercp_modules/notifications');
						if ($note_list['total'] > 1)
						{
							$total = $note_list['total'] - 1;
							$additional_comments = ' plus ' . $total . ' more comments';
						}
						else if ($note_list['total'] == 1)
						{
							$additional_comments = '';
						}
						$note_row = $templating->store_replace($note_row, array('additional_comments' => $additional_comments));
					}
					if ($note_list['type'] == 'editor_plan')
					{
						$note_row = $templating->block_store('editor_plan', 'usercp_modules/notifications');
						if ($note_list['total'] > 1)
						{
							$total = $note_list['total'] - 1;
							$additional_comments = ' plus ' . $total . ' more plans';
						}
						else if ($note_list['total'] == 1)
						{
							$additional_comments = '';
						}
						$note_row = $templating->store_replace($note_row, array('additional_comments' => $additional_comments));
					}
				}
				if ($note_list['is_like'] == 0 && $note_list['is_quote'] == 0 && $note_list['type'] == NULL)
				{
					$note_row = $templating->block_store('row', 'usercp_modules/notifications');
					if ($note_list['total'] > 1)
					{
						$total = $note_list['total'] - 1;
						$additional_comments = ' plus ' . $total . ' more comments';
					}
					else if ($note_list['total'] == 1)
					{
						$additional_comments = '';
					}
					$note_row = $templating->store_replace($note_row, array('additional_comments' => $additional_comments));
				}
				else if ($note_list['is_quote'] == 1)
				{
					$note_row = $templating->block_store('quoted_row', 'usercp_modules/notifications');
				}
				else if ($note_list['is_like'] == 1)
				{
					$note_row = $templating->block_store('liked_row', 'usercp_modules/notifications');
					if ($note_list['total'] > 1)
					{
						$total = $note_list['total'] - 1;
						$additional_likes = ' and ' . $total . ' others';
					}
					else if ($note_list['total'] == 1)
					{
						$additional_likes = '';
					}
					$note_row = $templating->store_replace($note_row, array('additional_likes' => $additional_likes, 'this_template' => $core->config('website_url') . 'templates/' . $core->config('template')));
				}

				if ($note_list['seen'] == 0)
				{
					$icon = 'envelope';
				}
				else if ($note_list['seen'] == 1)
				{
					$icon = 'envelope-open';
				}

				if ($core->config('pretty_urls') == 1)
				{
					$profile_link = '/profiles/' . $note_list['user_id'];
				}
				else
				{
					$profile_link = '/index.php?module=profile&user_id=' . $note_list['user_id'];
				}

				if (!empty($note_list['username']))
				{
					$username = $note_list['username'];
				}
				else
				{
					$username = 'Guest';
				}

				$avatar = $user->sort_avatar($note_list['user_id']);

				$link = '/index.php?module=articles_full&amp;aid=' . $note_list['article_id'] . '&amp;comment_id=' . $note_list['comment_id'] . '&amp;clear_note=' . $note_list['id'];

				$note_row = $templating->store_replace($note_row, array('id' => $note_list['id'], 'icon' => $icon, 'title' => $note_list['title'], 'link' => $link, 'avatar' => $avatar, 'username' => $username, 'profile_link' => $profile_link, 'this_template' => $core->config('website_url') . 'templates/' . $core->config('template')));

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
}

else if (isset($_GET['go']))
{
	if ($_GET['go'] == 'clear_all')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$templating->set_previous('title', 'Clear all notifications', 1);
			$core->yes_no('Are you sure you want to clear all notifications? This cannot be undone!', url."usercp.php?module=notifications&go=clear_all");
		}

		else if (isset($_POST['no']))
		{
			header("Location: /usercp.php?module=notifications");
		}

		else if (isset($_POST['yes']))
		{
			$db->sqlquery("UPDATE `user_notifications` SET `seen` = 1, `seen_date` = ? WHERE `owner_id` = ?", array(core::$date, $_SESSION['user_id']));
			header("Location: /usercp.php?module=notifications&message=all_clear");
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
			$db->sqlquery("DELETE FROM `user_notifications` WHERE `seen` = 1 AND `owner_id` = ?", array($_SESSION['user_id']));
			header("Location: /usercp.php?module=notifications&message=removed_read");
		}
	}

	if ($_GET['go'] == 'remove_all')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$templating->set_previous('title', 'Remove all read notifications', 1);
			$core->yes_no('Are you sure you want to remove all notifications (unread and read)? This cannot be undone!', url."usercp.php?module=notifications&go=remove_all");
		}

		else if (isset($_POST['no']))
		{
			header("Location: /usercp.php?module=notifications");
		}

		else if (isset($_POST['yes']))
		{
			$db->sqlquery("DELETE FROM `user_notifications` WHERE `owner_id` = ?", array($_SESSION['user_id']));
			header("Location: /usercp.php?module=notifications&message=removed_all");
		}
	}
}
?>

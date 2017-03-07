<?php
if ($user->check_group([1,2]) == false)
{
	$core->message("You need to be an editor or an admin to access this section!", NULL, 1);
}
else
{
	$templating->merge('admin_modules/users');

	if (isset($_GET['view']) && !isset($_POST['act']))
	{
		if ($_GET['view'] == 'search')
		{
			$templating->block('search','admin_modules/users');

			$username = '';
			$email = '';
			if (isset($_POST['search']))
			{
				if (isset($_POST['username']))
				{
					$username = trim($_POST['username']);
				}

				if (isset($_POST['email']))
				{
					$email = trim($_POST['email']);
				}

				if (!empty($username) || !empty($email))
				{
					if (isset($username) && !empty($username))
					{
						$db->sqlquery("SELECT `user_id`, `username`, `banned`,`email` FROM `users` WHERE `username` LIKE ?", array('%'.$username.'%'));
					}
					if (isset($email) && !empty($email))
					{
						$db->sqlquery("SELECT `user_id`, `username`, `banned`,`email` FROM `users` WHERE `email` LIKE ?", array('%'.$email.'%'));
					}
					$templating->block('search_row_top', 'admin_modules/users');
					while ($search = $db->fetch())
					{
						$templating->block('search_row','admin_modules/users');
						$templating->set('username', $search['username']);
						$templating->set('user_id', $search['user_id']);
						$templating->set('email', $search['email']);
					}
				}
			}
		}

		if ($_GET['view'] == 'premium')
		{
			$db->sqlquery("SELECT `user_id`, `username` FROM `users` WHERE `user_group` IN (6,7) OR `secondary_user_group` IN (6,7) AND user_group NOT IN (1,2) ORDER BY `premium-ends-date` ASC");
			$templating->block('premium_row_top');
			while ($search = $db->fetch())
			{
				$end_date = $core->normal_date($search['premium-ends-date']);
				$templating->block('premium_row','admin_modules/users');
				$templating->set('username', $search['username']);
				$templating->set('user_id', $search['user_id']);
			}
		}

		if ($_GET['view'] == 'edituser')
		{
			if (!isset($_GET['user_id']) || isset($_GET['user_id']) && empty($_GET['user_id']))
			{
				header("Location: admin.php?module=users&view=search&message=no_id&extra=user");
			}
			else
			{
				$templating->block('search','admin_modules/users');

				$db->sqlquery("SELECT * FROM `users` WHERE `user_id` = ?", array($_GET['user_id']));
				$user_info = $db->fetch();

				$templating->block('edituser', 'admin_modules/users');

				if (core::config('pretty_urls') == 1)
				{
					$profile_link = '/profiles/' . $user_info['user_id'];
				}
				else
				{
					$profile_link = '/index.php?module=profile&user_id='. $user_info['user_id'];
				}
				$templating->set('profile_link', $profile_link);

				$templating->set('user_id', $user_info['user_id']);
				$templating->set('username', $user_info['username']);
				$templating->set('email', $user_info['email']);
				$templating->set('website', $user_info['website']);
				$templating->set('bio', $user_info['article_bio']);
				$admin_only = '';
				if ($user->check_group(1) == true)
				{
					$groups = '';
					$none_selected = '';
					$db->sqlquery("SELECT `group_id`, `group_name` FROM `user_groups` ORDER BY `group_id` ASC");
					while ($group_sql = $db->fetch())
					{
						if ($user_info['secondary_user_group'] == 0)
						{
							$none_selected = 'selected';
						}

						if ($group_sql['group_id'] == $user_info['user_group'])
						{
							$groups .= "<option value=\"{$group_sql['group_id']}\" selected>{$group_sql['group_name']}</option>";
						}

						else
						{
							$groups .= "<option value=\"{$group_sql['group_id']}\">{$group_sql['group_name']}</option>";
						}
					}
					$groups .= '<option value="0" '.$none_selected.'>None</option>';

					$sgroups = '';
					$snone_selected = '';
					$db->sqlquery("SELECT `group_id`, `group_name` FROM `user_groups` ORDER BY `group_id` ASC");
					while ($sgroup_sql = $db->fetch())
					{
						if ($user_info['secondary_user_group'] == 0)
						{
							$snone_selected = 'selected';
						}

						if ($sgroup_sql['group_id'] == $user_info['secondary_user_group'])
						{
							$sgroups .= "<option value=\"{$sgroup_sql['group_id']}\" selected>{$sgroup_sql['group_name']}</option>";
						}

						else
						{
							$sgroups .= "<option value=\"{$sgroup_sql['group_id']}\">{$sgroup_sql['group_name']}</option>";
						}
					}
					$sgroups .= '<option value="0" '.$snone_selected.'>None</option>';

					$admin_only = "User Group: <select name=\"user_group\">{$groups}</select><br />Secondary User Group: <select name=\"secondary_user_group\">{$sgroups}</select><br />";

					$developer_check = '';
					if ($user_info['game_developer'] == 1)
					{
						$developer_check = 'checked';
					}

					$admin_only .= '<label><input type="checkbox" name="game_developer" '.$developer_check.'/>Game developer?</label>';

				}
				$templating->set('admin_only', $admin_only);
				// sort out the avatar
				// either no avatar (gets no avatar from gravatars redirect) or gravatar set
				if (empty($user_info['avatar']) || $user_info['avatar_gravatar'] == 1)
				{
					$comment_avatar = "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $user_info['gravatar_email'] ) ) ) . "?d=http://www.gamingonlinux.com/uploads/avatars/no_avatar.png";
				}

				// either uploaded or linked an avatar
				else
				{
					$comment_avatar = $user_info['avatar'];
					if ($user_info['avatar_uploaded'] == 1)
					{
						$comment_avatar = "/uploads/avatars/{$user_info['avatar']}";
					}
				}
				$templating->set('avatar', $comment_avatar);

				if ($user_info['banned'] == 1)
				{
					$ban_button = '<button type="submit" name="act" value="unban">UnBan User</button>';
					$templating->set('delete_content_button', "<button type=\"submit\" name=\"act\" value=\"delete_user_content\">Delete user content</button>");
				}

				else
				{
					$ban_button = '<button type="submit" name="act" value="ban">Ban User</button>';
				}

				$templating->set('ban_button', $ban_button);

				$db->sqlquery("SELECT `notes` FROM `admin_user_notes` WHERE `user_id` = ?", array($_GET['user_id']));
				$grab_notes = $db->fetch();

				$templating->set('admin_notes', $grab_notes['notes']);
			}
		}

		if ($_GET['view'] == 'ipbanmanage')
		{
			$templating->block('ipban');
			
			$db->sqlquery("SELECT `id`, `ip` FROM `ipbans` ORDER BY `id` DESC");
			$total = $db->num_rows();
			if ($total > 0)
			{
				while ($ip = $db->fetch())
				{
					$templating->block('iprow');
					$templating->set('ip', $ip['ip']);
					$templating->set('id', $ip['id']);
				}
			}
			else
			{
				$core->message('No IP bans are currently in place!');
			}
		}
	}

	else if (isset($_POST['act']))
	{
		if ($_POST['act'] == 'edituser')
		{
			$username = trim($_POST['username']);
			$email = trim($_POST['email']);

			$empty_check = core::mempty(compact('username', 'email'));
			if ($empty_check !== true)
			{
				$_SESSION['message'] = 'empty';
				$_SESSION['message_extra'] = $empty_check;
				header("Location: admin.php?module=users&view=edituser&user_id={$_GET['user_id']}");
			}

			else
			{
				$expires = 0;
				if (isset($_POST['expires']))
				{
					$expires = strtotime(gmdate($_POST['expires']));
				}

				$dev_check = 0;
				if (isset($_POST['game_developer']))
				{
					$dev_check = 1;
				}

				$db->sqlquery("UPDATE `users` SET `username` = ?, `email` = ?, `article_bio` = ?, `website` = ? WHERE `user_id` = ?", array($_POST['username'], $_POST['email'], $_POST['article_bio'], $_POST['website'], $_GET['user_id']));

				if ($user->check_group(1) == true)
				{
					$db->sqlquery("UPDATE `users` SET `user_group` = ?, `secondary_user_group` = ?, `game_developer` = ? WHERE `user_id` = ?", array($_POST['user_group'], $_POST['secondary_user_group'], $dev_check, $_GET['user_id']));
				}

				// make sure they have a row for notes, if not add a new row otherwise edit
				$db->sqlquery("SELECT `user_id` FROM `admin_user_notes` WHERE `user_id` = ?", array($_GET['user_id']));
				$user_count = $db->num_rows();

				$notes = trim($_POST['notes']);

				if ($user_count == 1)
				{
					$db->sqlquery("UPDATE `admin_user_notes` SET `notes` = ?, `last_edited` = ?, `last_edit_by` = ? WHERE `user_id` = ?", array($notes, core::$date, $_SESSION['user_id'], $_GET['user_id']));
				}
				else if ($user_count == 0)
				{
					$db->sqlquery("INSERT INTO `admin_user_notes` SET `notes` = ?, `last_edited` = ?, `last_edit_by` = ?, `user_id` = ?", array($notes, core::$date, $_SESSION['user_id'], $_GET['user_id']));
				}

				$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = 'edited_user', `data` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], $_GET['user_id'], core::$date, core::$date));

				$_SESSION['message'] = 'edited';
				$_SESSION['message_extra'] = 'user account';
				header("Location: admin.php?module=users&view=edituser&user_id={$_GET['user_id']}");
			}
		}

		if ($_POST['act'] == 'ban')
		{
			$db->sqlquery("SELECT `username` FROM `users` WHERE `user_id` = ?", array($_GET['user_id']));
			$ban_info = $db->fetch();

			if (!isset($_POST['yes']))
			{
				$core->yes_no("Are you sure you wish to ban {$ban_info['username']}?", "admin.php?module=users&user_id={$_GET['user_id']}", 'ban');
			}

			else
			{
				if ($_GET['user_id'] == 1)
				{
					$core->message("You cannot ban the main editor!", NULL, 1);
				}

				else
				{
					$db->sqlquery("UPDATE `users` SET `banned` = 1 WHERE `user_id` = ?", array($_GET['user_id']));

					$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = 'banned_user', `data` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], $_GET['user_id'], core::$date, core::$date));

					$_SESSION['message'] = 'user_banned';
					header("Location: /admin.php?module=users&view=edituser&user_id={$_GET['user_id']}");
				}
			}
		}

		if ($_POST['act'] == 'unban')
		{
			$db->sqlquery("UPDATE `users` SET `banned` = 0 WHERE `user_id` = ?", array($_GET['user_id']));

			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = 'unbanned_user', `data` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], $_GET['user_id'], core::$date, core::$date));

			$_SESSION['message'] = 'user_unbanned';
			header('Location: /admin.php?module=users&view=edituser&user_id='.$_GET['user_id']);
		}

		if ($_POST['act'] == 'ipban')
		{
			if (empty($_POST['ip']))
			{
				$core->message('You need to enter an IP you wish to ban!');
			}

			else
			{
				$db->sqlquery("INSERT INTO `ipbans` SET `ip` = ?, `ban_date` = ?", array($_POST['ip'], core::$sql_date_now));

				$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = 'ip_banned', `data` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], $_POST['ip'], core::$date, core::$date));

				$_SESSION['message'] = 'ip_ban';
				header("Location: /admin.php?module=users&view=ipbanmanage");
			}
		}

		if ($_POST['act'] == 'totalban')
		{
			$db->sqlquery("SELECT `username` FROM `users` WHERE `user_id` = ?", array($_GET['user_id']));
			$ban_info = $db->fetch();

			if (!isset($_POST['yes']) && !isset($_POST['no']))
			{
				$_SESSION['ban_ip'] = $_POST['ip'];
				$core->yes_no("Are you sure you wish to ban {$ban_info['username']} and ban the IP associated with them?", "admin.php?module=users&user_id={$_GET['user_id']}", 'totalban');
			}

			else if (isset($_POST['no']))
			{
				header("Location: /profiles/{$_GET['user_id']}");
			}

			else
			{
				if ($_GET['user_id'] == 1)
				{
					$db->message("You cannot ban the main editor!", NULL, 1);
				}

				else
				{
					$db->sqlquery("UPDATE `users` SET `banned` = 1 WHERE `user_id` = ?", array($_GET['user_id']));
					$db->sqlquery("INSERT INTO `ipbans` SET `ip` = ?, `ban_date` = ?", array($_SESSION['ban_ip'], core::$sql_date_now));

					$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = 'total_ban', `data` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], $_GET['user_id'], core::$date, core::$date));

					$_SESSION['message'] = 'user_banned';
					header("Location: /admin.php?module=users&view=edituser&user_id={$_GET['user_id']}");
					unset($_SESSION['ban_ip']);
				}
			}
		}

		if ($_POST['act'] == 'unipban')
		{
			$db->sqlquery("SELECT `ip` FROM `ipbans` WHERE `id` = ?", array($_POST['id']));
			$get_ip_ban = $db->fetch();

			$db->sqlquery("DELETE FROM `ipbans` WHERE `id` = ?", array($_POST['id']));

			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = 'unban_ip', `data` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], $get_ip_ban['ip'], core::$date, core::$date));

			$_SESSION['message'] = 'ip_unban';
			header("Location: /admin.php?module=users&view=ipbanmanage");
		}

		if ($_POST['act'] == 'editipban')
		{
			if (!isset($_POST['act2']))
			{
				$templating->block('editip');

				$db->sqlquery("SELECT `id`, `ip` FROM `ipbans` WHERE `id` = ?", array($_POST['id']));
				$ip = $db->fetch();

				$templating->set('ip', $ip['ip']);
				$templating->set('id', $ip['id']);
			}

			else if (isset($_POST['act2']) && $_POST['act2'] == 'go')
			{
				if (empty($_POST['ip']))
				{
					$core->message('You need to enter an IP you wish to ban!');
				}

				else
				{
					$db->sqlquery("UPDATE `ipbans` SET `ip` = ? WHERE `id` = ?", array($_POST['ip'], $_POST['id']));
					
					$_SESSION['message'] = 'edited';
					$_SESSION['message_extra'] = 'IP address ban';
					header("Location: /admin.php?module=users&view=ipbanmanage");
				}
			}
		}

		if ($_POST['act'] == 'deleteavatar')
		{
			// remove any old avatar if one was uploaded
			$db->sqlquery("SELECT `avatar`, `avatar_uploaded`, `avatar_gravatar` FROM `users` WHERE `user_id` = ?", array($_GET['user_id']));
			$avatar = $db->fetch();

			if ($avatar['avatar_uploaded'] == 1)
			{
				unlink('uploads/avatars/' . $avatar['avatar']);
			}

			$db->sqlquery("UPDATE `users` SET `avatar` = '', `avatar_uploaded` = 0, `avatar_gravatar` = 0, `gravatar_email` = '' WHERE `user_id` = ?", array($_GET['user_id']));

			$_SESSION['message'] = 'deleted';
			$_SESSION['message_extra'] = 'avatar';
			header("Location: /admin.php?module=users&view=edituser&user_id={$_GET['user_id']}&message=deleted&extra=avatar");
		}

		if ($_POST['act'] == 'delete_user')
		{
			if (!isset($_POST['yes']) && !isset($_POST['no']))
			{
				$core->yes_no("Are you sure you wish to delete {$_POST['username']}? This CANNOT be undone, and this action is logged!", "admin.php?module=users&user_id={$_GET['user_id']}", 'delete_user');
			}

			else if (isset($_POST['no']))
			{
				header("Location: /admin.php?module=users&view=edituser&user_id={$_GET['user_id']}");
			}

			else
			{
				// remove any old avatar if one was uploaded
				$db->sqlquery("SELECT `avatar`, `avatar_uploaded`, `avatar_gravatar`, `username` FROM `users` WHERE `user_id` = ?", array($_GET['user_id']));
				$deleted_info = $db->fetch();

				if ($deleted_info['avatar_uploaded'] == 1)
				{
					unlink('uploads/avatars/' . $deleted_info['avatar']);
				}

				$db->sqlquery("DELETE FROM `users` WHERE `user_id` = ?", array($_GET['user_id']));
				$db->sqlquery("DELETE FROM `forum_topics_subscriptions` WHERE `user_id` = ?", array($_GET['user_id']));
				$db->sqlquery("DELETE FROM `articles_subscriptions` WHERE `user_id` = ?", array($_GET['user_id']));
				$db->sqlquery("DELETE FROM `user_conversations_info` WHERE `owner_id` = ?", array($_GET['user_id']));
				$db->sqlquery("DELETE FROM `user_conversations_participants` WHERE `participant_id` = ?", array($_GET['user_id']));

				$db->sqlquery("UPDATE `config` SET `data_value` = (data_value - 1) WHERE `data_key` = 'total_users'");

				$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = 'delete_user', `data` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], $deleted_info['username'], core::$date, core::$date));

				$_SESSION['message'] = 'deleted';
				$_SESSION['message_extra'] = 'user account';
				header("Location: /admin.php?module=users&view=search");
			}
		}

		if ($_POST['act'] == 'delete_user_content')
		{
			if (!isset($_GET['user_id']))
			{
				$_SESSION['message'] = 'no_id';
				$_SESSION['message_extra'] = 'user id';
				header("Location: /admin.php?module=users&view=search&message=no_id&extra=user");
			}
			else
			{
				$db->sqlquery("SELECT `username`, `banned` FROM `users` WHERE `user_id` = ?", array($_GET['user_id']));
				$check_ban = $db->fetch();

				if ($check_ban['banned'] !== "1")
				{
					$core->message("This user is not banned. Deleting a users content is only possible if they are banned.");
				}
				else
				{
					if (!isset($_POST['yes']) && !isset($_POST['no']))
					{
						$core->yes_no("Are you sure you wish to delete all content from {$check_ban['username']}? This CANNOT be undone, and this action is logged!", "admin.php?module=users&user_id={$_GET['user_id']}", 'delete_user_content');
					}

					else if (isset($_POST['no']))
					{
						header("Location: /admin.php?module=users&view=edituser&user_id={$_GET['user_id']}");
					}
					else
					{
						// delete subscriptions and complete any reports on their forum topics
						$db->sqlquery("SELECT `topic_id`, `reported` FROM `forum_topics` WHERE `author_id` = ?", array($_GET['user_id']));
						$topics_subs_to_remove = $db->fetch_all_rows();
						foreach ($topics_subs_to_remove as $key => $row)
						{
							$db->sqlquery("DELETE FROM `forum_topics_subscriptions` WHERE `topic_id` = ?", array( $row['topic_id'] ));
							if ($row['reported'] == 1)
							{
								$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ?  WHERE `data` = ? AND `type` = 'forum_topic_report'", array(core::$date, $row['topic_id']));
							}
						}

						// complete any reports on their forum replies
						$db->sqlquery("SELECT `post_id`, `reported` FROM `forum_replies` WHERE `author_id` = ?", array($_GET['user_id']));
						$topics_admin_notif_rep_to_remove = $db->fetch_all_rows();
						foreach ($topics_admin_notif_rep_to_remove as $key => $row)
						{
							if ($row['reported'] == 1)
							{
								$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ?  WHERE `data` = ? AND `type` = 'forum_reply_report'", array(core::$date, $row['post_id']));
							}
						}

						// complete any comment reports for their comments
						$db->sqlquery("SELECT `comment_id`, `spam` FROM `articles_comments` WHERE `author_id` = ?" , array($_GET['user_id']));
						$reported_comments = $db->fetch_all_rows();
						foreach ($reported_comments as $comment_loop)
						{
							if ($comment_loop['spam'] == 1)
							{
								$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` = 'reported_comment' AND `data` = ?", array(core::$date, $comment_loop['comment_id']));
							}
						}

						// now do the actual deleting of the rest of their content
						$db->sqlquery("DELETE FROM `forum_replies` WHERE `author_id` = ?", array($_GET['user_id']));
						$db->sqlquery("DELETE FROM `forum_topics` WHERE `author_id` = ?", array($_GET['user_id']));
						$db->sqlquery("DELETE FROM `articles_comments` WHERE `author_id` = ?", array($_GET['user_id']));

						// alert admins this was done
						$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'deleted_user_content', `data` = ?, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], $_GET['user_id'], core::$date, core::$date));

						$_SESSION['message'] = 'user_content_deleted';
						$_SESSION['message_extra'] = 'user content';
						header("Location: /admin.php?module=users&view=edituser&user_id={$_GET['user_id']}");
					}
				}
			}
		}
	}
}

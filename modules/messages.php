<?php
$templating->set_previous('title', 'Private Messages', 1);

if ($_SESSION['user_id'] == 0)
{
	header('Location: /index.php?module=login');
}

else
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

	$templating->load('private_messages');

	// if nothing list messages
	if (!isset($_GET['view']) && !isset($_POST['act']))
	{
		$templating->block('top');

		$compose_link = $core->config('website_url') . 'private-messages/compose/';
		$view_all = $core->config('website_url') . 'private-messages/';
		$templating->set('compose_link', $compose_link);
		$templating->set('view_all', $view_all);
		
		$templating->block('search');
		$templating->set('search_term', '');
		
		// get blocked id's
		$blocked_sql = '';
		$blocked_ids = [];
		if (count($user->blocked_users) > 0)
		{
			foreach ($user->blocked_users as $username => $blocked_id)
			{
				$blocked_ids[] = $blocked_id[0];
			}

			$in  = str_repeat('?,', count($blocked_ids) - 1) . '?';
			$blocked_sql = "AND i.`author_id` NOT IN ($in)";
		}

		// count them for pagination
		$total = $dbl->run("SELECT COUNT(i.`conversation_id`) FROM `user_conversations_info` i INNER JOIN user_conversations_participants p ON p.`participant_id` = i.`owner_id` AND p.`conversation_id` = i.`conversation_id` WHERE i.`owner_id` = ? $blocked_sql", array_merge([$_SESSION['user_id']], $blocked_ids))->fetchOne();

		$last_page = ceil($total/10);

		if ($page > $last_page)
		{
			$page = $last_page;
		}

		// sort out the pagination link
		$pagination = $core->pagination_link(10, $total, "/private-messages/", $page);

		// need to paginate the list
		$get_pms = $dbl->run("SELECT
			i.`conversation_id`,
			i.`title`,
			i.`creation_date`,
			i.`replies`,
			i.`last_reply_date`,
			i.`owner_id`,
			u.`username`,
			u.`user_id`,
			u2.`username` as last_username,
			u2.`user_id` as last_user_id,
			p.`unread`
		FROM
			`user_conversations_info` i
		LEFT JOIN
			`users` u ON u.`user_id` = i.`author_id`
		INNER JOIN
			user_conversations_participants p ON p.`participant_id` = i.`owner_id` AND p.`conversation_id` = i.`conversation_id`
		LEFT JOIN
			`users` u2 ON u2.`user_id` = i.`last_reply_id`
		WHERE
			i.`owner_id` = ? $blocked_sql
		ORDER BY
			i.`last_reply_date` DESC LIMIT ?, 10", array_merge([$_SESSION['user_id']], $blocked_ids, [$core->start]))->fetch_all();
		foreach ($get_pms as $message)
		{
			$templating->block('message_row');

			$pm_url = "/private-messages/{$message['conversation_id']}/";
			$templating->set('pm_url', $pm_url);

			$unread = '';
			$new_bg = '';
			$mail_icon ='<span class="icon inline envelope-open"></span> ';
			if ($message['unread'] == 1)
			{
				$unread = 'class="strong"';
				$new_bg = 'new-message-bg';
				$mail_icon = '<span class="icon inline envelope"></span> ';

			}
			$templating->set('new_message_bolding', $unread);
			$templating->set('new_message_bg', $new_bg);
			$templating->set('mail_icon', $mail_icon);

			$templating->set('title', $message['title']);
			$templating->set('reply_count', $message['replies']);
			$templating->set('last_reply_date', $core->human_date($message['last_reply_date']));
			if (isset($message['username']))
			{
				$author_username = "<a href=\"/profiles/{$message['user_id']}/\">{$message['username']}</a>";
			}
			else
			{
				$author_username = 'Guest';
			}
			$templating->set('author', $author_username);
			$templating->set('creation_date', $core->human_date($message['creation_date']));
			$templating->set('last_reply_username', "<a href=\"/profiles/{$message['last_user_id']}/\">{$message['last_username']}</a>");
		}

		if ($total > 0)
		{
			$templating->block('bottom');
			$templating->set('compose_link', '/private-messages/compose/');

			$templating->block('pagination');
			$templating->set('pagination', $pagination);
		}
	}

	// if editing a message
	if (isset($_GET['view']) && $_GET['view'] == 'Edit')
	{
		if (!isset($_GET['message_id']) || !is_numeric($_GET['message_id']))
		{
			$core->message('No message ID!', 1);
		}

		else if (!isset($_GET['conversation_id']) || !is_numeric($_GET['conversation_id']))
		{
			$core->message('No conversation ID!', 1);
		}

		else
		{
			$info = $dbl->run("SELECT `message`, `author_id` FROM `user_conversations_messages` WHERE `message_id` = ?", array($_GET['message_id']))->fetch();

			if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $info['author_id'] || $user->check_group([1,2]) == true && $_SESSION['user_id'] != 0)
			{
				$page = '';
				if (!empty($_GET['page']) && is_numeric($_GET['page']))
				{
					$page = $_GET['page'];
				}

				$templating->block('edit', 'private_messages');
				$templating->set('formaction', $core->config('website_url') . 'index.php?module=messages&message_id='.$_GET['message_id'].'&conversation_id='.$_GET['conversation_id'].'&page=' . $page);

				$core->editor(['name' => 'text', 'content' => $info['message'], 'editor_id' => 'comment']);

				$templating->block('edit_bottom', 'private_messages');
				$templating->block('preview', 'private_messages');
			}

			else
			{
				$core->message('You are not authorized to edit this message!', 1);
			}
		}
	}

	// if viewing a message
	if (isset($_GET['view']) && $_GET['view'] == 'message')
	{
		// check they can access the message
		$id_check = $dbl->run("SELECT `owner_id` FROM `user_conversations_info` WHERE `conversation_id` = ? AND `owner_id` = ?", array($_GET['id'], $_SESSION['user_id']))->fetchOne();

		if (!$id_check)
		{
			$core->message('Naughty, that is not your message to view!', 1);
		}

		else
		{
			include('includes/profile_fields.php');

			// get usernames of everyone in this conversation
			$people_res = $dbl->run("SELECT u.`username`, u.`user_id` FROM `users` u INNER JOIN `user_conversations_participants` p ON u.`user_id` = p.`participant_id` WHERE p.`conversation_id` = ?", array($_GET['id']))->fetch_all();
			$p_list = '';

			$count_participants = count($people_res);

			foreach ($people_res as $participants)
			{
				$p_list .= "<a href=\"/profiles/{$participants['user_id']}/\">{$participants['username']}</a> ";
			}

			// count them for pagination
			$total = $dbl->run("SELECT COUNT(`conversation_id`) FROM `user_conversations_messages` WHERE `conversation_id` = ? AND position > 0", array($_GET['id']))->fetchOne();

			// sort out the pagination link
			$pagination = $core->pagination_link(9, $total, "/private-messages/{$_GET['id']}/", $page);

			$templating->block('view_top', 'private_messages');
			$templating->set('pagination', $pagination);

			$message_list_link = '/private-messages/';
			$templating->set('message_list_link', $message_list_link);

			$templating->set('conversation_list', $p_list);

			// user profile fields
			$db_grab_fields = '';
			foreach ($profile_fields as $field)
			{
				$db_grab_fields .= "u.`{$field['db_field']}`,";
			}

			$start = $dbl->run("SELECT i.`conversation_id`, i.`title`, m.`creation_date`, m.`message`, m.`message_id`, m.`author_id`, u.`user_id`, u.`register_date`, u.`username`, u.`user_group`, u.`secondary_user_group`, u.`avatar`, u.`avatar_gallery`, $db_grab_fields u.`avatar_uploaded` FROM `user_conversations_info` i INNER JOIN `user_conversations_messages` m ON m.`conversation_id` = i.`conversation_id` LEFT JOIN `users` u ON u.user_id = i.author_id WHERE i.`conversation_id` = ?", array($_GET['id']))->fetch();

			$templating->block('view_row', 'private_messages');
			$templating->set('title', $start['title']);
			$templating->set('post_id', $start['message_id']);
			$templating->set('message_date', $core->human_date($start['creation_date']));
			$templating->set('tzdate', date('c',$start['creation_date']) ); //piratelv timeago
			$templating->set('plain_username',$start['username']);
			$templating->set('text_plain', htmlspecialchars($start['message'], ENT_QUOTES));

			// sort out the avatar
			$avatar = $user->sort_avatar($start);

			$templating->set('avatar', $avatar);
			$templating->set('username', $start['username']);
			$cake_bit = $user->cake_day($start['register_date'], $start['username']);
			$templating->set('cake_icon', $cake_bit);
			$templating->set('user_id', $start['user_id']);
			$templating->set('message_text', $bbcode->parse_bbcode($start['message']));

			$their_groups = $user->post_group_list([$start['author_id']]);
			$start['user_groups'] = $their_groups[$start['author_id']];
			$badges = user::user_badges($start, 1);
			$templating->set('badges', implode(' ', $badges));

			$profile_fields_output = '';

			foreach ($profile_fields as $field)
			{
				if (!empty($start[$field['db_field']]))
				{
					$url = '';
					if ($field['base_link_required'] == 1 && strpos($start[$field['db_field']], $field['base_link']) === false ) //base_link_required and not already in the database
					{
						$url = $field['base_link'];
					}

					$image = '';
					if (isset($field['image']) && $field['image'] != NULL)
					{
						$image = "<img src=\"{$field['image']}\" alt=\"{$field['name']}\" />";
					}

					$span = '';
					if (isset($field['span']))
					{
						$span = $field['span'];
					}
					$into_output = '';
					if ($field['name'] != 'Distro')
					{
						$into_output .= "<li><a href=\"$url{$start[$field['db_field']]}\">$image$span</a></li>";
					}

					$profile_fields_output .= $into_output;
				}
			}

			$templating->set('profile_fields', $profile_fields_output);

			$edit_link = '';
			if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $start['author_id'] || $user->check_group([1,2]) == true && $_SESSION['user_id'] != 0)
			{
				$page = '';
				if (!empty($_GET['page']) && is_numeric($_GET['page']))
				{
					$page = $_GET['page'];
				}

				$edit_link = "<a href=\"/index.php?module=messages&amp;view=Edit&amp;message_id={$start['message_id']}&conversation_id={$start['conversation_id']}&page=$page\"><i class=\"icon-edit\"></i> Edit</a>";
			}
			$templating->set('edit_link', $edit_link);

			// replies
			$get_replies = $dbl->run("SELECT m.`creation_date`, m.`message`, m.`message_id`, m.`author_id`, u.`user_id`, u.`username`, u.`register_date`, u.`user_group`, u.`secondary_user_group`, u.`avatar`, u.`avatar_gallery`, $db_grab_fields u.`avatar_uploaded` FROM `user_conversations_messages` m INNER JOIN `users` u ON u.`user_id` = m.`author_id` WHERE m.`conversation_id` = ? AND m.position > 0 ORDER BY m.message_id ASC LIMIT ?, 9", array($_GET['id'], $core->start))->fetch_all();
			
			if ($get_replies)
			{
				$user_ids = [];
				foreach ($get_replies as $id_loop)
				{
					$user_ids[] = (int) $id_loop['author_id'];
				}
				
				// get a list of each users user groups, so we can display their badges
				$comment_user_groups = $user->post_group_list($user_ids);
			}
			
			foreach ($get_replies as $replies)
			{
				$templating->block('view_row_reply', 'private_messages');
				$templating->set('message_date', $core->human_date($replies['creation_date']));
				$templating->set('tzdate', date('c',$replies['creation_date']) ); //piratelv timeago
				$templating->set('post_id', $replies['message_id']);
				$templating->set('plain_username',$replies['username']);
				$templating->set('text_plain', htmlspecialchars($replies['message'], ENT_QUOTES));

				// sort out the avatar
				$avatar = $user->sort_avatar($replies);

				$templating->set('avatar', $avatar);
				$templating->set('username', $replies['username']);
				$cake_bit = $user->cake_day($replies['register_date'], $replies['username']);
				$templating->set('cake_icon', $cake_bit);
				$templating->set('user_id', $replies['user_id']);
				$templating->set('message_text', $bbcode->parse_bbcode($replies['message']));

				$profile_fields_output = '';

				foreach ($profile_fields as $field)
				{
					if (!empty($replies[$field['db_field']]))
					{
						$url = '';
						if ($field['base_link_required'] == 1)
						{
							$url = $field['base_link'];
						}

						$image = '';
						if (isset($field['image']) && $field['image'] != NULL)
						{
							$image = "<img src=\"{$field['image']}\" alt=\"{$field['name']}\" />";
						}

						$span = '';
						if (isset($field['span']))
						{
							$span = $field['span'];
						}
						$into_output = '';
						if ($field['name'] != 'Distro')
						{
							$into_output .= "<li><a href=\"$url{$replies[$field['db_field']]}\">$image$span</a></li>";
						}

						$profile_fields_output .= $into_output;
					}
				}

				$templating->set('profile_fields', $profile_fields_output);

				// if we have some user groups for that user
				if (array_key_exists($replies['author_id'], $comment_user_groups))
				{
					$replies['user_groups'] = $comment_user_groups[$replies['author_id']];
					$badges = user::user_badges($replies, 1);
					$templating->set('badges', implode(' ', $badges));
				}
				// otherwise guest account or their account was removed, as we didn't get any groups for it
				else
				{
					$templating->set('badges', '');
				}

				$edit_link = '';
				if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $replies['author_id'] || $user->check_group([1,2]) == true && $_SESSION['user_id'] != 0)
				{
					$page = '';
					if (!empty($_GET['page']) && is_numeric($_GET['page']))
					{
						$page = $_GET['page'];
					}
					$edit_link = "<a href=\"/index.php?module=messages&amp;view=Edit&amp;message_id={$replies['message_id']}&conversation_id={$_GET['id']}&page={$page}\"><i class=\"icon-edit\"></i> Edit</a>";
				}
				$templating->set('edit_link', $edit_link);
			}

			$templating->block('view_bottom', 'private_messages');

			// Stop them from replying if it's only them left in the convo
			if ($count_participants != 1)
			{
				$templating->block('reply', 'private_messages');
				$templating->set('pagination', $pagination);

				$core->editor(['name' => 'text', 'editor_id' => 'comment']);

				$templating->block('reply_bottom', 'private_messages');
				$templating->set('conversation_id', $start['conversation_id']);

				$templating->block('preview', 'private_messages');
			}
			// only them left, let them delete it
			else
			{
				$templating->block('bottom_delete', 'private_messages');
				$templating->set('conversation_id', $start['conversation_id']);				
			}

			$dbl->run("UPDATE `user_conversations_participants` SET `unread` = 0 WHERE `participant_id` = ? AND `conversation_id` = ?", array($_SESSION['user_id'], $_GET['id']));
		}
	}

	// if making a message
	if (isset($_GET['view']) && $_GET['view'] == 'compose')
	{
		$title = '';
		$text = '';
		$user_to = '';

		// if there was some sort of error
		if (isset($message_map::$error) && $message_map::$error == 1)
		{			
			if (isset($_SESSION['mto']) && is_array($_SESSION['mto']) && core::is_number($_SESSION['mto']))
			{
				$user_to = '';
				$sql_ids = [];
				$total_users = count($_SESSION['mto']);
				for ($i = 0; $i < $total_users; $i++)
				{
					$sql_ids[] = '?';
				}
				
				$check_res = $dbl->run("SELECT `user_id`, `username` FROM `users` WHERE `user_id` IN (".implode(',', $sql_ids).")", $_SESSION['mto'])->fetch_all();
				foreach ($check_res as $check_to)
				{
					$user_to .= '<option value="'.$check_to['user_id'].'" selected>'.$check_to['username'].'</option>';
				}
			}

			$title = $_SESSION['mtitle'];
			$text = $_SESSION['mtext'];
		}

		// if they've click a link to PM someone specific
		if (isset($_GET['user']))
		{
			$user_info = $dbl->run("SELECT `user_id`, `username`, `get_pms` FROM `users` WHERE `user_id` = ?", array($_GET['user']))->fetch();

			// check your ability to send it to them, don't even list them in the send to box if you cannot
			$can_send = 0;
			if ($user_info['get_pms'] == 1)
			{
				$can_send = 1;
			}
			// if they don't want a PM, check if mod or admin to force allow
			else if ($user_info['get_pms'] == 0)
			{
				if ($user->check_group([1,2,5]))
				{
					$can_send = 1;
				}
				else
				{
					$can_send = 0;
				}
			}
			
			// check they haven't blocked you
			$blocked = $dbl->run("SELECT `blocked_id` FROM `user_block_list` WHERE `user_id` = ? AND `blocked_id` = ?", array($_GET['user'], $_SESSION['user_id']))->fetchOne();
			if ($blocked)
			{
				$can_send = 0;
			}
			
			// check you haven't blocked them
			$blocked = $dbl->run("SELECT `blocked_id` FROM `user_block_list` WHERE `user_id` = ? AND `blocked_id` = ?", array($_SESSION['user_id'], $_GET['user']))->fetchOne();
			if ($blocked)
			{
				$can_send = 0;
			}
			
			if ($can_send == 1)
			{
				$user_to = '<option value="'.$user_info['user_id'].'" selected>'.$user_info['username'].'</option>';
			}
		}

		$templating->block('compose_top', 'private_messages');
		$templating->set('to', $user_to);
		$templating->set('title', $title);

		$core->editor(['name' => 'text', 'content' => $text, 'editor_id' => 'comment']);

		$templating->block('compose_bottom', 'private_messages');
		$templating->block('preview', 'private_messages');
	}
	
	if (isset($_GET['view']) && $_GET['view'] == 'search_title')
	{
		$templating->block('top');
		
		$compose_link = $core->config('website_url') . 'private-messages/compose/';
		$view_all = $core->config('website_url') . 'private-messages/';
		$templating->set('compose_link', $compose_link);
		$templating->set('view_all', $view_all);
		
		$title = str_replace("+", ' ', $_GET['search_title']);
		$title = core::make_safe($title);
		
		$templating->block('search');
		$templating->set('search_term', $title);
		
		$page = core::give_page();
		
		$total_pms = $dbl->run("SELECT COUNT(`conversation_id`) FROM `user_conversations_info` WHERE `owner_id` = ? AND `title` LIKE ?", array($_SESSION['user_id'], '%' . $title . '%'))->fetchOne();
		
		$per_page = 15;
		if (isset($_SESSION['per-page']) && is_numeric($_SESSION['per-page']) && $_SESSION['per-page'] > 0)
		{
			$per_page = $_SESSION['per-page'];
		}
		
		$pagination = $core->pagination_link($per_page, $total_pms, '/index.php?module=messages&view=search_title&', $page, '&search_title='.$title);
		
		$search_res = $dbl->run("SELECT
			i.`conversation_id`,
			i.`title`,
			i.`creation_date`,
			i.`replies`,
			i.`last_reply_date`,
			i.`owner_id`,
			u.`username`,
			u.`user_id`,
			u2.`username` as last_username,
			u2.`user_id` as last_user_id,
			p.`unread`
		FROM
			`user_conversations_info` i
		INNER JOIN
			`users` u ON u.`user_id` = i.`author_id`
		INNER JOIN
			user_conversations_participants p ON p.`participant_id` = i.`owner_id` AND p.`conversation_id` = i.`conversation_id`
		LEFT JOIN
			`users` u2 ON u2.`user_id` = i.`last_reply_id`
		WHERE
			i.`owner_id` = ?
		AND 
			i.`title` LIKE ?
		ORDER BY
			i.`last_reply_date` DESC LIMIT ?, ?", array($_SESSION['user_id'], '%' . $title . '%', $core->start, $per_page))->fetch_all();

		if ($search_res)
		{
			foreach ($search_res as $search)
			{
				$templating->block('message_row');

				$pm_url = "/private-messages/{$search['conversation_id']}/";
				$templating->set('pm_url', $pm_url);

				$unread = '';
				$new_bg = '';
				$mail_icon ='<span class="icon envelope-open"></span> ';
				if ($search['unread'] == 1)
				{
					$unread = 'class="strong"';
					$new_bg = 'new-message-bg';
					$mail_icon = '<span class="icon envelope"></span> ';

				}
				$templating->set('new_message_bolding', $unread);
				$templating->set('new_message_bg', $new_bg);
				$templating->set('mail_icon', $mail_icon);

				$templating->set('title', $search['title']);
				$templating->set('reply_count', $search['replies']);
				$templating->set('last_reply_date', $core->human_date($search['last_reply_date']));
				$templating->set('author', "<a href=\"/profiles/{$search['user_id']}/\">{$search['username']}</a>");
				$templating->set('creation_date', $core->human_date($search['creation_date']));
				$templating->set('last_reply_username', "<a href=\"/profiles/{$search['last_user_id']}/\">{$search['last_username']}</a>");
			}
		}
		else
		{
			$core->message('Nothing was found with those search terms.');
		}
		$templating->block('pagination', 'private_messages');
		$templating->set('pagination', $pagination);
		$templating->block('bottom', 'private_messages');
		$compose_link = $core->config('website_url') . 'private-messages/compose/';
		$templating->set('compose_link', $compose_link);
	}

	if (isset($_POST['act']) && $_POST['act'] == 'New')
	{
		$title = strip_tags($_POST['title']);
		$text = trim($_POST['text']);
		$text = core::make_safe($text);
		$user_ids = '';
		if (isset($_POST['user_ids']))
		{
			$user_ids = $_POST['user_ids'];
		}
		
		// check empty
		$check_empty = core::mempty(compact('user_ids', 'title', 'text'));
		if ($check_empty !== true)
		{
			$_SESSION['mto'] = $user_ids;
			$_SESSION['mtitle'] = $title;
			$_SESSION['mtext'] = $text;

			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $check_empty;
			header("Location: " . $core->config('website_url') . 'private-messages/compose/');
			die();
		}
		
		if(!is_array($_POST['user_ids']) || !core::is_number($_POST['user_ids']))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'usernames';
			header("Location: " . $core->config('website_url') . 'private-messages/compose/');
			die();	
		}

		// first be sure they exist, even though we searched to find them originally, just be sure
		$found_users = 0;
			
		$sql_ids = [];
		$total_users = count($user_ids);
		for ($i = 0; $i < $total_users; $i++)
		{
			$sql_ids[] = '?';
		}
			
		$recepients_count = $dbl->run("SELECT COUNT(`user_id`) FROM `users` WHERE `user_id` IN (".implode(',', $sql_ids).")", $user_ids)->fetchOne();

		if ($recepients_count == 0)
		{
			$_SESSION['message'] = 'notfound';
			header("Location:" . $core->config('website_url') . "private-messages/compose/");
			die();
		}
		
		// make sure they're allow to send it to at least one person
		$check = $dbl->run("SELECT `user_id`, `get_pms` FROM `users` WHERE `user_id` IN (".implode(',', $sql_ids).")", $user_ids)->fetch_all();

		$can_send_to = [];
		foreach ($check as $test)
		{
			if ($test['get_pms'] == 1)
			{
				$can_send_to[] = $test['user_id'];
			}
			else if ($test['get_pms'] == 0)
			{
				if ($user->check_group([1,2,5]))
				{
					$can_send_to[] = $test['user_id'];
				}
			}
		}
		
		// blocking check
		foreach ($can_send_to as $key => $user_id)
		{
			// check they haven't blocked you
			$blocked = $dbl->run("SELECT `blocked_id` FROM `user_block_list` WHERE `user_id` = ? AND `blocked_id` = ?", array($user_id, $_SESSION['user_id']))->fetchOne();
			if ($blocked)
			{
				unset($can_send_to[$key]);
			}
			
			// check you haven't blocked them
			$blocked = $dbl->run("SELECT `blocked_id` FROM `user_block_list` WHERE `user_id` = ? AND `blocked_id` = ?", array($_SESSION['user_id'], $user_id))->fetchOne();
			if ($blocked)
			{
				unset($can_send_to[$key]);
			}
		}
		
		// at least one person they can send the message to
		if (count($can_send_to) > 0)
		{
			// make the new message
			$dbl->run("INSERT INTO `user_conversations_info` SET `title` = ?, `creation_date` = ?, `author_id` = ?, `owner_id` = ?, `last_reply_date` = ?, `replies` = 0, `last_reply_id` = ?", array($title, core::$date, $_SESSION['user_id'], $_SESSION['user_id'], core::$date, $_SESSION['user_id']));

			$conversation_id = $dbl->new_id();

			// send message to each user
			foreach ($can_send_to as $user_id)
			{
				// make the duplicate message for other participants
				$dbl->run("INSERT INTO `user_conversations_info` SET `conversation_id` = ?, `title` = ?, `creation_date` = ?, `author_id` = ?, `owner_id` = ?, `last_reply_date` = ?, `replies` = 0, `last_reply_id` = ?", array($conversation_id, $title, core::$date, $_SESSION['user_id'], $user_id, core::$date, $_SESSION['user_id']));

				// Add all the participants
				$dbl->run("INSERT INTO `user_conversations_participants` SET `conversation_id` = ?, `participant_id` = ?, unread = 1", array($conversation_id, $user_id));

				// also while we are here, email each user to tell them they have a new convo
				$email_data = $dbl->run("SELECT `username`, `email`, `email_on_pm` FROM `users` WHERE `user_id` = ? AND `user_id` != ?", array($user_id, $_SESSION['user_id']))->fetch();

				if ($email_data['email_on_pm'] == 1)
				{
					// subject
					$subject = 'New conversation started on GamingOnLinux';

					$email_text = $bbcode->email_bbcode($text);

					$message = '';

					// message
					$html_message = "<p>Hello <strong>{$email_data['username']}</strong>,</p>
					<p><strong>{$_SESSION['username']}</strong> has started a new conversation with you on <a href=\"".$core->config('website_url')."private-messages/\" target=\"_blank\">GamingOnLinux</a>, titled \"<a href=\"".$core->config('website_url')."private-messages/{$conversation_id}\" target=\"_blank\"><strong>{$_POST['title']}</strong></a>\".</p>
					<br style=\"clear:both\">
					<div>
					<hr>
					{$email_text}";

					$plain_message = PHP_EOL."Hello {$email_data['username']}, {$_SESSION['username']} has started a new conversation with you on ".$core->config('website_url')."private-messages/, titled \"{$_POST['title']}\",\r\n{$_POST['text']}";
					$boundary = uniqid('np');

					// Mail it
					if ($core->config('send_emails') == 1)
					{
						$mail = new mailer($core);
						$mail->sendMail($email_data['email'], $subject, $html_message, $plain_message);
					}
				}
			}

			$dbl->run("INSERT INTO `user_conversations_messages` SET `conversation_id` = ?, `author_id` = ?, `creation_date` = ?, `message` = ?, `position` = 0", array($conversation_id, $_SESSION['user_id'], core::$date, $text));

			$dbl->run("INSERT INTO `user_conversations_participants` SET `conversation_id` = ?, `participant_id` = ?, unread = 0", array($conversation_id, $_SESSION['user_id']));

			$_SESSION['message'] = 'pm_sent';
			header("Location: /private-messages/");
			die();

		}
		else
		{
			$_SESSION['message'] = 'cannot_send_pm';
			header("Location: /private-messages/");
			die();
		}
	}

	if (isset($_POST['act']) && $_POST['act'] == 'Edit')
	{
		$text = trim($_POST['text']);
		$text = core::make_safe($text);

		if (!isset($_GET['message_id']) || !is_numeric($_GET['message_id']))
		{
			$core->message('No message ID!', 1);
		}

		else if (!isset($_GET['conversation_id']) || !is_numeric($_GET['conversation_id']))
		{
			$core->message('No conversation ID!', 1);
		}

		else if (empty($text))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'text';
			header("Location: /index.php?module=messages&view=Edit&message_id=" . $_GET['message_id'] . "&conversation_id=" . $_GET['conversation_id']);
		}

		else
		{
			$info = $dbl->run("SELECT `message`, `author_id` FROM `user_conversations_messages` WHERE `message_id` = ?", array($_GET['message_id']))->fetch();

			if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $info['author_id'] || $user->check_group([1,2]) == true && $_SESSION['user_id'] != 0)
			{
				$dbl->run("UPDATE `user_conversations_messages` SET `message` = ? WHERE `message_id` = ?", array($text, $_GET['message_id']));

				$page = '';
				if (!empty($_GET['page']) && is_numeric($_GET['page']))
				{
					$page = "page={$_GET['page']}";
				}

				header("Location: /private-messages/{$_GET['conversation_id']}/$page");
				die();
			}

			else
			{
				$core->message('You are not authorized to edit this message!', 1);
			}
		}
	}

	if (isset($_POST['act']) && $_POST['act'] == 'Delete')
	{
		// check the id exists
		$check_res = $dbl->run("SELECT `conversation_id` FROM `user_conversations_info` WHERE `conversation_id` = ? AND `owner_id` = ?", array($_POST['conversation_id'], $_SESSION['user_id']))->fetch();
		if ($check_res)
		{
			// check they are okay with deleting it
			if (!isset($_POST['yes']) && !isset($_POST['no']))
			{
				$templating->set_previous('title', 'Deleting PM', 1);
				$core->yes_no('Are you sure you want to delete that Personal Messaging thread?', "index.php?module=messages", 'Delete', $_POST['conversation_id'], 'conversation_id');
			}

			else if (isset($_POST['no']))
			{
				header("Location: /private-messages/");
			}

			else if (isset($_POST['yes']))
			{
				$dbl->run("DELETE FROM `user_conversations_info` WHERE `conversation_id` = ? AND `owner_id` = ?", array($_POST['conversation_id'], $_SESSION['user_id']));
				$dbl->run("DELETE FROM `user_conversations_participants` WHERE `conversation_id` = ? AND `participant_id` = ?", array($_POST['conversation_id'], $_SESSION['user_id']));

				// check if there's no one left in the conversation, remove it entirely
				$people_left = $dbl->run("SELECT COUNT(*) FROM `user_conversations_participants` WHERE `conversation_id` = ?", array($_POST['conversation_id']))->fetchOne();
				if ($people_left == 0)
				{
					$dbl->run("DELETE FROM `user_conversations_messages` WHERE `conversation_id` = ?", array($_POST['conversation_id']));
				}
				
				$_SESSION['message'] = 'deleted';
				$_SESSION['message_extra'] = 'private message';
				
				header("Location: /private-messages/");
			}
		}

		else
		{
			header("Location: /private-messages/");
			die();
		}
	}

	// if a reply has been made
	if (isset($_POST['act']) && $_POST['act'] == 'Reply')
	{
		$text = trim($_POST['text']);
		$text = core::make_safe($text);

		if (empty($_POST['conversation_id']) || !is_numeric($_POST['conversation_id']))
		{
			$core->message("Not a valid conversation! <a href=\"/private-messages/\">Click here to return.</a>");
		}

		else if (empty($text))
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = 'text';
			header("Location: /private-messages/{$_POST['conversation_id']}/");
		}

		else
		{
			// find last position
			$last = $dbl->run("SELECT m.`position`, i.title FROM `user_conversations_messages` m INNER JOIN `user_conversations_info` i ON m.conversation_id = i.conversation_id WHERE m.`conversation_id` = ? ORDER BY m.`message_id` DESC LIMIT 1", array($_POST['conversation_id']))->fetch();

			$position = $last['position'] + 1;

			// add the new reply
			$dbl->run("INSERT INTO `user_conversations_messages` SET `conversation_id` = ?, `author_id` = ?, `creation_date` = ?, `message` = ?, `position` = ?", array($_POST['conversation_id'], $_SESSION['user_id'], core::$date, $text, $position));
			$post_id = $dbl->new_id();

			// update conversation info
			$dbl->run("UPDATE `user_conversations_info` SET `replies` = (replies + 1), `last_reply_date` = ?, `last_reply_id` = ? WHERE `conversation_id` = ?", array(core::$date, $_SESSION['user_id'], $_POST['conversation_id']));

			// make unread notifications
			$participants = $dbl->run("SELECT `participant_id` FROM `user_conversations_participants` WHERE `conversation_id` = ? AND `participant_id` != ?", array($_POST['conversation_id'], $_SESSION['user_id']))->fetch_all();
			foreach ($participants as $person)
			{
				// check to see if they're blocking you, otherwise, don't notify them of this at all
				$check = $dbl->run("SELECT `blocked_id` FROM `user_block_list` WHERE `user_id` = ? AND `blocked_id` = ?", array($person['participant_id'], $_SESSION['user_id']))->fetch();
				if (!$check)
				{
					$dbl->run("UPDATE `user_conversations_participants` SET `unread` = 1 WHERE `participant_id` = ? AND `conversation_id` = ?", array($person['participant_id'], $_POST['conversation_id']));

					// also while we are here, email each user to tell them they have a new reply
					$email_data = $dbl->run("SELECT `username`, `email`, `email_on_pm` FROM `users` WHERE `user_id` = ? AND `user_id` != ?", array($person['participant_id'], $_SESSION['user_id']))->fetch();

					if ($email_data['email_on_pm'] == 1)
					{
						// subject
						$subject = 'New reply to a conversation on GamingOnLinux';

						$email_text = $bbcode->email_bbcode($text);

						// message
						$html_message = "<p>Hello <strong>{$email_data['username']}</strong>,</p>
						<p><strong>{$_SESSION['username']}</strong> has replied to a conversation with you on <a href=\"".$core->config('website_url')."private-messages/\" target=\"_blank\">GamingOnLinux</a>, titled \"<a href=\"".$core->config('website_url')."private-messages/{$_POST['conversation_id']}\" target=\"_blank\"><strong>{$last['title']}</strong></a>\".</p>
						<br style=\"clear:both\">
						<div>
						<hr>
						{$email_text}";

						$plain_message = PHP_EOL."Hello {$email_data['username']}, {$_SESSION['username']} has replied to a conversation with you on ".$core->config('website_url')."private-messages/, titled \"{$last['title']}\",\r\n{$_POST['text']}";
						$boundary = uniqid('np');

						// Mail it
						if ($core->config('send_emails') == 1)
						{
							$mail = new mailer($core);
							$mail->sendMail($email_data['email'], $subject, $html_message, $plain_message);
						}
					}
				}
			}

			$get_info = $dbl->run("SELECT `replies` FROM `user_conversations_info` WHERE `conversation_id` = ?", array($_POST['conversation_id']))->fetch();

			$page = 1;
			if ($get_info['replies'] > 9)
			{
				$page = ceil($get_info['replies']/9);
			}
			
			$_SESSION['message'] = 'pm_sent';

			header("Location: /private-messages/{$_POST['conversation_id']}/page=$page");
			die();
		}
	}
}

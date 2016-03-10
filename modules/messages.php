<?php
$templating->set_previous('title', 'Private Messages', 1);

if ($_SESSION['user_id'] == 0)
{
	header('Location: /index.php?module=login');
}

else
{
	if (isset($_GET['message']))
	{
		if ($_GET['message'] == 'deleted')
		{
			$core->message("Message Deleted!", NULL, 1);
		}
	}

	// paging for pagination
	if (!isset($_GET['page']))
	{
		$page = 1;
	}

	else if (is_numeric($_GET['page']))
	{
		$page = $_GET['page'];
	}

	$templating->merge('private_messages');

	// if nothing list messages
	if (!isset($_GET['view']) && !isset($_POST['act']))
	{
		$templating->block('top');

		if ($config['pretty_urls'] == 1)
		{
			$compose_link = '/private-messages/compose/';
		}
		else {
			$compose_link = core::config('website_url') . "index.php?module=messages&view=compose";
		}
		$templating->set('compose_link', $compose_link);

		// count them for pagination
		$db->sqlquery("SELECT `conversation_id` FROM `user_conversations_info` WHERE `owner_id` = ?", array($_SESSION['user_id']));
		$total = $db->num_rows();

		// sort out the pagination link
		$pagination = $core->pagination_link(9, $total, "/private-messages/", $page);

		// need to paginate the list
		$db->sqlquery("SELECT
			i.`conversation_id`,
			i.`title`,
			i.`creation_date`,
			i.replies,
			i.last_reply_date,
			i.owner_id,
			u.username,
			u.user_id,
			u2.username as last_username,
			u2.user_id as last_user_id,
			p.unread
		FROM
			`user_conversations_info` i
		INNER JOIN
			`users` u ON u.user_id = i.author_id
		INNER JOIN
			user_conversations_participants p ON p.participant_id = i.owner_id AND p.conversation_id = i.conversation_id
		LEFT JOIN
			`users` u2 ON u2.user_id = i.last_reply_id
		WHERE
			i.`owner_id` = ?
		ORDER BY
			i.`last_reply_date` DESC LIMIT ?, 9", array($_SESSION['user_id'], $core->start));

		while ($message = $db->fetch())
		{
			$templating->block('message_row');

			if ($config['pretty_urls'] == 1)
			{
				$pm_url = "/private-messages/{$message['conversation_id']}/";
			}
			else {
				$pm_url = core::config('website_url') . "index.php?module=messages&view=message&id={$message['conversation_id']}";
			}

			$templating->set('pm_url', $pm_url);

			$unread = '';
			$new_bg = '';
			$mail_icon ='<span class="icon envelope-open"></span> ';
			if ($message['unread'] == 1)
			{
				$unread = 'class="strong"';
				$new_bg = 'new-message-bg';
				$mail_icon = '<span class="icon envelope"></span> ';

			}
			$templating->set('new_message_bolding', $unread);
			$templating->set('new_message_bg', $new_bg);
			$templating->set('mail_icon', $mail_icon);

			$templating->set('title', $message['title']);
			$templating->set('reply_count', $message['replies']);
			$templating->set('last_reply_date', $core->format_date($message['last_reply_date']));
			$templating->set('author', "<a href=\"/profiles/{$message['user_id']}/\">{$message['username']}</a>");
			$templating->set('creation_date', $core->format_date($message['creation_date']));
			$templating->set('last_reply_username', "<a href=\"/profiles/{$message['last_user_id']}/\">{$message['last_username']}</a>");
		}

		$templating->block('bottom');
		if ($config['pretty_urls'] == 1)
		{
			$compose_link = '/private-messages/compose/';
		}
		else {
			$compose_link = "{$config['path']}index.php?module=messages&view=compose";
		}
		$templating->set('compose_link', $compose_link);
		$templating->set('pagination', $pagination);
	}

	// if editing a message
	if (isset($_GET['view']) && $_GET['view'] == 'Edit')
	{
		if (!isset($_GET['message_id']) || !is_numeric($_GET['message_id']))
		{
			$core->message('No message ID!', NULL, 1);
		}

		else if (!isset($_GET['conversation_id']) || !is_numeric($_GET['conversation_id']))
		{
			$core->message('No conversation ID!', NULL, 1);
		}

		else
		{
			$db->sqlquery("SELECT `message`, `author_id` FROM `user_conversations_messages` WHERE `message_id` = ?", array($_GET['message_id']));
			$info = $db->fetch();

			if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $info['author_id'] || $user->check_group(1,2) == true && $_SESSION['user_id'] != 0)
			{
				$templating->block('edit', 'private_messages');

				$page = '';
				if (!empty($_GET['page']) && is_numeric($_GET['page']))
				{
					$page = $_GET['page'];
				}

				$buttons = '<button type="submit" name="act" value="Edit" class="btn btn-primary">Edit</button><button type="submit" name="act" value="preview_edit" class="btn btn-primary">Preview Edit</button>';

				$core->editor('text', $info['message'], $buttons, $config['path'] . 'index.php?module=messages&message_id='.$_GET['message_id'].'&conversation_id='.$_GET['conversation_id'].'&page=' . $page);
			}

			else
			{
				$core->message('You are not authorized to edit this message!', NULL, 1);
			}
		}
	}

	// if viewing a message
	if (isset($_GET['view']) && $_GET['view'] == 'message')
	{
		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'empty')
			{
				$core->message('You have to enter a message to reply!', NULL, 1);
			}
		}

		// check they can access the message
		$check_id_now = array();
		$db->sqlquery("SELECT `owner_id` FROM `user_conversations_info` WHERE `conversation_id` = ?", array($_GET['id']));
		while ($check_ids = $db->fetch())
		{
			$check_id_now[] = $check_ids['owner_id'];
		}

		if (!in_array($_SESSION['user_id'], $check_id_now))
		{
			$core->message('Naughty, that is not your message to view!', NULL, 1);
		}

		else
		{
			include('includes/profile_fields.php');

			// get usernames of everyone in this conversation
			$db->sqlquery("SELECT u.`username`, u.`user_id` FROM `users` u INNER JOIN `user_conversations_participants` p ON u.user_id = p.participant_id WHERE p.conversation_id = ?", array($_GET['id']));
			$p_list = '';

			$count_participants = $db->num_rows();

			while ($participants = $db->fetch())
			{
				$p_list .= "<a href=\"/profiles/{$participants['user_id']}/\">{$participants['username']}</a> ";
			}

			$templating->block('view_top', 'private_messages');
			$templating->set('conversation_list', $p_list);
			$templating->set('form_action', 'index.php?module=messages');

			// count them for pagination
			$db->sqlquery("SELECT `conversation_id` FROM `user_conversations_messages` WHERE `conversation_id` = ? AND position > 0", array($_GET['id']));
			$total = $db->num_rows();

			// sort out the pagination link
			$pagination = $core->pagination_link(9, $total, "/private-messages/{$_GET['id']}/", $page);

			// user profile fields
			$db_grab_fields = '';
			foreach ($profile_fields as $field)
			{
				$db_grab_fields .= "u.`{$field['db_field']}`,";
			}

			$db->sqlquery("SELECT i.conversation_id, i.`title`, m.creation_date, m.message, m.message_id, m.author_id, u.user_id, u.username, u.user_group, u.secondary_user_group, u.avatar, u.avatar_gravatar,u.gravatar_email, $db_grab_fields u.avatar_uploaded FROM `user_conversations_info` i INNER JOIN `user_conversations_messages` m ON m.conversation_id = i.conversation_id INNER JOIN `users` u ON u.user_id = i.author_id WHERE i.`conversation_id` = ?", array($_GET['id']));
			$start = $db->fetch();

			$templating->block('view_row', 'private_messages');
			$templating->set('pagination', $pagination);
			$templating->set('title', $start['title']);
			$templating->set('post_id', $start['message_id']);
			$templating->set('message_date', $core->format_date($start['creation_date']));
			$templating->set('tzdate', date('c',$start['creation_date']) ); //piratelv timeago
			$templating->set('plain_username',$start['username']);
			$templating->set('text_plain', $start['message']);

			// sort out the avatar
			// either no avatar (gets no avatar from gravatars redirect) or gravatar set
			if (empty($start['avatar']) || $start['avatar_gravatar'] == 1)
			{
				$avatar = "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $start['gravatar_email'] ) ) ) . "?d=http://www.gamingonlinux.com/uploads/avatars/no_avatar.png";
			}

			// either uploaded or linked an avatar
			else
			{
				$avatar = $start['avatar'];
				if ($start['avatar_uploaded'] == 1)
				{
					$avatar = "/uploads/avatars/{$start['avatar']}";
				}
			}

			$templating->set('avatar', $avatar);
			$templating->set('username', $start['username']);
			$templating->set('user_id', $start['user_id']);
			$templating->set('message_text', bbcode($start['message']));

			$donator_badge = '';

			if (($start['secondary_user_group'] == 6 || $start['secondary_user_group'] == 7) && $start['user_group'] != 1 && $start['user_group'] != 2)
			{
				$donator_badge = '<br />
				<span class="label label-warning">GOL Supporter!</span><br /> ';
			}
			$templating->set('donator_badge', $donator_badge);

			$editor_bit = '';
			// check if editor or admin
			if ($start['user_group'] == 1 || $start['user_group'] == 2)
			{
				$editor_bit = "<span class=\"label label-success\">Editor</span><br />";
			}
			$templating->set('editor', $editor_bit);

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
			if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $start['author_id'] || $user->check_group(1,2) == true && $_SESSION['user_id'] != 0)
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
			$db->sqlquery("SELECT m.creation_date, m.message, m.message_id, m.author_id, u.user_id, u.username, u.user_group, u.secondary_user_group, u.avatar, u.avatar_gravatar,u.gravatar_email, $db_grab_fields u.avatar_uploaded FROM `user_conversations_messages` m INNER JOIN `users` u ON u.user_id = m.author_id WHERE m.`conversation_id` = ? AND m.position > 0 ORDER BY m.message_id ASC LIMIT ?, 9", array($_GET['id'], $core->start));
			while ($replies = $db->fetch())
			{
				$templating->block('view_row_reply', 'private_messages');
				$templating->set('message_date', $core->format_date($replies['creation_date']));
				$templating->set('tzdate', date('c',$replies['creation_date']) ); //piratelv timeago
				$templating->set('post_id', $replies['message_id']);
				$templating->set('plain_username',$replies['username']);
				$templating->set('text_plain', $replies['message']);

				// sort out the avatar
				// either no avatar (gets no avatar from gravatars redirect) or gravatar set
				if (empty($replies['avatar']) || $replies['avatar_gravatar'] == 1)
				{
					$avatar = "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $replies['gravatar_email'] ) ) ) . "?d=http://www.gamingonlinux.com/uploads/avatars/no_avatar.png";
				}

				// either uploaded or linked an avatar
				else
				{
					$avatar = $replies['avatar'];
					if ($replies['avatar_uploaded'] == 1)
					{
						$avatar = "/uploads/avatars/{$replies['avatar']}";
					}
				}

				$templating->set('avatar', $avatar);
				$templating->set('username', $replies['username']);
				$templating->set('user_id', $replies['user_id']);
				$templating->set('message_text', bbcode($replies['message']));

				$donator_badge = '';

				if (($replies['secondary_user_group'] == 6 || $replies['secondary_user_group'] == 7) && $replies['user_group'] != 1 && $replies['user_group'] != 2)
				{
					$donator_badge = '<br />
					<span class="label label-warning">GOL Supporter!</span><br /> ';
				}
				$templating->set('donator_badge', $donator_badge);

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

				$editor_bit = '';
				// check if editor or admin
				if ($replies['user_group'] == 1 || $replies['user_group'] == 2)
				{
					$editor_bit = "<span class=\"label label-success\">Editor</span><br />";
				}
				$templating->set('editor', $editor_bit);

				$edit_link = '';
				if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $replies['author_id'] || $user->check_group(1,2) == true && $_SESSION['user_id'] != 0)
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

			// Stop them from replying if it's only them left in the convo
			if ($count_participants != 1)
			{
				$templating->block('reply', 'private_messages');
				$templating->set('pagination', $pagination);

				$core->editor('text', '');

				$templating->block('view_bottom', 'private_messages');
				$templating->set('preview_action', 'index.php?module=messages#commentbox');
				$templating->set('conversation_id', $start['conversation_id']);
			}

			$templating->block('delete', 'private_messages');
			$templating->set('conversation_id', $start['conversation_id']);

			$db->sqlquery("UPDATE `user_conversations_participants` SET `unread` = 0 WHERE `participant_id` = ? AND `conversation_id` = ?", array($_SESSION['user_id'], $_GET['id']));
		}
	}

	// if making a message
	if (isset($_GET['view']) && $_GET['view'] == 'compose')
	{
		$title = '';
		$text = '';
		$user_to = '';

		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'empty')
			{
				$core->message("You have to enter in at least 1 person and some text!", NULL, 1);
			}

			if ($_GET['message'] == 'notfound')
			{
				$core->message("We couldn't find the people requested! Have you got the correct username spellings? Please try again.", NULL, 1);
			}

			$user_to = $_SESSION['mto'];
			$title = $_SESSION['mtitle'];
			$text = $_SESSION['mtext'];
		}

		if (isset($_GET['user']))
		{
			// find the username of the person requested
			$db->sqlquery("SELECT `username` FROM `users` WHERE `user_id` = ?", array($_GET['user']));
			$user_info = $db->fetch();

			$user_to = $user_info['username'];
		}

		$templating->block('compose_top', 'private_messages');
		$templating->set('to', $user_to);
		$templating->set('title', $title);

		$core->editor('text', $text);

		$templating->block('compose_bottom', 'private_messages');
		$templating->set('preview_action', '/index.php?module=messages#commentbox');
	}

	if (isset($_POST['act']) && $_POST['act'] == 'preview_new')
	{
		$title = '';
		$text = '';
		$user_to = '';
		if (!empty($_POST['to']))
		{
			$user_to = $_POST['to'];
		}
		if (!empty($_POST['title']))
		{
			$title = $_POST['title'];
		}
		if (!empty($_POST['text']))
		{
			$text = $_POST['text'];
		}

		$templating->block('preview');
		$templating->set('message', bbcode($_POST['text']));

		$buttons = '<button type="submit" name="act" value="New" class="btn btn-primary" >Send Message</button><button type="submit" name="act" value="preview_new" class="btn btn-primary" >Preview Message</button>';

		$core->editor('text', $text, $buttons, $config['path'] . 'index.php?module=messages', '<div class="box"><div class="head">Compose a new private message</div></div>
		To (seperate names by commas, make sure you have their exact username!)<br />
		<input type="text" name="to" value="'.$user_to.'" /><br />
		Title<br />
		<input type="text" name="title" value="'.$title.'" /><br />');
	}

	if (isset($_POST['act']) && $_POST['act'] == 'preview_edit')
	{
		$page = '';
		if (!empty($_GET['page']) && is_numeric($_GET['page']))
		{
			$page = $_GET['page'];
		}

		$text = '';
		if (!empty($_POST['text']))
		{
			$text = $_POST['text'];
		}

		$templating->block('preview');
		$templating->set('message', bbcode($text));

		$buttons = '<button type="submit" name="act" value="Edit" class="btn btn-primary">Edit</button><button type="submit" name="act" value="preview_edit" class="btn btn-primary">Preview Edit</button>';

		$core->editor('text', $text, $buttons, $config['path'] . 'index.php?module=messages&message_id='.$_GET['message_id'].'&conversation_id='.$_GET['conversation_id'].'&page=' . $page);
	}

	if (isset($_POST['act']) && $_POST['act'] == 'New')
	{
		$title = strip_tags($_POST['title']);
		$text = trim($_POST['text']);
		$text = htmlspecialchars($text);

		// check empty
		if (empty($_POST['to']) || empty($title) || empty($text))
		{
			$_SESSION['mto'] = $_POST['to'];
			$_SESSION['mtitle'] = $title;
			$_SESSION['mtext'] = $text;

			if ($config['pretty_urls'] == 1)
			{
				header("Location: /private-messages/compose/message=empty");
				die();
			}
			else
			{
				header("Location: {$config['path']}index.php?module=messages&view=compose&message=empty");
				die();
			}
		}

		else
		{
			// find users
			$users = explode(',', $_POST['to']);

			$user_id_list = array();

			foreach ($users as $user)
			{
				$user = trim($user);
				$db->sqlquery("SELECT `user_id` FROM `users` WHERE `username` = ?", array($user));
				$user_id = $db->fetch();

				if ($user_id['user_id'] != $_SESSION['user_id'])
				{
					$user_id_list[] = $user_id['user_id'];
				}
			}

			if (empty($user_id))
			{
				if ($config['pretty_urls'] == 1)
				{
					header("Location: {$config['path']}private-messages/compose/message=notfound");
					die();
				}
				else
				{
					header("Location: {$config['path']}index.php?module=messages&view=compose&message=notfound");
					die();
				}
			}

			// make the new message
			$db->sqlquery("INSERT INTO `user_conversations_info` SET `title` = ?, `creation_date` = ?, `author_id` = ?, `owner_id` = ?, `last_reply_date` = ?, `replies` = 0, `last_reply_id` = ?", array($title, $core->date, $_SESSION['user_id'], $_SESSION['user_id'], $core->date, $_SESSION['user_id']));

			$conversation_id = $db->grab_id();

			// send message to each user
			foreach ($user_id_list as $user_id)
			{
				// make the duplicate message for other participants
				$db->sqlquery("INSERT INTO `user_conversations_info` SET `conversation_id` = ?, `title` = ?, `creation_date` = ?, `author_id` = ?, `owner_id` = ?, `last_reply_date` = ?, `replies` = 0, `last_reply_id` = ?", array($conversation_id, $title, $core->date, $_SESSION['user_id'], $user_id, $core->date, $_SESSION['user_id']));

				// Add all the participants
				$db->sqlquery("INSERT INTO `user_conversations_participants` SET `conversation_id` = ?, `participant_id` = ?, unread = 1", array($conversation_id, $user_id));

				// also while we are here, email each user to tell them they have a new convo
				$db->sqlquery("SELECT `username`, `email`, `email_on_pm` FROM `users` WHERE `user_id` = ? AND `user_id` != ?", array($user_id, $_SESSION['user_id']));
				$email_data = $db->fetch();

				if ($email_data['email_on_pm'] == 1)
				{
					// sort out registration email
					$to  = $email_data['email'];

					// subject
					$subject = 'New conversation started on GamingOnLinux.com';

					$email_text = email_bbcode($text);

					$message = '';

					// message
					$html_message = "
					<html>
					<head>
					<title>New conversation started on GamingOnLinux.com</title>
					</head>
					<body>
					<img src=\"http://www.gamingonlinux.com/templates/default/images/icon.png\" alt=\"Gaming On Linux\">
					<br />
					<p>Hello <strong>{$email_data['username']}</strong>,</p>
					<p><strong>{$_SESSION['username']}</strong> has started a new conversation with you on <a href=\"http://www.gamingonlinux.com/private-messages/\" target=\"_blank\">gamingonlinux.com</a>, titled \"<a href=\"http://www.gamingonlinux.com/private-messages/\" target=\"_blank\"><strong>{$_POST['title']}</strong></a>\".</p>
					<br style=\"clear:both\">
					<div>
					<hr>
					{$email_text}
			 		<hr>
			  		<p>If you haven&#39;t registered at <a href=\"http://www.gamingonlinux.com\" target=\"_blank\">gamingonlinux.com</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> with some info about what you want us to do about it or if you logged in and found no message let us know!</p>
			  		<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
					</div>
					</body>
					</html>
					";

					$plain_message = PHP_EOL."Hello {$email_data['username']}, {$_SESSION['username']} has started a new conversation with you on  http://www.gamingonlinux.com/private-messages, titled \"{$_POST['title']}\",\r\n{$_POST['text']}";
					$boundary = uniqid('np');

					// To send HTML mail, the Content-type header must be set
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= "Content-Type: multipart/alternative;charset=utf-8;boundary=" . $boundary . "\r\n";
					$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

					$message .= "\r\n\r\n--" . $boundary.PHP_EOL;
					$message .= "Content-Type: text/plain;charset=utf-8".PHP_EOL;
					$message .= "Content-Transfer-Encoding: 7bit".PHP_EOL;
					$message .= $plain_message;

					$message .= "\r\n\r\n--" . $boundary.PHP_EOL;
					$message .= "Content-Type: text/html;charset=utf-8".PHP_EOL;
					$message .= "Content-Transfer-Encoding: 7bit".PHP_EOL;
					$message .= "$html_message";
					$message .= "\r\n\r\n--" . $boundary . "--";

					// Mail it
					mail($to, $subject, $message, $headers);
				}
			}

			$db->sqlquery("INSERT INTO `user_conversations_messages` SET `conversation_id` = ?, `author_id` = ?, `creation_date` = ?, `message` = ?, `position` = 0", array($conversation_id, $_SESSION['user_id'], $core->date, $text));

			$db->sqlquery("INSERT INTO `user_conversations_participants` SET `conversation_id` = ?, `participant_id` = ?, unread = 0", array($conversation_id, $_SESSION['user_id']));

			if ($config['pretty_urls'] == 1)
			{
				header("Location: /private-messages/");
			}
			else {
				header("Location: {$config['path']}index.php?module=messages");
			}
		}
	}

	if (isset($_POST['act']) && $_POST['act'] == 'Edit')
	{
		if (!isset($_GET['message_id']) || !is_numeric($_GET['message_id']))
		{
			$core->message('No message ID!', NULL, 1);
		}

		else if (!isset($_GET['conversation_id']) || !is_numeric($_GET['conversation_id']))
		{
			$core->message('No conversation ID!', NULL, 1);
		}

		else
		{
			$db->sqlquery("SELECT `message`, `author_id` FROM `user_conversations_messages` WHERE `message_id` = ?", array($_GET['message_id']));
			$info = $db->fetch();

			if (($_SESSION['user_id'] != 0) && $_SESSION['user_id'] == $info['author_id'] || $user->check_group(1,2) == true && $_SESSION['user_id'] != 0)
			{
				$text = trim($_POST['text']);
				$text = htmlspecialchars($text);
				$db->sqlquery("UPDATE `user_conversations_messages` SET `message` = ? WHERE `message_id` = ?", array($text, $_GET['message_id']));

				$page = '';
				if (!empty($_GET['page']) && is_numeric($_GET['page']))
				{
					$page = "page={$_GET['page']}";
				}

				header("Location: /private-messages/{$_GET['conversation_id']}/$page");
			}

			else
			{
				$core->message('You are not authorized to edit this message!', NULL, 1);
			}
		}
	}

	if (isset($_POST['act']) && $_POST['act'] == 'Delete')
	{
		// check the id exists
		$db->sqlquery("SELECT `conversation_id` FROM `user_conversations_info` WHERE `conversation_id` = ? AND `owner_id` = ?", array($_POST['conversation_id'], $_SESSION['user_id']));
		if ($db->num_rows() == 1)
		{
			// check they are okay with deleting it
			if (!isset($_POST['yes']) && !isset($_POST['no']))
			{
				$templating->set_previous('title', ' - Deleting comment', 1);
				$core->yes_no('Are you sure you want to delete that Personal Messaging thread?', "index.php?module=messages", 'Delete', $_POST['conversation_id'], 'conversation_id');
			}

			else if (isset($_POST['no']))
			{
				header("Location: /private-messages/");
			}

			else if (isset($_POST['yes']))
			{
				$db->sqlquery("DELETE FROM `user_conversations_info` WHERE `conversation_id` = ? AND `owner_id` = ?", array($_POST['conversation_id'], $_SESSION['username']));
				$db->sqlquery("DELETE FROM `user_conversations_participants` WHERE `conversation_id` = ? AND `participant_id` = ?", array($_POST['conversation_id'], $_SESSION['user_id']));
				// delete it
				header("Location: /private-messages/message=deleted");
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
		$text = htmlspecialchars($text);

		if (empty($_POST['conversation_id']) || !is_numeric($_POST['conversation_id']))
		{
			$core->message("Not a valid conversation! <a href=\"/private-messages/\">Click here to return.</a>");
		}

		else if (empty($text))
		{
			header("Location: /private-messages/{$_POST['conversation_id']}/message=empty");
		}

		else
		{
			// find last position
			$db->sqlquery("SELECT m.`position`, i.title FROM `user_conversations_messages` m INNER JOIN `user_conversations_info` i ON m.conversation_id = i.conversation_id WHERE m.`conversation_id` = ? ORDER BY m.`message_id` DESC LIMIT 1", array($_POST['conversation_id']));
			$last = $db->fetch();

			$position = $last['position'] + 1;

			// add the new reply
			$db->sqlquery("INSERT INTO `user_conversations_messages` SET `conversation_id` = ?, `author_id` = ?, `creation_date` = ?, `message` = ?, `position` = ?", array($_POST['conversation_id'], $_SESSION['user_id'], $core->date, $text, $position));
			$post_id = $db->grab_id();

			// update conversation info
			$db->sqlquery("UPDATE `user_conversations_info` SET `replies` = (replies + 1), `last_reply_date` = ?, `last_reply_id` = ? WHERE `conversation_id` = ?", array($core->date, $_SESSION['user_id'], $_POST['conversation_id']));

			// make unread notifications
			$db->sqlquery("SELECT `participant_id` FROM `user_conversations_participants` WHERE `conversation_id` = ? AND `participant_id` != ?", array($_POST['conversation_id'], $_SESSION['user_id']));
			$participants = $db->fetch_all_rows();
			foreach ($participants as $person)
			{
				$db->sqlquery("UPDATE `user_conversations_participants` SET `unread` = 1 WHERE `participant_id` = ? AND `conversation_id` = ?", array($person['participant_id'], $_POST['conversation_id']));

				// also while we are here, email each user to tell them they have a new reply
				$db->sqlquery("SELECT `username`, `email`, `email_on_pm` FROM `users` WHERE `user_id` = ? AND `user_id` != ?", array($person['participant_id'], $_SESSION['user_id']));
				$email_data = $db->fetch();

				if ($email_data['email_on_pm'] == 1)
				{
					// sort out registration email
					$to  = $email_data['email'];

					// subject
					$subject = 'New reply to a conversation on GamingOnLinux.com';

					$email_text = email_bbcode($text);

					$message = '';

					// message
					$html_message = "
					<html>
					<head>
					<title>New reply to a conversation on GamingOnLinux.com</title>
					</head>
					<body>
					<img src=\"http://www.gamingonlinux.com/templates/default/images/icon.png\" alt=\"Gaming On Linux\">
					<br />
					<p>Hello <strong>{$email_data['username']}</strong>,</p>
					<p><strong>{$_SESSION['username']}</strong> has replied to a conversation with you on <a href=\"http://www.gamingonlinux.com/private-messages/\" target=\"_blank\">gamingonlinux.com</a>, titled \"<a href=\"http://www.gamingonlinux.com/private-messages/\" target=\"_blank\"><strong>{$last['title']}</strong></a>\".</p>
					<br style=\"clear:both\">
					<div>
				 	<hr>
					{$email_text}
			 		<hr>
			  		<p>If you haven&#39;t registered at <a href=\"http://www.gamingonlinux.com\" target=\"_blank\">gamingonlinux.com</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> with some info about what you want us to do about it or if you logged in and found no message let us know!</p>
			  		<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
					</div>
					</body>
					</html>
					";

					$plain_message = PHP_EOL."Hello {$email_data['username']}, {$_SESSION['username']} has replied to a conversation with you on http://www.gamingonlinux.com/private-messages, titled \"{$last['title']}\",\r\n{$_POST['text']}";
					$boundary = uniqid('np');

					// To send HTML mail, the Content-type header must be set
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= "Content-Type: multipart/alternative;charset=utf-8;boundary=" . $boundary . "\r\n";
					$headers .= "From: GamingOnLinux.com Notification <noreply@gamingonlinux.com>\r\n" . "Reply-To: noreply@gamingonlinux.com\r\n";

					$message .= "\r\n\r\n--" . $boundary.PHP_EOL;
					$message .= "Content-Type: text/plain;charset=utf-8".PHP_EOL;
					$message .= "Content-Transfer-Encoding: 7bit".PHP_EOL;
					$message .= $plain_message;

					$message .= "\r\n\r\n--" . $boundary.PHP_EOL;
					$message .= "Content-Type: text/html;charset=utf-8".PHP_EOL;
					$message .= "Content-Transfer-Encoding: 7bit".PHP_EOL;
					$message .= "$html_message";
					$message .= "\r\n\r\n--" . $boundary . "--";

					// Mail it
					mail($to, $subject, $message, $headers);
				}
			}

			$db->sqlquery("SELECT `replies` FROM `user_conversations_info` WHERE `conversation_id` = ?", array($_POST['conversation_id']));
			$get_info = $db->fetch();

			$page = 1;
			if ($get_info['replies'] > 9)
			{
				$page = ceil($get_info['replies']/9);
			}

			if ($config['pretty_urls'] == 1)
			{
				header("Location: /private-messages/{$_POST['conversation_id']}/page=$page");
			}
			else {
				header("Location: {$config['path']}index.php?module=messages&view=message&id={$_POST['conversation_id']}&page=$page#$post_id");
			}


		}
	}
}

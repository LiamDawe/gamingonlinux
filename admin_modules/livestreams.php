<?php
$templating->merge('admin_modules/livestreams');

if (isset($_GET['view']) && !isset($_POST['act']))
{
	if ($_GET['view'] == 'manage')
	{
		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'edited')
			{
				$core->message("Livestream edited!");
			}
			if ($_GET['message'] == 'added')
			{
				$core->message("Livestream added");
			}
			if ($_GET['message'] == 'missing')
			{
				$core->message('Please fill a title, and a date!', null, 1);
			}
			if ($_GET['message'] == 'date_backwards')
			{
				$core->message('The livestream end date cannot be before it starts!', null, 1);
			}
		}

		$templating->set_previous('meta_description', 'Managing livestreams', 1);
		$templating->set_previous('title', 'Managing livestreams', 1);

		$templating->block('add_top', 'admin_modules/livestreams');
		
		$timezones = core::timezone_list($_SESSION['timezone']);

		$templating->block('item', 'admin_modules/livestreams');
		$templating->set('title', '');
		$templating->set('timezones_list', 'Select your timezone:<br />'.$timezones);
		$templating->set('date', '');
		$templating->set('end_date', '');
		$templating->set('community_check', '');
		$templating->set('community_name', '');
		$templating->set('stream_url', '');

		$templating->block('add_bottom', 'admin_modules/livestreams');

		$templating->block('edit_top', 'admin_modules/livestreams');

		$streams_grab = $db->sqlquery("SELECT `row_id`, `title`, `date`, `end_date`, `community_stream`, `streamer_community_name`, `stream_url` FROM `livestreams` ORDER BY `date` ASC");
		$streams_store = $db->fetch_all_rows();

		foreach ($streams_store as $streams)
		{
			$templating->block('item', 'admin_modules/livestreams');
			$templating->set('title', $streams['title']);
			$templating->set('id', $streams['row_id']);

			$date = new DateTime($streams['date']);
			$templating->set('date', $date->format('Y-m-d H:i:s'));

			$end_date = new DateTime($streams['end_date']);
			$templating->set('end_date', $end_date->format('Y-m-d H:i:s'));
			
			$templating->set('timezones_list', '');

			$streamer_list = '';
			$db->sqlquery("SELECT s.`user_id`, u.username FROM `livestream_presenters` s INNER JOIN users u ON u.user_id = s.user_id WHERE `livestream_id` = ?", array($streams['row_id']));
			while ($grab_streamers = $db->fetch())
			{
				$streamer_list .= '<option value="'.$grab_streamers['user_id'].'" selected>'.$grab_streamers['username'].'</option>';
			}
			$templating->set('users_list', $streamer_list);

			$community_check = '';
			if ($streams['community_stream'] == 1)
			{
				$community_check = 'checked';
			}
			$templating->set('community_check', $community_check);

			$templating->set('disabled_check', '');

			$templating->set('stream_url', $streams['stream_url']);

			$streamer_community_name = '';
			if (!empty($streams['streamer_community_name']))
			{
				$streamer_community_name = $streams['streamer_community_name'];
			}
			$templating->set('community_name', $streamer_community_name);

			$templating->block('edit_bottom', 'admin_modules/livestreams');
			$templating->set('id', $streams['row_id']);
		}
	}

	if ($_GET['view'] == 'submitted')
	{
		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'edited')
			{
				$core->message("Livestream edited!");
			}
			if ($_GET['message'] == 'denied')
			{
				$core->message("Livestream submission denied!");
			}
			if ($_GET['message'] == 'approved')
			{
				$core->message("Livestream submission approved!");
			}
			if ($_GET['message'] == 'missing')
			{
				$core->message('Please fill a title, and a date!', null, 1);
			}
			if ($_GET['message'] == 'missing_id')
			{
				$core->message('The submission approval was missing an ID, this is likely a bug!', null, 1);
			}
		}

		$templating->set_previous('meta_description', 'Managing submitted livestreams', 1);
		$templating->set_previous('title', 'Managing submitted livestreams', 1);

		$templating->block('submit_top', 'admin_modules/livestreams');

		$streams_grab = $db->sqlquery("SELECT `row_id`, `title`, `date`, `end_date`, `community_stream`, `streamer_community_name`, `stream_url` FROM `livestreams` WHERE `accepted` = 0 ORDER BY `date` ASC");
		$streams_store = $db->fetch_all_rows();

		foreach ($streams_store as $streams)
		{
			$templating->block('item', 'admin_modules/livestreams');
			$templating->set('title', $streams['title']);
			$templating->set('id', $streams['row_id']);
			
			$templating->set('timezones_list', '');

			$date = new DateTime($streams['date']);
			$templating->set('date', $date->format('Y-m-d H:i:s'));

			$end_date = new DateTime($streams['end_date']);
			$templating->set('end_date', $end_date->format('Y-m-d H:i:s'));

			$streamer_list = '';
			$db->sqlquery("SELECT s.`user_id`, u.username FROM `livestream_presenters` s INNER JOIN users u ON u.user_id = s.user_id WHERE `livestream_id` = ?", array($streams['row_id']));
			while ($grab_streamers = $db->fetch())
			{
				$streamer_list .= '<option value="'.$grab_streamers['user_id'].'" selected>'.$grab_streamers['username'].'</option>';
			}
			$templating->set('users_list', $streamer_list);

			$community_check = '';
			if ($streams['community_stream'] == 1)
			{
				$community_check = 'checked';
			}
			$templating->set('community_check', $community_check);

			$templating->set('disabled_check', 'disabled="disabled"');

			$templating->set('stream_url', $streams['stream_url']);

			$streamer_community_name = '';
			if (!empty($streams['streamer_community_name']))
			{
				$streamer_community_name = $streams['streamer_community_name'];
			}
			$templating->set('community_name', $streamer_community_name);

			$templating->block('submitted_bottom', 'admin_modules/livestreams');
			$templating->set('id', $streams['row_id']);
		}
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'Add')
	{
		if (empty($_POST['title']) || empty($_POST['date']))
		{
			header("Location: /admin.php?module=livestreams&view=manage&error=missing");
			die();
		}
		
		$start_time = core::adjust_time($_POST['date'], $_POST['timezone'], 'UTC');
		$end_time = core::adjust_time($_POST['end_date'], $_POST['timezone'], 'UTC');
		
		$title = trim($_POST['title']);
		$community_name = trim($_POST['community_name']);
		$stream_url = trim($_POST['stream_url']);
		
		if ($end_time < $start_time)
		{
			header("Location: /admin.php?module=livestreams&view=manage&message=date_backwards");
			die();
		}

		$date_created = core::$sql_date_now;

		$community = 0;
		if (isset($_POST['community']))
		{
			$community = 1;
		}

		$db->sqlquery("INSERT INTO `livestreams` SET `author_id` = ?, `accepted` = 1, `title` = ?, `date_created` = ?, `date` = ?, `end_date` = ?, `community_stream` = ?, `streamer_community_name` = ?, `stream_url` = ?", array($_SESSION['user_id'], $title, $date_created, $start_time, $end_time, $community, $community_name, $stream_url));
		$new_id = $db->grab_id();

		$core->process_livestream_users($new_id);

		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], 'new_livestream_event', core::$date, core::$date));

		header("Location: /admin.php?module=livestreams&view=manage&&message=added");
	}
	if ($_POST['act'] == 'Edit')
	{
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			header("Location: /admin.php?module=livestreams&view=manage&message=missing_id");
			die();
		}

		if (empty($_POST['title']) || empty($_POST['date']))
		{
			header("Location: /admin.php?module=livestreams&view=edit&message=missing&id=" . $_POST['id']);
			die();
		}

		$date = new DateTime($_POST['date']);
		$end_date = new DateTime($_POST['end_date']);

		$title = trim($_POST['title']);
		$community_name = trim($_POST['community_name']);
		$stream_url = trim($_POST['stream_url']);

		$community = 0;
		if (isset($_POST['community']))
		{
			$community = 1;
		}

		$db->sqlquery("UPDATE `livestreams` SET `title` = ?, `date` = ?, `end_date` = ?, `community_stream` = ?, `streamer_community_name` = ?, `stream_url` = ? WHERE `row_id` = ?", array($title, $date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s'), $community, $community_name, $stream_url, $_POST['id']));

		$core->process_livestream_users($_POST['id']);

		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = ?, `data` = ?, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], 'edit_livestream_event', $_POST['id'], core::$date, core::$date));

		header("Location: /admin.php?module=livestreams&view=manage&message=edited");
	}
	if ($_POST['act'] == 'Delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$db->sqlquery("SELECT `title` FROM `livestreams` WHERE `row_id` = ?", array($_POST['id']));
			$title = $db->fetch();

			$return = '';
			if (isset($_GET['return']) && !empty($_GET['return']))
			{
				$return = $_GET['return'];
			}

			$core->yes_no('Are you sure you want to delete ' . $title['title'] . ' from the livestream event list?', "admin.php?module=livestreams&id={$_POST['id']}", "Delete");
		}

		else if (isset($_POST['no']))
		{
			if (isset($_GET['return']) && !empty($_GET['return']))
			{
				header("Location: /index.php?module=livestreams");
				die();
			}
		}

		else if (isset($_POST['yes']))
		{
			$db->sqlquery("DELETE FROM `livestreams` WHERE `row_id` = ?", array($_GET['id']));

			$db->sqlquery("DELETE FROM `livestream_presenters` WHERE `id` = ?", array($_GET['id']));

			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = ?, `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], 'deleted_livestream_event', core::$date, core::$date));

			header("Location: /admin.php?module=livestreams&view=manage&message=deleted");
		}
	}

	if ($_POST['act'] == 'deny_submission')
	{
		$db->sqlquery("SELECT l.row_id, l.`title`, u.`username`, u.`email` FROM `livestreams` l INNER JOIN `users` u ON l.author_id = u.user_id WHERE l.`row_id` = ?", array($_POST['id']));
		$livestream = $db->fetch();

		if (!isset($_POST['go']))
		{
			$templating->block('deny_reason');
			$templating->set('title', $livestream['title']);
			$templating->set('username', $livestream['username']);
			$templating->set('id', $livestream['row_id']);
		}
		else if (isset($_POST['go']) && $_POST['go'] == 'nope')
		{
			header("Location: /admin.php?module=livestreams&view=submitted");
			die();
		}

		else if (isset($_POST['go']) && $_POST['go'] == 'deny')
		{
			$comment_email = email_bbcode($_POST['message']);

			// subject
			$subject = "Your livestream event submission was denied on GamingOnLinux.com";

			// message
			$html_message = "<p>Hello <strong>{$livestream['username']}</strong>,</p>
			<p><strong>{$_SESSION['username']}</strong> has denied your livestream even submission, sorry!</p>
			<div>
			<hr>
			{$comment_email}
			<hr>
				<p>If you haven&#39;t registered at <a href=\"" . core::config('website_url') . "\" target=\"_blank\">" . core::config('website_url') . "</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> with some info about what you want us to do about it or if you logged in and found no message let us know!</p>
				<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
				<p>-----------------------------------------------------------------------------------------------------------</p>
			</div>";

			$plain_message = PHP_EOL."Hello {$livestream['username']}, {$_SESSION['username']} has denied your livestream even submission, sorry!";

			// Mail it
			if (core::config('send_emails') == 1)
			{
				$mail = new mail($livestream['email'], $subject, $html_message, $plain_message);
				$mail->send();
			}

			$db->sqlquery("DELETE FROM `livestreams` WHERE `row_id` = ?", array($_POST['id']));

			$db->sqlquery("DELETE FROM `livestream_presenters` WHERE `id` = ?", array($_POST['id']));

			$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'denied_livestream_submission', `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], core::$date, core::$date));
			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE type = 'new_livestream_submission' AND `data` = ?", array(core::$date, $livestream['row_id']));

			header("Location: /admin.php?module=livestreams&view=submitted&message=denied");
		}
	}

	if ($_POST['act'] == 'approve_submission')
	{
		if (empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			header("Location: /admin.php?module=livestreams&view=submitted&message=missing_id");
			die();
		}

		if (empty($_POST['title']) || empty($_POST['date']))
		{
			header("Location: /admin.php?module=livestreams&view=edit&message=missing");
			die();
		}

		$date = new DateTime($_POST['date']);
		$end_date = new DateTime($_POST['end_date']);
		$title = trim($_POST['title']);
		$community_name = trim($_POST['community_name']);
		$stream_url = trim($_POST['stream_url']);

		$db->sqlquery("SELECT l.row_id, l.`title`, u.`username`, u.`email` FROM `livestreams` l INNER JOIN `users` u ON l.author_id = u.user_id WHERE l.`row_id` = ?", array($_POST['id']));
		$livestream = $db->fetch();

		$comment_email = email_bbcode($_POST['message']);

		// subject
		$subject = "Your livestream event submission was approved on GamingOnLinux.com";

		// message
		$html_message = "<p>Hello <strong>{$livestream['username']}</strong>,</p>
		<p><strong>{$_SESSION['username']}</strong> has approved your livestream even submission, thanks for sending it in!</p>
		<div>
		<hr>
			<p>If you haven&#39;t registered at <a href=\"" . core::config('website_url') . "\" target=\"_blank\">" . core::config('website_url') . "</a>, Forward this mail to <a href=\"mailto:liamdawe@gmail.com\" target=\"_blank\">liamdawe@gmail.com</a> with some info about what you want us to do about it or if you logged in and found no message let us know!</p>
			<p>Please, Don&#39;t reply to this automated message, We do not read any mails recieved on this email address.</p>
			<p>-----------------------------------------------------------------------------------------------------------</p>
		</div>";

		$plain_message = PHP_EOL."Hello {$livestream['username']}, {$_SESSION['username']} has approved your livestream even submission, thanks for sending it in!";

		// Mail it
		if (core::config('send_emails') == 1)
		{
			$mail = new mail($livestream['email'], $subject, $html_message, $plain_message);
			$mail->send();
		}

		$db->sqlquery("UPDATE `livestreams` SET `accepted` = 1, `title` = ?, `date` = ?, `end_date` = ?, `streamer_community_name` = ?, `stream_url` = ? WHERE `row_id` = ?", array($title, $date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s'), $community_name, $stream_url, $_POST['id']));

		$core->process_livestream_users($_POST['id']);

		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `type` = 'accepted_livestream_submission', `created_date` = ?, `completed_date` = ?", array($_SESSION['user_id'], core::$date, core::$date));
		$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE type = 'new_livestream_submission' AND `data` = ?", array(core::$date, $livestream['row_id']));

		header("Location: /admin.php?module=livestreams&view=submitted&message=approved");
	}
}

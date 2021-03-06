<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: admin livestreams config.');
}

$templating->load('admin_modules/livestreams');

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
				$core->message('Please fill a title, and a date!', 1);
			}
			if ($_GET['message'] == 'date_backwards')
			{
				$core->message('The livestream end date cannot be before it starts!', 1);
			}
		}

		$templating->set_previous('meta_description', 'Managing livestreams', 1);
		$templating->set_previous('title', 'Managing livestreams', 1);

		$templating->block('add_top', 'admin_modules/livestreams');
		
		$timezones = core::timezone_list($user->user_details['timezone']);

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

		$streams_store = $dbl->run("SELECT `row_id`, `title`, `date`, `end_date`, `community_stream`, `streamer_community_name`, `stream_url` FROM `livestreams` ORDER BY `date` ASC")->fetch_all();

		foreach ($streams_store as $streams)
		{
			$templating->block('item', 'admin_modules/livestreams');
			$templating->set('title', htmlentities($streams['title'], ENT_QUOTES));
			$templating->set('id', $streams['row_id']);

			$date = new DateTime($streams['date']);
			$templating->set('date', $date->format('Y-m-d'));
			$templating->set('time', $date->format('H:i'));

			$end_date = new DateTime($streams['end_date']);
			$templating->set('end_date', $end_date->format('Y-m-d'));
			$templating->set('end_time', $end_date->format('H:i'));
			
			$templating->set('timezones_list', '');

			$streamer_list = '';
			$stream_res = $dbl->run("SELECT s.`user_id`, u.username FROM `livestream_presenters` s INNER JOIN `users` u ON u.user_id = s.user_id WHERE `livestream_id` = ?", array($streams['row_id']))->fetch_all();
			foreach ($stream_res as $grab_streamers)
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
				$core->message('Please fill a title, and a date!', 1);
			}
			if ($_GET['message'] == 'missing_id')
			{
				$core->message('The submission approval was missing an ID, this is likely a bug!', 1);
			}
		}

		$templating->set_previous('meta_description', 'Managing submitted livestreams', 1);
		$templating->set_previous('title', 'Managing submitted livestreams', 1);

		$templating->block('submit_top', 'admin_modules/livestreams');

		$streams_store = $dbl->run("SELECT `row_id`, `title`, `date`, `end_date`, `community_stream`, `streamer_community_name`, `stream_url` FROM `livestreams` WHERE `accepted` = 0 ORDER BY `date` ASC")->fetch_all();

		foreach ($streams_store as $streams)
		{
			$templating->block('item', 'admin_modules/livestreams');
			$templating->set('title', htmlentities($streams['title'], ENT_QUOTES));
			$templating->set('id', $streams['row_id']);
			
			$templating->set('timezones_list', '');

			$date = new DateTime($streams['date']);
			$templating->set('date', $date->format('Y-m-d'));
			$templating->set('time', $date->format('H:i'));

			$end_date = new DateTime($streams['end_date']);
			$templating->set('end_date', $end_date->format('Y-m-d'));
			$templating->set('end_time', $end_date->format('H:i'));

			$streamer_list = '';
			$stream_res = $dbl->run("SELECT s.`user_id`, u.username FROM `livestream_presenters` s INNER JOIN `users` u ON u.user_id = s.user_id WHERE `livestream_id` = ?", array($streams['row_id']))->fetch_all();
			foreach ($stream_res as $grab_streamers)
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
		$title = trim($_POST['title']);
		$start_time = trim($_POST['date']);
		$end_time = trim($_POST['end_date']);
		
		$check_empty = core::mempty(compact('title', 'start_time', 'end_time'));
		if ($check_empty !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $check_empty;
			header("Location: /admin.php?module=livestreams&view=manage");
			die();
		}
		
		$start_time = core::adjust_time($_POST['date'].$_POST['time'], $_POST['timezone'], 'UTC', 0);
		$end_time = core::adjust_time($_POST['end_date'].$_POST['end_time'], $_POST['timezone'], 'UTC', 0);
		
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

			if (empty($stream_url))
			{
				$_SESSION['message'] = 'community_link_needed';
				header("Location: /admin.php?module=livestreams&view=manage");
				die();				
			}
		}

		$dbl->run("INSERT INTO `livestreams` SET `author_id` = ?, `accepted` = 1, `title` = ?, `date_created` = ?, `date` = ?, `end_date` = ?, `community_stream` = ?, `streamer_community_name` = ?, `stream_url` = ?", array($_SESSION['user_id'], $title, $date_created, $start_time, $end_time, $community, $community_name, $stream_url));
		$new_id = $dbl->new_id();
		
		$user_ids = [];
		if (isset($_POST['user_ids']) && !empty($_POST['user_ids']))
		{
			$user_ids = $_POST['user_ids'];
		}

		$core->process_livestream_users($new_id, $user_ids);

		$core->new_admin_note(array('completed' => 1, 'content' => ' added a new livestream event to the <a href="/index.php?module=livestreams">schedule page</a>.'));

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

		$date = new DateTime($_POST['date'].$_POST['time']);
		$end_date = new DateTime($_POST['end_date'].$_POST['end_time']);

		$title = trim($_POST['title']);
		$community_name = trim($_POST['community_name']);
		$stream_url = trim($_POST['stream_url']);

		$community = 0;
		if (isset($_POST['community']))
		{
			$community = 1;
		}

		$dbl->run("UPDATE `livestreams` SET `title` = ?, `date` = ?, `end_date` = ?, `community_stream` = ?, `streamer_community_name` = ?, `stream_url` = ? WHERE `row_id` = ?", array($title, $date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s'), $community, $community_name, $stream_url, $_POST['id']));
		
		$user_ids = [];
		if (isset($_POST['user_ids']) && !empty($_POST['user_ids']))
		{
			$user_ids = $_POST['user_ids'];
		}

		$core->process_livestream_users($_POST['id'], $user_ids);

		$core->new_admin_note(array('completed' => 1, 'content' => ' edited a livestream event on the <a href="/index.php?module=livestreams">schedule page</a>.'));

		header("Location: /admin.php?module=livestreams&view=manage&message=edited");
		die();
	}
	if ($_POST['act'] == 'Delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$title = $dbl->run("SELECT `title` FROM `livestreams` WHERE `row_id` = ?", array($_POST['id']))->fetchOne();

			$core->confirmation(['title' => 'Are you sure you want to delete ' . $title . ' from the livestream event list?', 'text' => 'This cannot be undone!', 'action_url' => "admin.php?module=livestreams&id={$_POST['id']}", 'act' => "Delete"]);
		}

		else if (isset($_POST['no']))
		{
			header("Location: /admin.php?module=livestreams&view=manage");
			die();
		}

		else if (isset($_POST['yes']))
		{
			$dbl->run("DELETE FROM `livestreams` WHERE `row_id` = ?", array($_GET['id']));

			$dbl->run("DELETE FROM `livestream_presenters` WHERE `id` = ?", array($_GET['id']));

			$core->new_admin_note(array('completed' => 1, 'content' => ' deleted a livestream event from the <a href="/index.php?module=livestreams">schedule page</a>.'));

			$_SESSION['message'] = 'deleted';
			$_SESSION['message_extra'] = 'livestream';
			header("Location: /admin.php?module=livestreams&view=manage&message=deleted");
			die();
		}
	}

	if ($_POST['act'] == 'deny_submission')
	{
		$livestream = $dbl->run("SELECT l.row_id, l.`title`, u.`username`, u.`email` FROM `livestreams` l INNER JOIN `users` u ON l.author_id = u.user_id WHERE l.`row_id` = ?", array($_POST['id']))->fetch();

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
			$comment_email = $bbcode->email_bbcode($_POST['message']);

			// subject
			$subject = "Your livestream event submission was denied on GamingOnLinux.com";

			// message
			$html_message = "<p>Hello <strong>{$livestream['username']}</strong>,</p>
			<p><strong>{$_SESSION['username']}</strong> has denied your livestream even submission, sorry!</p>
			<div>
			<hr>
			{$comment_email}";

			$plain_message = PHP_EOL."Hello {$livestream['username']}, {$_SESSION['username']} has denied your livestream even submission, sorry!";

			// Mail it
			if ($core->config('send_emails') == 1)
			{
				$mail = new mailer($core);
				$mail->sendMail($livestream['email'], $subject, $html_message, $plain_message);
			}

			$dbl->run("DELETE FROM `livestreams` WHERE `row_id` = ?", array($_POST['id']));

			$dbl->run("DELETE FROM `livestream_presenters` WHERE `id` = ?", array($_POST['id']));

			// notify editors you've done this
			$core->update_admin_note(array('type' => 'new_livestream_submission', 'data' => $livestream['row_id']));

			$core->new_admin_note(array('completed' => 1, 'content' => ' denied a new livestream submission event on the <a href="/index.php?module=livestreams">schedule page</a>.'));

			header("Location: /admin.php?module=livestreams&view=submitted&message=denied");
			die();
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

		$date = new DateTime($_POST['date'].$_POST['time']);
		$end_date = new DateTime($_POST['end_date'].$_POST['end_time']);
		$title = trim($_POST['title']);
		$community_name = trim($_POST['community_name']);
		$stream_url = trim($_POST['stream_url']);

		$livestream = $dbl->run("SELECT l.row_id, l.`title`, u.`username`, u.`email` FROM `livestreams` l INNER JOIN `users` u ON l.author_id = u.user_id WHERE l.`row_id` = ?", array($_POST['id']))->fetch();

		// subject
		$subject = "Your livestream event submission was approved on GamingOnLinux.com";

		// message
		$html_message = "<p>Hello <strong>{$livestream['username']}</strong>,</p>
		<p><strong>{$_SESSION['username']}</strong> has approved your livestream even submission, thanks for sending it in!</p>";

		$plain_message = PHP_EOL."Hello {$livestream['username']}, {$_SESSION['username']} has approved your livestream even submission, thanks for sending it in!";

		// Mail it
		if ($core->config('send_emails') == 1)
		{
			$mail = new mailer($core);
			$mail->sendMail($livestream['email'], $subject, $html_message, $plain_message);
		}

		$dbl->run("UPDATE `livestreams` SET `accepted` = 1, `title` = ?, `date` = ?, `end_date` = ?, `streamer_community_name` = ?, `stream_url` = ? WHERE `row_id` = ?", array($title, $date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s'), $community_name, $stream_url, $_POST['id']));
		
		$user_ids = [];
		if (isset($_POST['user_ids']) && !empty($_POST['user_ids']))
		{
			$user_ids = $_POST['user_ids'];
		}

		$core->process_livestream_users($_POST['id'], $user_ids);

		// notify editors you've done this
		$core->update_admin_note(array('type' => 'new_livestream_submission', 'data' => $livestream['row_id']));

		$core->new_admin_note(array('completed' => 1, 'content' => ' approved a new livestream submission event on the <a href="/index.php?module=livestreams">schedule page</a>.'));

		header("Location: /admin.php?module=livestreams&view=submitted&message=approved");
		die();
	}
}
